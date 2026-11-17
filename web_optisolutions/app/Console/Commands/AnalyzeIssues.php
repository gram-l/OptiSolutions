<?php

namespace App\Console\Commands;

use App\Services\IssueAnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeIssues extends Command
{
    protected $signature = 'issues:analyze';
    protected $description = 'Save missing issue categories and reported reasons for reviews and complaints';

    public function handle(IssueAnalysisService $service): int
    {
        $reviews = DB::table('feedback as f')
            ->leftJoin('sentiment_results as s', 's.feedback_id', '=', 'f.feedback_id')
            ->whereNull('f.issue_analysis')
            ->whereNotNull('f.feedback_text')->whereRaw("TRIM(f.feedback_text) <> ''")
            ->where(fn ($query) => $query->where('s.sentiment_label', 'Negative')->orWhereBetween('f.star_rating', [1, 3]))
            ->select('f.feedback_id as id', 'f.feedback_text as text');
        $complaints = DB::table('complaints')->whereNull('issue_analysis')
            ->select('complaint_id as id', 'complaint_text as text');
        $saved = $pending = 0;
        foreach (['feedback' => [$reviews, 'f.feedback_id'], 'complaint' => [$complaints, 'complaint_id']] as $source => [$query, $id]) {
            $query->chunkById(100, function ($rows) use ($service, $source, &$saved, &$pending) {
                $result = $service->resolve($rows->map(fn ($row) => [
                    'id' => $row->id, 'text' => $row->text, 'source' => $source,
                ]));
                $saved += $result['details']->count();
                $pending += $result['pending'];
            }, $id, 'id');
        }

        $this->info("Issue results available: {$saved}. Still pending: {$pending}.");
        return $pending ? self::FAILURE : self::SUCCESS;
    }
}
