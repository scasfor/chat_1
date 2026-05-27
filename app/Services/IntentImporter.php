<?php

namespace App\Services;

use App\Models\Intent;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;

class IntentImporter
{
    public function __construct(private GeminiService $gemini) {}

    /**
     * Import intents from an XLSX file.
     * Expected columns: Category ID | Question | Response
     *
     * @return array{imported: int, failed: int, errors: string[]}
     */
    public function import(string $filePath): array
    {
        $results = ['imported' => 0, 'failed' => 0, 'errors' => []];

        $reader = new Reader();
        $reader->open($filePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            $rowIndex = 0;

            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;

                if ($rowIndex === 1) {
                    continue; // skip header row
                }

                $cells = $row->getCells();

                if (count($cells) < 3) {
                    continue;
                }

                $categoryId = trim((string) $cells[0]->getValue());
                $question   = trim((string) $cells[1]->getValue());
                $response   = trim((string) $cells[2]->getValue());

                // Skip empty or non-numeric category rows
                if (empty($question) || empty($response) || !is_numeric($categoryId)) {
                    continue;
                }

                try {
                    $this->processRow((int) $categoryId, $question, $response);
                    $results['imported']++;
                } catch (\Throwable $e) {
                    $results['failed']++;
                    $results['errors'][] = "Row {$rowIndex}: " . $e->getMessage();
                }
            }

            break; // only process the first sheet
        }

        $reader->close();

        return $results;
    }

    private function processRow(int $categoryId, string $question, string $response): void
    {
        $intentKey = $this->generateUniqueKey($question);

        $intent = Intent::create([
            'category_id' => $categoryId,
            'intent_key'  => $intentKey,
            'title'       => $question,
            'response'    => $response,
            'priority'    => 1,
            'is_active'   => true,
        ]);

        // Enrich with AI-generated phrases and keywords
        try {
            $aiData = $this->gemini->generateIntentData($question, $response);

            foreach ($aiData['phrases'] ?? [] as $phrase) {
                if (is_string($phrase) && !empty(trim($phrase))) {
                    $intent->phrases()->create(['phrase' => trim($phrase)]);
                }
            }

            foreach ($aiData['keywords'] ?? [] as $kw) {
                if (isset($kw['keyword']) && !empty(trim((string) $kw['keyword']))) {
                    $intent->keywords()->create([
                        'keyword' => trim((string) $kw['keyword']),
                        'weight'  => (int) ($kw['weight'] ?? 1),
                    ]);
                }
            }
        } catch (\Throwable) {
            // Intent was created — AI enrichment failed silently.
            // Phrases/keywords can be added manually via the admin.
        }
    }

    private function generateUniqueKey(string $question): string
    {
        $base    = Str::slug(Str::limit($question, 60, ''));
        $key     = $base;
        $counter = 1;

        while (Intent::where('intent_key', $key)->exists()) {
            $key = $base . '-' . $counter++;
        }

        return $key;
    }
}
