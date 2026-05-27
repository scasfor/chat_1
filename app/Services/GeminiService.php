<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    private string $apiKey;

    public function __construct()
    {
        $key = Setting::get('gemini_api_key', '');

        if (empty($key)) {
            throw new \RuntimeException('Gemini API key is not configured. Please set it in Settings.');
        }

        $this->apiKey = $key;
    }

    /**
     * Generate phrases and keywords for a chatbot intent.
     *
     * @return array{phrases: string[], keywords: array{keyword: string, weight: int}[]}
     */
    public function generateIntentData(string $question, string $response): array
    {
        $prompt = <<<PROMPT
You are configuring a rule-based chatbot. Given the following intent:

Question: {$question}
Response: {$response}

Generate ONLY a JSON object (no markdown, no explanation) with:
1. "phrases": 5-8 natural language variations of the question (array of strings)
2. "keywords": 5-10 important single words or short terms with weight 1-10, where 10 is most important (array of objects)

Example format:
{"phrases":["how to login","how do i sign in","sign in help"],"keywords":[{"keyword":"login","weight":9},{"keyword":"password","weight":6}]}
PROMPT;

        $httpResponse = Http::timeout(30)->post(self::API_URL . '?key=' . $this->apiKey, [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'temperature'        => 0.2,
                'responseMimeType'   => 'application/json',
            ],
        ]);

        if ($httpResponse->failed()) {
            throw new \RuntimeException(
                'Gemini API request failed with status ' . $httpResponse->status() . ': ' . $httpResponse->body()
            );
        }

        $text = $httpResponse->json('candidates.0.content.parts.0.text', '{}');

        // Strip markdown code fences if present
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim((string) $text));
        $text = preg_replace('/\s*```$/m', '', $text);

        $data = json_decode(trim($text), true);

        if (!is_array($data) || !array_key_exists('phrases', $data) || !array_key_exists('keywords', $data)) {
            throw new \RuntimeException('Unexpected response format from Gemini API: ' . $text);
        }

        return $data;
    }
}
