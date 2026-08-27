<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\Staff\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $data = $this->getDashboardData();

        return view('admin_acc.dashboard', $data);
    }

    public function data(Request $request)
    {
        return response()->json($this->getDashboardData());
    }

    public function getDashboardData(): array
    {
        // ---- Stat cards ----
        $totalInquiries      = DB::table('inquiries')->count();

        $todaysAppointments  = DB::table('schedule_visit')
            ->whereDate('visit_date', Carbon::today())
            ->count();

        // NOTE: schedule_visit has no status column in the current schema,
        // so "pending approval" can't be computed yet. Set to 0 for now.
        // If you want this feature, add a status column to schedule_visit
        // (e.g. via migration: $table->string('status')->default('pending');)
        $pendingApproval     = 0;

        $activePatients      = DB::table('patients')->count();

        // NOTE: patients has no created_at column in the current schema,
        // so "new patients this month" can't be computed yet. Set to 0 for now.
        // If you want this feature, add a created_at timestamp column to patients.
        $newPatientsThisMonth = 0;

        $avgRating           = DB::table('feedback')->avg('star_rating');

        // ---- 1) Service Distribution ----
        $serviceDistribution = DB::table('schedule_visit')
            ->select('service_type', DB::raw('COUNT(*) as total'))
            ->groupBy('service_type')
            ->orderByDesc('total')
            ->get();

        // ---- 2) Weekly Patient Visits (last 6 weeks) ----
        $weekCount = 6;
        $weeklyVisits = array_fill(0, $weekCount, 0);
        $weekLabels = [];
        $startOfThisWeek = Carbon::now()->startOfWeek();

        for ($i = $weekCount - 1; $i >= 0; $i--) {
            $weekStart = $startOfThisWeek->copy()->subWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();
            $index = $weekCount - 1 - $i;

            $weeklyVisits[$index] = DB::table('schedule_visit')
                ->whereBetween('visit_date', [$weekStart, $weekEnd])
                ->count();

            $weekLabels[] = $i === 0 ? 'This wk' : 'W-' . $i;
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
        $weekStartForInquiries = Carbon::now()->startOfWeek();
        $weekEndForInquiries   = Carbon::now()->endOfWeek();

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

        return [
            'totalInquiries'       => $totalInquiries,
            'todaysAppointments'   => $todaysAppointments,
            'pendingApproval'      => $pendingApproval,
            'activePatients'       => $activePatients,
            'newPatientsThisMonth' => $newPatientsThisMonth,
            'avgRating'            => $avgRating ? round($avgRating, 1) : 0,

            'serviceDistribution'  => $serviceDistribution,
            'weeklyVisits'         => $weeklyVisits,
            'weekLabels'           => $weekLabels,

            'positivePercent'      => $positivePercent,
            'neutralPercent'       => $neutralPercent,
            'negativePercent'      => $negativePercent,

            'inquiryVolumeByDay'   => $inquiryVolumeByDay,
        ];
    }
}