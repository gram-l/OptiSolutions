<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Support\AnalysisLimits;
use Illuminate\Support\Facades\Log;

class SentimentAnalysisService
{
    private function json(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function post(string $path, array $payload)
    {
        $json = $this->json($payload);
        if (strlen($json) > AnalysisLimits::MAX_REQUEST_BYTES) throw new \LengthException('Analysis request exceeds the byte limit.');
        return Http::timeout(5)->withBody($json, 'application/json')->post(rtrim(config('services.django.url'), '/').$path);
    }

    public function diagnose(array $items): ?array
    {
        try {
            // Validate every record before sending any batch. Never truncate text
            // or return a partial diagnosis that could make counts misleading.
            $batches = $batch = $identities = [];
            $bytes = strlen('{"items":[]}');
            foreach ($items as $item) {
                if (!is_array($item) || !isset($item['id'], $item['text'], $item['source'])
                    || (!is_int($item['id']) && !is_string($item['id'])) || trim((string) $item['id']) === ''
                    || mb_strlen((string) $item['id']) > AnalysisLimits::MAX_ID_LENGTH
                    || !is_string($item['text']) || mb_strlen($item['text']) > AnalysisLimits::MAX_TEXT_LENGTH
                    || !in_array($item['source'], ['feedback', 'complaint'], true)) {
                    throw new \InvalidArgumentException('An analysis record is invalid or exceeds the text limit.');
                }
                $identity = $item['source'].':'.(string) $item['id'];
                if (isset($identities[$identity])) throw new \InvalidArgumentException('Duplicate analysis record.');
                $identities[$identity] = true;
                $item = ['id' => $item['id'], 'text' => $item['text'], 'source' => $item['source']];
                $size = strlen($this->json($item));
                if ($size + strlen('{"items":[]}') > AnalysisLimits::MAX_REQUEST_BYTES) throw new \LengthException('Analysis record exceeds the byte limit.');
                if ($batch && (count($batch) >= AnalysisLimits::MAX_BATCH_ITEMS || $bytes + 1 + $size > AnalysisLimits::MAX_REQUEST_BYTES)) {
                    $batches[] = $batch; $batch = []; $bytes = strlen('{"items":[]}');
                }
                $bytes += $size + ($batch ? 1 : 0);
                $batch[] = $item;
            }
            if ($batch) $batches[] = $batch;
            $result = ['feedback_category_counts' => [], 'complaint_category_counts' => [], 'details' => []];
            foreach ($batches as $batch) {
                $response = $this->post('/api/diagnose/', ['items' => $batch]);
                $details = $response->json('details');
                if (!$response->successful() || !is_array($details) || count($details) !== count($batch)) {
                    throw new \RuntimeException('Analysis returned an incomplete batch.');
                }
                $expected = [];
                foreach ($batch as $item) $expected[$item['source'].':'.$item['id']] = true;
                foreach ($details as $detail) {
                    if (!is_array($detail) || !isset($detail['source'], $detail['id']) || !is_array($detail['matched_categories'] ?? null)
                        || (!is_string($detail['id']) && !is_int($detail['id'])) || !is_string($detail['source'])) {
                        throw new \RuntimeException('Analysis returned an invalid detail.');
                    }
                    $key = $detail['source'].':'.$detail['id'];
                    if (!isset($expected[$key])) throw new \RuntimeException('Analysis returned duplicate or unexpected details.');
                    unset($expected[$key]);
                    $result['details'][] = $detail;
                    // Derive counts from complete record-level results, counting
                    // a category once per record even when several phrases match.
                    $field = $detail['source'].'_category_counts';
                    foreach (array_unique($detail['matched_categories']) as $category) {
                        if (!is_string($category)) throw new \RuntimeException('Analysis returned an invalid category.');
                        $result[$field][$category] = ($result[$field][$category] ?? 0) + 1;
                    }
                }
                foreach (['feedback_category_counts', 'complaint_category_counts'] as $field) {
                    foreach (array_keys((array) $response->json($field, [])) as $category) $result[$field][$category] ??= 0;
                }
            }
            arsort($result['feedback_category_counts']);
            arsort($result['complaint_category_counts']);
            return $result;
        } catch (\Throwable $exception) {
            Log::warning('Issue diagnosis API unavailable', ['error' => $exception->getMessage()]);
            return null;
        }
    }

    public function analyze(string $text): ?array
    {
        if (trim($text) === '' || mb_strlen($text) > AnalysisLimits::MAX_TEXT_LENGTH) return null;
        try {
            $response = $this->post('/api/predict/', ['feedback_text' => $text]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Sentiment API non-success', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::error('Sentiment API call failed: ' . $e->getMessage());
        }

        return null; // caller decides how to handle a failed analysis
    }

    public function categorize(string $text): ?string
    {
        if (trim($text) === '' || mb_strlen($text) > AnalysisLimits::MAX_TEXT_LENGTH) return null;
        try {
            $response = $this->post('/api/categorize/', ['complaint_text' => $text]);

            if ($response->successful()) {
                return $response->json()['category'] ?? null;
            }

            Log::warning('Categorize API non-success', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::error('Categorize API call failed: ' . $e->getMessage());
        }

        return null;
    }
}
