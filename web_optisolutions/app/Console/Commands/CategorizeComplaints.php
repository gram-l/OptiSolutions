<?php

namespace App\Console\Commands;

use App\Models\admin_models\Complaint;
use App\Services\SentimentAnalysisService;
use Illuminate\Console\Command;

class CategorizeComplaints extends Command
{
    protected $signature = 'complaints:categorize';
    protected $description = 'Send uncategorized complaints to the ML service and store the returned category';

    public function handle(SentimentAnalysisService $service): int
    {
        $complaints = Complaint::where(function ($q) {
                $q->whereNull('category')
                  ->orWhere('category', '')
                  ->orWhere('category', 'Uncategorized');
            })
            ->get();

        if ($complaints->isEmpty()) {
            $this->info('No uncategorized complaints found.');
            return self::SUCCESS;
        }

        $this->info("Categorizing {$complaints->count()} complaint(s)...");
        $this->info('DB connection: ' . \DB::connection()->getDatabaseName());

        foreach ($complaints as $complaint) {
            $category = $service->categorize($complaint->complaint_text);

            if ($category) {
                $complaint->update(['category' => $category]);
                $this->line("  #{$complaint->complaint_id}: {$category}");

                // --- TEMP DEBUG ---
                $this->line('    DB now says: ' .
                    Complaint::whereKey($complaint->complaint_id)->value('category')
                );
                // --- END TEMP DEBUG ---
            } else {
                $this->warn("  #{$complaint->complaint_id}: categorization failed, left as-is");
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}