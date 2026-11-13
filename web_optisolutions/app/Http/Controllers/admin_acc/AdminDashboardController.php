<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $charts = app(\App\Services\DashboardChartService::class)->data(balanceSentimentPercentages: true);

        return array_merge([
            'pendingInquiries' => $pendingInquiries,
            'todaysAppointments' => $todaysAppointments,
            'pendingApproval' => $pendingApproval,
            'activePatients' => $activePatients,
            'newPatientsThisMonth' => $newPatientsThisMonth,
            'avgRating' => $avgRating ? round($avgRating, 1) : 0,
            'chartInterpretations' => $this->interpretCharts($charts),
        ], $charts);
    }

    private function interpretCharts(array $charts): array
    {
        $ratings = collect(['doctors' => $charts['leadingDoctors'], 'services' => $charts['leadingServices']])
            ->filter(fn ($leaders) => $leaders->isNotEmpty())
            ->map(function ($leaders, $group) {
                $leader = $leaders->first();
                return $leader->name . ' leads the ' . $group . ' list at ' . number_format($leader->average_rating, 1) . '/5';
            });

        $sentiment = [
            'positive' => $charts['positivePercent'],
            'neutral' => $charts['neutralPercent'],
            'negative' => $charts['negativePercent'],
        ];
        $largestShare = max($sentiment);
        $topSentiments = array_keys($sentiment, $largestShare);
        $sentimentSummary = match (true) {
            $largestShare === 0 => 'No reviews have been analyzed for sentiment yet.',
            count($topSentiments) > 1 => ucfirst(implode(' and ', $topSentiments)) . " tied for the largest share at {$largestShare}% each.",
            default => ucfirst($topSentiments[0]) . " feedback had the largest share at {$largestShare}%.",
        };

        $resolved = $charts['resolvedInquiries'];
        $inquiryTotal = $resolved + $charts['unresolvedInquiries'];
        $services = $charts['serviceDistribution'];
        $topServiceCount = $services->max('total');
        $topServices = $services->where('total', $topServiceCount);
        $serviceSummary = match (true) {
            $services->isEmpty() => 'No visits have been scheduled for any service yet.',
            $topServices->count() > 1 => $topServices->count() . " services tied for the most scheduled visits with {$topServiceCount} each.",
            default => $topServices->first()->service_type . " accounted for {$topServiceCount} of " . $services->sum('total') . ' scheduled visits.',
        };

        $weeks = $charts['weeklyVisits'];
        $ongoing = array_pop($weeks);
        [$previous, $latest] = array_slice($weeks, -2);
        $visitSummary = match (true) {
            array_sum($weeks) + $ongoing === 0 => 'No visits were scheduled in the selected weeks.',
            array_sum($weeks) === 0 => "The ongoing week has {$ongoing} scheduled " . Str::plural('visit', $ongoing) . ', the first in the selected period.',
            $latest > $previous => "Scheduled visits rose from {$previous} to {$latest} across the last two completed weeks.",
            $latest < $previous => "Scheduled visits fell from {$previous} to {$latest} across the last two completed weeks.",
            default => "The last two completed weeks each had {$latest} scheduled " . Str::plural('visit', $latest) . '.',
        };

        $daily = array_combine(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'], $charts['inquiryVolumeByDay']);
        $peak = max($daily);
        $peakDays = array_keys($daily, $peak);
        $volumeSummary = match (true) {
            $peak === 0 => 'Daily inquiry volume stayed at zero in the selected week.',
            count($peakDays) > 1 => count($peakDays) . " days tied for the most inquiries with {$peak} each.",
            default => "{$peakDays[0]} had the most inquiries with {$peak} of " . array_sum($daily) . ' for the selected week.',
        };

        return [
            'ratings' => $ratings->isEmpty() ? 'No ratings are linked to scheduled visits yet.' : $ratings->implode(' and ') . '.',
            'sentiment' => $sentimentSummary,
            'inquiry-status' => $inquiryTotal > 0 ? "The clinic has resolved {$resolved} of {$inquiryTotal} chatbot " . Str::plural('inquiry', $inquiryTotal) . '.' : 'No chatbot inquiries have been recorded yet.',
            'services' => $serviceSummary,
            'visits' => $visitSummary,
            'inquiry-volume' => $volumeSummary,
        ];
    }
}
