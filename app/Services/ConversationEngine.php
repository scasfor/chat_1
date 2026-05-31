<?php

namespace App\Services;

use App\Models\Intent;
use App\Models\IntentPhrase;

class ConversationEngine
{
    /**
     * Intent keys that belong to the general conversation layer.
     * These are matched before business intents in the processing pipeline.
     */
    public const CONVERSATIONAL_KEYS = [
        'greeting',
        'goodbye',
        'thanks',
        'help',
        'yes_confirmation',
        'no_confirmation',
    ];

    /**
     * Conversational intents that can participate in hybrid responses
     * (i.e. combined with a business intent in the same message).
     */
    private const HYBRID_ELIGIBLE = ['greeting', 'help'];

    /**
     * Detect a conversational intent from the normalised message.
     * Returns the matched Intent or null if none found.
     */
    public function detect(string $normalized): ?Intent
    {
        // 1. Exact phrase match against conversational intents
        $phrase = IntentPhrase::where('normalized_phrase', $normalized)
            ->whereHas('intent', fn ($q) => $q
                ->whereIn('intent_key', self::CONVERSATIONAL_KEYS)
                ->where('is_active', true)
            )
            ->with('intent')
            ->first();

        if ($phrase) {
            return $phrase->intent;
        }

        // 2. Keyword scan across conversational intents
        $tokens = array_values(
            array_filter(explode(' ', $normalized), fn ($t) => strlen($t) > 1)
        );

        if (empty($tokens)) {
            return null;
        }

        $intents = Intent::whereIn('intent_key', self::CONVERSATIONAL_KEYS)
            ->where('is_active', true)
            ->with('keywords')
            ->orderByDesc('priority')
            ->get();

        $best      = null;
        $bestScore = 0;

        foreach ($intents as $intent) {
            $score = 0;
            foreach ($intent->keywords as $keyword) {
                if (in_array($keyword->keyword, $tokens, true)) {
                    $score += $keyword->weight;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = $intent;
            }
        }

        return $bestScore > 0 ? $best : null;
    }

    /**
     * Whether a given intent key belongs to the conversational layer.
     */
    public function isConversationalKey(string $key): bool
    {
        return in_array($key, self::CONVERSATIONAL_KEYS, true);
    }

    /**
     * Whether a conversational intent can participate in a hybrid response.
     */
    public function isHybridEligible(string $intentKey): bool
    {
        return in_array($intentKey, self::HYBRID_ELIGIBLE, true);
    }

    /**
     * Prepend a short conversational prefix to a business response text.
     */
    public function buildHybridText(Intent $conversational, string $businessText): string
    {
        $prefix = match ($conversational->intent_key) {
            'greeting' => 'Hello! 👋 ',
            'help'     => 'Sure, I can help! ',
            default    => '',
        };

        return $prefix . $businessText;
    }
}
