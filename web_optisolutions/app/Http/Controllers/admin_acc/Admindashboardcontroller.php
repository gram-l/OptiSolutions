<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// TODO: swap these for your real models/tables
// use App\Models\Inquiry;
// use App\Models\ScheduleVisit;
// use App\Models\Doctor;
// use App\Models\Patient;
// use App\Models\Feedback;

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
        $totalInquiries      = DB::table('inquiries')->count(); // TODO: adjust table name
        $todaysAppointments  = DB::table('schedule_visit')
            ->whereDate('visit_date', Carbon::today()) // TODO: adjust column name
            ->count();
        $pendingApproval     = DB::table('schedule_visit')
            ->whereDate('visit_date', Carbon::today())
            ->where('status', 'pending') // TODO: adjust column/value
            ->count();
        $activePatients      = DB::table('patients')->count(); // TODO: adjust table name
        $newPatientsThisMonth = DB::table('patients')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        $avgRating           = DB::table('feedback')->avg('star_rating'); // TODO: adjust table/col

        // ---- 1) Service Distribution ----
        $serviceDistribution = DB::table('schedule_visit') // TODO: adjust table
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

            $weeklyVisits[$index] = DB::table('schedule_visit') // TODO: adjust table/col
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
        $inquiryVolumeByDay = array_fill(0, 7, 0); // Mon..Sun
        $inquiries = DB::table('inquiries') // TODO: adjust table
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->get(['created_at']);

        foreach ($inquiries as $inquiry) {
            $dayIndex = Carbon::parse($inquiry->created_at)->dayOfWeekIso - 1; // Mon=0..Sun=6
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