<?php

namespace App\Services;

use App\Support\AnalysisLimits;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IssueAnalysisService
{
    private const SOURCES = [
        'feedback' => ['feedback', 'feedback_id', 'feedback_text'],
        'complaint' => ['complaints', 'complaint_id', 'complaint_text'],
    ];

    public function __construct(private SentimentAnalysisService $sentiment)
    {
    }

    public function capture(string $source, int $id, string $text): void
    {
        if (trim($text) === '') {
            return;
        }

        try {
            $this->resolve(collect([compact('source', 'id', 'text')]));
        } catch (\Throwable $exception) {
            Log::warning('Could not store issue analysis', ['source' => $source, 'id' => $id, 'error' => $exception->getMessage()]);
        }
    }

    public function resolve(Collection $items): array
    {
        $items = $items->keyBy(fn ($item) => $item['source'].':'.$item['id']);
        $saved = collect();
        foreach ($items->groupBy('source', preserveKeys: true) as $source => $records) {
            [$table, $id] = self::SOURCES[$source];
            foreach ($records->chunk(500) as $chunk) {
                $stored = DB::table($table)->whereIn($id, $chunk->pluck('id'))->pluck('issue_analysis', $id);
                foreach ($chunk as $key => $item) {
                    $analysis = json_decode($stored->get($item['id']) ?? 'null', true);
                    if (($analysis['text_hash'] ?? null) === hash('sha256', $item['text'])
                        && is_array($analysis['matched_categories'] ?? null) && is_array($analysis['findings'] ?? null)) {
                        $saved->put($key, $this->detail($item, $analysis));
                    }
                }
            }
        }

        $pending = $items->diffKeys($saved);
        $eligible = $pending->filter(fn ($item) => mb_strlen($item['text']) <= AnalysisLimits::MAX_TEXT_LENGTH);
        if ($eligible->isNotEmpty()) {
            $result = $this->sentiment->diagnose($eligible->values()->all());
            foreach ($result['details'] ?? [] as $detail) {
                $key = $detail['source'].':'.$detail['id'];
                $item = $eligible->get($key);
                $detail = $this->detail($item, $detail);
                [$table, $id, $text] = self::SOURCES[$item['source']];
                $analysis = [
                    'text_hash' => hash('sha256', $item['text']),
                    'matched_categories' => $detail['matched_categories'],
                    'findings' => $detail['findings'],
                ];
                $updates = ['issue_analysis' => json_encode($analysis, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)];
                if ($item['source'] === 'complaint') {
                    $updates['category'] = $detail['matched_categories'][0] ?? 'other';
                }
                DB::table($table)->where($id, $item['id'])->where($text, $item['text'])->update($updates);
                $saved->put($key, $detail);
            }
        }

        return ['details' => $saved, 'pending' => $items->diffKeys($saved)->count()];
    }

    private function detail(array $item, array $analysis): array
    {
        return [
            'id' => $item['id'],
            'source' => $item['source'],
            'matched_categories' => array_values(array_unique($analysis['matched_categories'])),
            'findings' => is_array($analysis['findings'] ?? null) ? $analysis['findings'] : [],
        ];
    }
}
