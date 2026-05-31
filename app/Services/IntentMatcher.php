<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Intent;
use App\Models\IntentPhrase;
use App\Models\Synonym;
use App\Models\UnmatchedQuestion;

class IntentMatcher
{
    private const CONFIDENCE_THRESHOLD = 40.0;
    private const AMBIGUITY_THRESHOLD  = 0.20; // 20 % difference triggers clarification

    private ConversationEngine $engine;

    public function __construct()
    {
        $this->engine = new ConversationEngine();
    }

    // -------------------------------------------------------------------------
    // Public entry point
    // -------------------------------------------------------------------------

    public function match(string $message, string $sessionId): array
    {
        $normalized = $this->normalizeText($message);

        // ------------------------------------------------------------------
        // 1. Exact phrase match across ALL active intents
        // ------------------------------------------------------------------
        $exactIntent = $this->exactPhraseMatch($normalized);

        if ($exactIntent && !$this->engine->isConversationalKey($exactIntent->intent_key)) {
            // Pure business exact match — return immediately
            $response = $this->buildResponse($exactIntent);
            $this->logConversation($sessionId, $message, $normalized, $exactIntent->id, 100.0, $response['text']);

            return [
                'intent'      => $exactIntent->intent_key,
                'confidence'  => 100,
                'response'    => ['text' => $response['text']],
                'suggestions' => $response['suggestions'],
            ];
        }

        // ------------------------------------------------------------------
        // 2. Tokenize + synonym expansion
        // ------------------------------------------------------------------
        $tokens = $this->tokenize($normalized);
        $tokens = $this->expandSynonyms($tokens);

        // ------------------------------------------------------------------
        // 3. Detect conversational intent
        //    Either from the exact phrase match above, or via engine scan
        // ------------------------------------------------------------------
        $conversationalIntent = null;

        if ($exactIntent && $this->engine->isConversationalKey($exactIntent->intent_key)) {
            $conversationalIntent = $exactIntent;
        } elseif (!empty($tokens)) {
            $conversationalIntent = $this->engine->detect($normalized);
        }

        // ------------------------------------------------------------------
        // 4. Business keyword scoring (exclude conversational intents)
        // ------------------------------------------------------------------
        $businessIntent = null;
        $businessConfidence = 0.0;

        if (!empty($tokens)) {
            $scores = $this->keywordScoringExcluding($tokens, ConversationEngine::CONVERSATIONAL_KEYS);

            if (!empty($scores)) {
                arsort($scores);
                $topIds     = array_keys($scores);
                $topScore   = $scores[$topIds[0]];
                $totalScore = array_sum($scores);
                $confidence = $this->confidenceCalc($topScore, $totalScore);

                if ($confidence >= self::CONFIDENCE_THRESHOLD) {
                    // Ambiguity check (only relevant when there is no conversational intent)
                    if (
                        count($topIds) >= 2
                        && $this->ambiguityCheck($scores[$topIds[0]], $scores[$topIds[1]])
                        && $conversationalIntent === null
                    ) {
                        $topIntents = Intent::whereIn('id', array_slice($topIds, 0, 4))->get()->keyBy('id');
                        $options    = collect(array_slice($topIds, 0, 4))
                            ->map(fn ($id) => $topIntents[$id]->title ?? null)
                            ->filter()
                            ->values()
                            ->toArray();

                        $this->logConversation($sessionId, $message, $normalized, null, $confidence, null);

                        return [
                            'type'    => 'clarification',
                            'message' => 'I found multiple related topics. Which one do you mean?',
                            'options' => $options,
                        ];
                    }

                    $businessIntent    = Intent::find($topIds[0]);
                    $businessConfidence = round($confidence, 2);
                }
            }
        }

        // ------------------------------------------------------------------
        // 5. Decision tree
        // ------------------------------------------------------------------

        // 5a. Hybrid — conversational prefix + business answer
        if ($conversationalIntent && $businessIntent && $this->engine->isHybridEligible($conversationalIntent->intent_key)) {
            $businessResponse = $this->buildResponse($businessIntent);
            $hybridText       = $this->engine->buildHybridText($conversationalIntent, $businessResponse['text']);

            $this->logConversation($sessionId, $message, $normalized, $businessIntent->id, 90.0, $hybridText);

            return [
                'intent'      => $businessIntent->intent_key,
                'confidence'  => 90,
                'response'    => ['text' => $hybridText],
                'suggestions' => $businessResponse['suggestions'],
            ];
        }

        // 5b. Pure conversational
        if ($conversationalIntent) {
            $response = $this->buildResponse($conversationalIntent);
            $this->logConversation($sessionId, $message, $normalized, $conversationalIntent->id, 100.0, $response['text']);

            return [
                'intent'      => $conversationalIntent->intent_key,
                'confidence'  => 100,
                'response'    => ['text' => $response['text']],
                'suggestions' => $response['suggestions'],
            ];
        }

        // 5c. Pure business
        if ($businessIntent) {
            $response = $this->buildResponse($businessIntent);
            $this->logConversation($sessionId, $message, $normalized, $businessIntent->id, $businessConfidence, $response['text']);

            return [
                'intent'      => $businessIntent->intent_key,
                'confidence'  => $businessConfidence,
                'response'    => ['text' => $response['text']],
                'suggestions' => $response['suggestions'],
            ];
        }

        // 5d. No tokens at all
        if (empty($tokens)) {
            return $this->fallbackResponse($sessionId, $message, $normalized);
        }

        // 5e. Fallback — log the unmatched question
        UnmatchedQuestion::create(['question' => $message]);

        return $this->fallbackResponse($sessionId, $message, $normalized);
    }

