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
    private const AMBIGUITY_THRESHOLD  = 0.20; // 20% difference triggers clarification

    // -------------------------------------------------------------------------
    // Public entry point
    // -------------------------------------------------------------------------

    public function match(string $message, string $sessionId): array
    {
        $normalized = $this->normalizeText($message);

        // 1. Exact phrase match
        $exactIntent = $this->exactPhraseMatch($normalized);
        if ($exactIntent) {
            $response = $this->buildResponse($exactIntent);
            $this->logConversation($sessionId, $message, $normalized, $exactIntent->id, 100.0, $response['text']);
            return [
                'intent'      => $exactIntent->intent_key,
                'confidence'  => 100,
                'response'    => ['text' => $response['text']],
                'suggestions' => $response['suggestions'],
            ];
        }

        // 2. Tokenize + synonym expansion
        $tokens = $this->tokenize($normalized);
        $tokens = $this->expandSynonyms($tokens);

        if (empty($tokens)) {
            return $this->fallbackResponse($sessionId, $message, $normalized);
        }

        // 3. Keyword scoring
        $scores = $this->keywordScoring($tokens);

        if (empty($scores)) {
            return $this->fallbackResponse($sessionId, $message, $normalized);
        }

        arsort($scores);
        $topIds    = array_keys($scores);
        $topScore  = $scores[$topIds[0]];
        $totalScore = array_sum($scores);
        $confidence = $this->confidenceCalc($topScore, $totalScore);

        // 4. Below threshold → fallback
        if ($confidence < self::CONFIDENCE_THRESHOLD) {
            UnmatchedQuestion::create(['question' => $message]);
            return $this->fallbackResponse($sessionId, $message, $normalized);
        }

        // 5. Ambiguity check
        if (count($topIds) >= 2 && $this->ambiguityCheck($scores[$topIds[0]], $scores[$topIds[1]])) {
            $topIntents = Intent::whereIn('id', array_slice($topIds, 0, 4))->get()->keyBy('id');
            $options = collect(array_slice($topIds, 0, 4))
                ->map(fn($id) => $topIntents[$id]->title ?? null)
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

        // 6. Single best match
        $intent   = Intent::find($topIds[0]);
        $response = $this->buildResponse($intent);

        $this->logConversation($sessionId, $message, $normalized, $intent->id, $confidence, $response['text']);

        return [
            'intent'      => $intent->intent_key,
            'confidence'  => round($confidence, 2),
            'response'    => ['text' => $response['text']],
            'suggestions' => $response['suggestions'],
        ];
    }

    // -------------------------------------------------------------------------
    // Step 3a — Text normalization
    // -------------------------------------------------------------------------

    public function normalizeText(string $text): string
    {
        return IntentPhrase::normalize($text);
    }

    // -------------------------------------------------------------------------
    // Step 3b — Exact phrase match
    // -------------------------------------------------------------------------

    private function exactPhraseMatch(string $normalized): ?Intent
    {
        $phrase = IntentPhrase::where('normalized_phrase', $normalized)
            ->with('intent')
            ->first();

        return $phrase?->intent;
    }

    // -------------------------------------------------------------------------
    // Tokenize
    // -------------------------------------------------------------------------

    private function tokenize(string $normalized): array
    {
        return array_filter(explode(' ', $normalized), fn($t) => strlen($t) > 1);
    }

    // -------------------------------------------------------------------------
    // Step 3c — Synonym expansion
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
    // Step 3d — Keyword scoring
    // -------------------------------------------------------------------------

    private function keywordScoring(array $tokens): array
    {
        $intents = Intent::where('is_active', true)
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
    // Step 3e — Confidence calculation
    // -------------------------------------------------------------------------

    private function confidenceCalc(float $winningScore, float $totalScore): float
    {
        if ($totalScore === 0.0) {
            return 0.0;
        }
        return ($winningScore / $totalScore) * 100;
    }

    // -------------------------------------------------------------------------
    // Step 3f — Ambiguity check
    // -------------------------------------------------------------------------

    private function ambiguityCheck(float $first, float $second): bool
    {
        if ($first === 0.0) {
            return false;
        }
        return (($first - $second) / $first) < self::AMBIGUITY_THRESHOLD;
    }

    // -------------------------------------------------------------------------
    // Step 3g — Build response with follow-up suggestions
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
    // Step 3h — Log conversation
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
    // Fallback response helper
    // -------------------------------------------------------------------------

    private function fallbackResponse(string $sessionId, string $message, string $normalized): array
    {
        $this->logConversation($sessionId, $message, $normalized, null, 0.0, null);

        return [
            'type'    => 'fallback',
            'message' => "I couldn't find a matching topic. Please try rephrasing or contact support.",
        ];
    }
}
