<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->getDashboardData();
        $data['diagnostic'] = app(\App\Services\DiagnosticAnalyticsService::class)->dashboard($request);

        return view('admin_acc.dashboard', $data);
    }

    public function data(Request $request)
    {
        return response()->json($this->getDashboardData());
    }

    public function diagnostics(Request $request)
    {
        $diagnostic = app(\App\Services\DiagnosticAnalyticsService::class)->dashboard($request);
        return response()->json([
            'html' => view('admin_acc.partials.diagnostic_results', compact('diagnostic'))->render(),
        ]);
    }

    public function getDashboardData(): array
    {
        // ---- Stat cards ----
        $pendingInquiries = DB::table('inquiries')->whereNull('inquiry_reply')
            ->orWhere('inquiry_reply', '')->count();

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

        return array_merge([
            'pendingInquiries' => $pendingInquiries,
            'todaysAppointments' => $todaysAppointments,
            'pendingApproval' => $pendingApproval,
            'activePatients' => $activePatients,
            'newPatientsThisMonth' => $newPatientsThisMonth,
            'avgRating' => $avgRating ? round($avgRating, 1) : 0,
        ], app(\App\Services\DashboardChartService::class)->data());
    }
}