    // -------------------------------------------------------------------------
    // Text normalization
    // -------------------------------------------------------------------------

    public function normalizeText(string $text): string
    {
        return IntentPhrase::normalize($text);
    }

    // -------------------------------------------------------------------------
    // Exact phrase match (all active intents)
    // -------------------------------------------------------------------------

    private function exactPhraseMatch(string $normalized): ?Intent
    {
        $phrase = IntentPhrase::where('normalized_phrase', $normalized)
            ->whereHas('intent', fn ($q) => $q->where('is_active', true))
            ->with('intent')
            ->first();

        return $phrase?->intent;
    }

    // -------------------------------------------------------------------------
    // Tokenize
    // -------------------------------------------------------------------------

    private function tokenize(string $normalized): array
    {
        return array_values(
            array_filter(explode(' ', $normalized), fn ($t) => strlen($t) > 1)
        );
    }

    // -------------------------------------------------------------------------
    // Synonym expansion
    // -------------------------------------------------------------------------

    private function expandSynonyms(array $tokens): array
    {
        $synonymMap = Synonym::whereIn('synonym', $tokens)
            ->pluck('word', 'synonym')
            ->toArray();

        $expanded = [];
        foreach ($tokens as $token) {
            $expanded[] = $token;
            if (isset($synonymMap[$token]) && $synonymMap[$token] !== $token) {
                $expanded[] = $synonymMap[$token];
            }
        }

        return array_unique($expanded);
    }

    // -------------------------------------------------------------------------
    // Keyword scoring (excludes specified intent keys)
    // -------------------------------------------------------------------------

    private function keywordScoringExcluding(array $tokens, array $excludeKeys): array
    {
        $intents = Intent::where('is_active', true)
            ->whereNotIn('intent_key', $excludeKeys)
            ->with('keywords')
            ->get();

        $scores = [];
        foreach ($intents as $intent) {
            $score = 0;
            foreach ($intent->keywords as $keyword) {
                if (in_array($keyword->keyword, $tokens, true)) {
                    $score += $keyword->weight;
                }
            }
            if ($score > 0) {
                $scores[$intent->id] = $score;
            }
        }

        return $scores;
    }

    // -------------------------------------------------------------------------
    // Confidence calculation
    // -------------------------------------------------------------------------

    private function confidenceCalc(float $winningScore, float $totalScore): float
    {
        if ($totalScore === 0.0) {
            return 0.0;
        }
        return ($winningScore / $totalScore) * 100;
    }

    // -------------------------------------------------------------------------
    // Ambiguity check
    // -------------------------------------------------------------------------

    private function ambiguityCheck(float $first, float $second): bool
    {
        if ($first === 0.0) {
            return false;
        }
        return (($first - $second) / $first) < self::AMBIGUITY_THRESHOLD;
    }

    // -------------------------------------------------------------------------
    // Build response with follow-up suggestions
    // -------------------------------------------------------------------------

    private function buildResponse(Intent $intent): array
    {
        $intent->loadMissing('followUpIntents');
        $suggestions = $intent->followUpIntents->pluck('title')->toArray();

        return [
            'text'        => $intent->response,
            'suggestions' => $suggestions,
        ];
    }

    // -------------------------------------------------------------------------
    // Log conversation
    // -------------------------------------------------------------------------

    private function logConversation(
        string $sessionId,
        string $userMessage,
        string $normalizedMessage,
        ?int $intentId,
        float $confidence,
        ?string $botResponse
    ): void {
        Conversation::create([
            'session_id'         => $sessionId,
            'user_message'       => $userMessage,
            'normalized_message' => $normalizedMessage,
            'intent_id'          => $intentId,
            'confidence'         => $confidence,
            'bot_response'       => $botResponse,
        ]);
    }

    // -------------------------------------------------------------------------
    // Fallback response
    // -------------------------------------------------------------------------

    private function fallbackResponse(string $sessionId, string $message, string $normalized): array
    {
        $this->logConversation($sessionId, $message, $normalized, null, 0.0, null);

        // Fetch a few high-priority business intents as suggestions
        $suggestions = Intent::where('is_active', true)
            ->whereNotIn('intent_key', ConversationEngine::CONVERSATIONAL_KEYS)
            ->orderByDesc('priority')
            ->limit(4)
            ->pluck('title')
            ->toArray();

        return [
            'type'        => 'fallback',
            'message'     => "I'm here to help with APPUI support topics. Please try rephrasing your question.",
            'suggestions' => $suggestions,
        ];
    }
}
