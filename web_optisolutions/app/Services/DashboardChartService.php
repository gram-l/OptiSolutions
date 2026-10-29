<?php

namespace App\Services;

use App\Models\Staff\Inquiry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardChartService
{
    public function data(): array
    {
        $filters = request()->validate([
            'inquiry_period' => ['nullable', 'in:this_week,last_week'],
            'visit_weeks' => ['nullable', 'integer', 'in:4,6,12'],
        ]);
        $inquiryPeriod = $filters['inquiry_period'] ?? 'this_week';
        $totalInquiries = DB::table('inquiries')->count();
        // ---- 1) Service Distribution ----
        $serviceDistribution = DB::table('schedule_visit')
            ->select('service_type', DB::raw('COUNT(*) as total'))
            ->groupBy('service_type')
            ->orderByDesc('total')
            ->get();

        // ---- 2) Weekly Patient Visits (last 6 weeks) ----
        $weekCount = (int) ($filters['visit_weeks'] ?? 6);
        $weeklyVisits = array_fill(0, $weekCount, 0);
        $weekLabels = [];
        $startOfThisWeek = Carbon::now('Asia/Manila')->startOfWeek(Carbon::MONDAY);

        for ($i = $weekCount - 1; $i >= 0; $i--) {
            $weekStart = $startOfThisWeek->copy()->subWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();
            $index = $weekCount - 1 - $i;

            $weeklyVisits[$index] = DB::table('schedule_visit')
                ->whereBetween('visit_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->count();

            $weekLabels[] = $i === 0 ? 'This week (ongoing)' : $weekStart->format('M j') . ' – ' . $weekEnd->format('M j');
        }

        // ---- 3) Sentiment Analysis ----
        // Previously this derived sentiment from a star_rating threshold
        // (>=4 positive, ==3 neutral, <3 negative), which didn't match the
        // real ML-classified sentiment shown on the Feedback page
        // (sentiment_results.sentiment_label, set by SentimentAnalysisService
        // when feedback is submitted). Joining to sentiment_results here so
        // the dashboard and the Feedback page always agree on the numbers.
        // Feedback rows that haven't been analyzed yet (no sentiment_results
        // row) are simply excluded from the total, same as how the Feedback
        // page treats them ('pending').
        $sentimentCounts = DB::table('feedback')
            ->join('sentiment_results', 'sentiment_results.feedback_id', '=', 'feedback.feedback_id')
            ->select('sentiment_results.sentiment_label', DB::raw('COUNT(*) as total'))
            ->groupBy('sentiment_results.sentiment_label')
            ->pluck('total', 'sentiment_label');

        $positive = $sentimentCounts['Positive'] ?? 0;
        $neutral  = $sentimentCounts['Neutral'] ?? 0;
        $negative = $sentimentCounts['Negative'] ?? 0;
        $sentimentTotal = $positive + $neutral + $negative;

        $positivePercent = $sentimentTotal > 0 ? round($positive / $sentimentTotal * 100) : 0;
        $neutralPercent  = $sentimentTotal > 0 ? round($neutral / $sentimentTotal * 100) : 0;
        $negativePercent = $sentimentTotal > 0 ? round($negative / $sentimentTotal * 100) : 0;

        // ---- 4) Inquiry Volume per weekday (this week) ----
        // Previously this counted `replied_at`, which meant unresolved /
        // not-yet-replied inquiries never showed up in the chart. Chatbot
        // Inquiries (ChatbotInquiryController) already sources the real
        // "message received" timestamp from the inquiry's `log` relation
        // (chat_time on the chat log row), falling back to created_at.
        // We reuse that same logic here so the chart reflects *all*
        // inquiries received this week, not just the replied ones.
        $inquiryVolumeByDay = array_fill(0, 7, 0); // Mon..Sun
        $weekStartForInquiries = $startOfThisWeek->copy()->subWeeks($inquiryPeriod === 'last_week' ? 1 : 0);
        $weekEndForInquiries = $weekStartForInquiries->copy()->endOfWeek(Carbon::SUNDAY);

        Inquiry::with('log')
            ->get()
            ->each(function (Inquiry $inquiry) use (&$inquiryVolumeByDay, $weekStartForInquiries, $weekEndForInquiries) {
                $timestamp = optional($inquiry->log)->chat_time ?? $inquiry->created_at ?? null;

                if (!$timestamp) {
                    return;
                }

                $timestamp = Carbon::parse($timestamp);

                if ($timestamp->between($weekStartForInquiries, $weekEndForInquiries)) {
                    $dayIndex = $timestamp->dayOfWeekIso - 1; // Mon=0..Sun=6
                    $inquiryVolumeByDay[$dayIndex]++;
                }
            });

        // ---- 5) Sentiment Trend (weekly, last 6 weeks) ----
        // Same week-bucket pattern as Weekly Patient Visits above, but
        // tracking the Positive/Neutral/Negative % split per week instead
        // of a single count — lets the dashboard show whether sentiment is
        // improving or declining over time instead of just a single
        // current snapshot.
        $sentimentWeekCount = 6;
        $sentimentTrendLabels = [];
        $sentimentTrendPositive = array_fill(0, $sentimentWeekCount, 0);
        $sentimentTrendNeutral  = array_fill(0, $sentimentWeekCount, 0);
        $sentimentTrendNegative = array_fill(0, $sentimentWeekCount, 0);

        for ($i = $sentimentWeekCount - 1; $i >= 0; $i--) {
            $weekStart = $startOfThisWeek->copy()->subWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();
            $index = $sentimentWeekCount - 1 - $i;

            $weekCounts = DB::table('feedback')
                ->join('sentiment_results', 'sentiment_results.feedback_id', '=', 'feedback.feedback_id')
                ->whereBetween('feedback.submitted_at', [$weekStart, $weekEnd])
                ->select('sentiment_results.sentiment_label', DB::raw('COUNT(*) as total'))
                ->groupBy('sentiment_results.sentiment_label')
                ->pluck('total', 'sentiment_label');

            $weekTotal = $weekCounts->sum();
            $sentimentTrendPositive[$index] = $weekTotal > 0 ? round(($weekCounts['Positive'] ?? 0) / $weekTotal * 100) : 0;
            $sentimentTrendNeutral[$index]  = $weekTotal > 0 ? round(($weekCounts['Neutral'] ?? 0) / $weekTotal * 100) : 0;
            $sentimentTrendNegative[$index] = $weekTotal > 0 ? round(($weekCounts['Negative'] ?? 0) / $weekTotal * 100) : 0;

            $sentimentTrendLabels[] = $i === 0 ? 'This wk' : 'W-' . $i;
        }

        // ---- 6) Chat Inquiries: Resolved vs Unresolved ----
        // Mirrors the two-bucket "In Progress" / "Resolved" model used on
        // the Chatbot Logs page — anything not literally 'Resolved' counts
        // as unresolved/in-progress here too, so the two pages always agree.
        $resolvedInquiries   = DB::table('inquiries')->where('resolved_status', 'Resolved')->count();
        $unresolvedInquiries = $totalInquiries - $resolvedInquiries;

        $ratedVisits = fn () => DB::table('feedback as f')
            ->join('schedule_visit as v', 'v.visit_id', '=', 'f.visit_id')
            ->whereBetween('f.star_rating', [1, 5]);
        $leadingDoctors = $ratedVisits()
            ->join('doctors as d', 'd.doctor_id', '=', 'v.doctor_id')
            ->whereNotNull('d.doctor_name')->where('d.doctor_name', '!=', '')
            ->select('d.doctor_id', 'd.doctor_name as name')
            ->selectRaw('AVG(f.star_rating) as average_rating, COUNT(*) as rating_count')
            ->groupBy('d.doctor_id', 'd.doctor_name')
            ->orderByDesc('average_rating')->orderByDesc('rating_count')->orderBy('d.doctor_name')
            ->limit(3)->get();
        $leadingServices = $ratedVisits()
            ->whereNotNull('v.service_type')->where('v.service_type', '!=', '')
            ->select('v.service_type as name')
            ->selectRaw('AVG(f.star_rating) as average_rating, COUNT(*) as rating_count')
            ->groupBy('v.service_type')
            ->orderByDesc('average_rating')->orderByDesc('rating_count')->orderBy('v.service_type')
            ->limit(3)->get();

        return [
            'leadingDoctors' => $leadingDoctors,
            'leadingServices' => $leadingServices,
            'inquiryPeriod' => $inquiryPeriod,
            'visitWeeks' => $weekCount,
            'inquiryPeriodLabel' => ($inquiryPeriod === 'last_week' ? 'Last week' : 'This week (ongoing)') . ': '
                . $weekStartForInquiries->format('M j, Y') . ' – ' . $weekEndForInquiries->format('M j, Y') . ' (Philippine time)',
            'visitPeriodLabel' => 'Weekly scheduled visits over the last ' . $weekCount . ' weeks, including this week (ongoing).',
            'serviceDistribution' => $serviceDistribution,
            'weeklyVisits' => $weeklyVisits, 'weekLabels' => $weekLabels,
            'positivePercent' => $positivePercent, 'neutralPercent' => $neutralPercent,
            'negativePercent' => $negativePercent, 'inquiryVolumeByDay' => $inquiryVolumeByDay,
            'sentimentTrendLabels' => $sentimentTrendLabels,
            'sentimentTrendPositive' => $sentimentTrendPositive,
            'sentimentTrendNeutral' => $sentimentTrendNeutral,
            'sentimentTrendNegative' => $sentimentTrendNegative,
            'resolvedInquiries' => $resolvedInquiries, 'unresolvedInquiries' => $unresolvedInquiries,
        ];
    }
}
