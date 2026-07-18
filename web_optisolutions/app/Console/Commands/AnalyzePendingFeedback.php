<?php

namespace App\Console\Commands;

use App\Models\admin_models\Feedback;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AnalyzePendingFeedback extends Command
{
    protected $signature = 'feedback:analyze';
    protected $description = 'Run sentiment analysis on feedback rows that don\'t have a sentiment_results entry yet';

    public function handle()
    {
        $pending = Feedback::whereDoesntHave('sentimentResult')->get();

        if ($pending->isEmpty()) {
            $this->info('No pending feedback to analyze.');
            return;
        }

        $this->info("Found {$pending->count()} pending feedback entries.");

        foreach ($pending as $feedback) {
            $this->line("Analyzing feedback_id {$feedback->feedback_id}...");

            try {
                $response = Http::post('http://127.0.0.1:8001/api/predict/', [
                    'feedback_text' => $feedback->feedback_text,
                ]);

                if ($response->successful()) {
                    $result = $response->json();

                    $feedback->sentimentResult()->create([
                        'sentiment_label' => $result['sentiment_label'],
                        'confidence_score' => $result['confidence_score'],
                        'analyzed_at' => now(),
                    ]);

                    $this->info("  → {$result['sentiment_label']} ({$result['confidence_score']})");
                } else {
                    $this->error("  Django returned status {$response->status()}");
                }
            } catch (\Exception $e) {
                $this->error("  Failed: {$e->getMessage()}");
            }
        }

        $this->info('Done.');
    }
}