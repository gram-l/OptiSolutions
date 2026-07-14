<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $data = $this->buildDashboardData();

        return view('admin_acc.dashboard', $data);
    }

    public function data(Request $request)
    {
        return response()->json($this->buildDashboardData());
    }

    private function buildDashboardData(): array
    {
        // ---- Stat cards ----
        $totalInquiries      = DB::table('inquiries')->count();

        $todaysAppointments  = DB::table('schedule_visit')
            ->whereDate('visit_date', Carbon::today())
            ->count();

        // NOTE: schedule_visit has no `status` column in the current schema,
        // so "pending approval" can't be computed yet. Set to 0 for now.
        // If you want this feature, add a `status` column to schedule_visit
        // (e.g. via migration: $table->string('status')->default('pending');)
        $pendingApproval     = 0;

        $activePatients      = DB::table('patients')->count();

        // NOTE: patients has no `created_at` column in the current schema,
        // so "new patients this month" can't be computed yet. Set to 0 for now.
        // If you want this feature, add a `created_at` timestamp column to patients.
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
        $positive = DB::table('feedback')->where('star_rating', '>=', 4)->count();
        $neutral  = DB::table('feedback')->where('star_rating', '=', 3)->count();
        $negative = DB::table('feedback')->where('star_rating', '>', 0)->where('star_rating', '<', 3)->count();
        $sentimentTotal = $positive + $neutral + $negative;

        $positivePercent = $sentimentTotal > 0 ? round($positive / $sentimentTotal * 100) : 0;
        $neutralPercent  = $sentimentTotal > 0 ? round($neutral / $sentimentTotal * 100) : 0;
        $negativePercent = $sentimentTotal > 0 ? round($negative / $sentimentTotal * 100) : 0;

        // ---- 4) Inquiry Volume per weekday (this week) ----
        // NOTE: inquiries has no `created_at` column in the current schema
        // (only `replied_at`, which is null until a staff member replies).
        // Using `replied_at` here means unresolved/unreplied inquiries won't
        // be counted. If you want accurate "volume received per day", add a
        // `created_at` timestamp column to the inquiries table.
        $inquiryVolumeByDay = array_fill(0, 7, 0); // Mon..Sun
        $inquiries = DB::table('inquiries')
            ->whereNotNull('replied_at')
            ->whereBetween('replied_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->get(['replied_at']);

        foreach ($inquiries as $inquiry) {
            $dayIndex = Carbon::parse($inquiry->replied_at)->dayOfWeekIso - 1; // Mon=0..Sun=6
            $inquiryVolumeByDay[$dayIndex]++;
        }

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