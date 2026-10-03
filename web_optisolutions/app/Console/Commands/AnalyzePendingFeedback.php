<?php

namespace App\Console\Commands;

use App\Models\admin_models\Feedback;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyzePendingFeedback extends Command
{
    protected $signature = 'feedback:analyze';
    protected $description = 'Analyze pending feedback comments; leave blank reviews unclassified';

    public function handle(): int
    {
        $analyzed = 0;
        $skipped = 0;
        $failed = 0;
        $url = rtrim(config('services.django.url'), '/') . '/api/predict/';

        foreach (Feedback::whereDoesntHave('sentimentResult')->lazyById(100, 'feedback_id') as $feedback) {
            $text = trim((string) $feedback->feedback_text);
            if ($text === '') {
                $skipped++;
                continue;
            }

            $this->line("Analyzing feedback #{$feedback->feedback_id}...");
            try {
                $response = Http::timeout(30)->post($url, ['feedback_text' => $text]);
                if (! $response->successful()) {
                    throw new \RuntimeException("Sentiment API returned HTTP {$response->status()}");
                }

                $result = $response->json();
                $label = $result['sentiment_label'] ?? null;
                $confidence = $result['confidence_score'] ?? null;
                if (! in_array($label, ['Positive', 'Neutral', 'Negative'], true)
                    || ! is_numeric($confidence)
                    || ! is_finite((float) $confidence)
                    || $confidence < 0 || $confidence > 1) {
                    throw new \RuntimeException('Sentiment API returned an invalid label or confidence');
                }

                $feedback->sentimentResult()->firstOrCreate(
                    ['feedback_id' => $feedback->feedback_id],
                    [
                        'sentiment_label' => $label,
                        'confidence_score' => (float) $confidence,
                        'analyzed_at' => now(),
                    ]
                );
                $analyzed++;
                $this->info("  {$label} ({$confidence})");
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Pending feedback analysis failed', [
                    'feedback_id' => $feedback->feedback_id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("  Left pending: {$e->getMessage()}");
            }
        }

        $this->info("Analyzed: {$analyzed}; blank reviews skipped: {$skipped}; still pending: {$failed}.");
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
