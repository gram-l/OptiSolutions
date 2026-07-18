<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\ScheduleVisit;
use App\Models\Staff\Doctor;
use App\Models\Staff\Patient;
use App\Models\Staff\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class StaffController extends Controller
{
    public function dashboard()
    {
        return view('staff.dashboard', $this->buildDashboardData());
    }

    public function data()
    {
        return response()->json($this->buildDashboardData());
    }

    private function buildDashboardData(): array
    {
        $totalScheduleVisit = ScheduleVisit::count();
        $pendingInquiries   = Inquiry::where('resolved_status', 'Pending')->count();
        $activeDoctors      = Doctor::where('available', 1)->count();
        $totalPatients      = Patient::count();

        $serviceDistribution = ScheduleVisit::selectRaw('service_type, COUNT(*) as total')
            ->groupBy('service_type')
            ->get();

        $weekLabels = [];
        $weeklyVisits = [];
        $startOfThisWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);

        for ($i = 5; $i >= 0; $i--) {
            $weekStart = $startOfThisWeek->copy()->subWeeks($i);
            $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

            $weekLabels[] = $i === 0 ? 'This wk' : 'W-' . $i;

            $weeklyVisits[] = ScheduleVisit::whereBetween('visit_date', [
                $weekStart->toDateString(),
                $weekEnd->toDateString(),
            ])->count();
        }

        $positivePercent = 0;
        $neutralPercent  = 0;
        $negativePercent = 0;

        if (Schema::hasTable('feedback') && Schema::hasColumn('feedback', 'star_rating')) {
            $feedbackCounts = DB::table('feedback')->selectRaw('
                    SUM(CASE WHEN star_rating >= 4 THEN 1 ELSE 0 END) as positive,
                    SUM(CASE WHEN star_rating = 3 THEN 1 ELSE 0 END) as neutral,
                    SUM(CASE WHEN star_rating > 0 AND star_rating < 3 THEN 1 ELSE 0 END) as negative
                ')->first();

            $sentimentTotal = ($feedbackCounts->positive ?? 0)
                + ($feedbackCounts->neutral ?? 0)
                + ($feedbackCounts->negative ?? 0);

            if ($sentimentTotal > 0) {
                $positivePercent = round($feedbackCounts->positive / $sentimentTotal * 100);
                $neutralPercent  = round($feedbackCounts->neutral  / $sentimentTotal * 100);
                $negativePercent = round($feedbackCounts->negative / $sentimentTotal * 100);
            }
        }

        $inquiryVolumeByDay = array_fill(0, 7, 0);

        $thisWeekInquiries = DB::table('inquiries')
            ->join('chatbot_logs', 'inquiries.log_id', '=', 'chatbot_logs.log_id')
            ->whereBetween('chatbot_logs.chat_time', [
                $startOfThisWeek->toDateTimeString(),
                $startOfThisWeek->copy()->endOfWeek(Carbon::SUNDAY)->toDateTimeString(),
            ])
            ->pluck('chatbot_logs.chat_time');

        foreach ($thisWeekInquiries as $chatTime) {
            $dayIndex = Carbon::parse($chatTime)->dayOfWeekIso - 1;
            $inquiryVolumeByDay[$dayIndex]++;
        }

        return [
            'totalScheduleVisit'  => $totalScheduleVisit,
            'pendingInquiries'    => $pendingInquiries,
            'activeDoctors'       => $activeDoctors,
            'totalPatients'       => $totalPatients,
            'serviceDistribution' => $serviceDistribution,
            'weekLabels'          => $weekLabels,
            'weeklyVisits'        => $weeklyVisits,
            'positivePercent'     => $positivePercent,
            'neutralPercent'      => $neutralPercent,
            'negativePercent'     => $negativePercent,
            'inquiryVolumeByDay'  => $inquiryVolumeByDay,
            'recentActivities'    => $this->buildRecentActivities(),
        ];
    }

    /**
     * Pulls the most recent record from patients, schedule visits, and
     * inquiries (via chatbot_logs for the timestamp), merges them, and
     * returns the 5 most recent overall — newest first.
     *
     * NOTE: adjust the column names below (full_name, doctor_name, service,
     * message, etc.) if your actual table columns are named differently.
     */
    private function buildRecentActivities(int $limit = 5): array
    {
        $items = collect();

        // ---- Recently registered patients ----
        $patientTable = (new Patient())->getTable();
        $patientTsCol = Schema::hasColumn($patientTable, 'created_at') ? 'created_at' : null;

        Patient::query()
            ->when($patientTsCol, fn ($q) => $q->orderByDesc($patientTsCol))
            ->limit($limit)
            ->get()
            ->each(function ($p) use (&$items, $patientTsCol) {
                $items->push([
                    'icon'        => 'bi-people',
                    'color'       => '#8e44ad',
                    'title'       => 'New patient registered',
                    'description' => $p->full_name ?? ('Patient #' . ($p->patient_id ?? $p->id)),
                    'time'        => $patientTsCol ? $p->{$patientTsCol} : now(),
                ]);
            });

        // ---- Recently added schedule visits ----
        $visitTable = (new ScheduleVisit())->getTable();
        $visitTsCol = Schema::hasColumn($visitTable, 'created_at')
            ? 'created_at'
            : (Schema::hasColumn($visitTable, 'visit_date') ? 'visit_date' : null);

        ScheduleVisit::query()
            ->when($visitTsCol, fn ($q) => $q->orderByDesc($visitTsCol))
            ->limit($limit)
            ->get()
            ->each(function ($v) use (&$items, $visitTsCol) {
                $desc = trim(($v->doctor_name ?? '') . ' — ' . ($v->service ?? $v->service_type ?? ''), ' —');
                $items->push([
                    'icon'        => 'bi-calendar-check',
                    'color'       => '#2980b9',
                    'title'       => 'New schedule visit',
                    'description' => $desc !== '' ? $desc : 'Visit #' . ($v->visit_id ?? $v->id),
                    'time'        => $visitTsCol ? $v->{$visitTsCol} : now(),
                ]);
            });

        // ---- Recent chatbot inquiries (timestamp lives in chatbot_logs) ----
        if (Schema::hasTable('inquiries') && Schema::hasTable('chatbot_logs')) {
            $hasMessageCol = Schema::hasColumn('chatbot_logs', 'message');

            DB::table('inquiries')
                ->join('chatbot_logs', 'inquiries.log_id', '=', 'chatbot_logs.log_id')
                ->orderByDesc('chatbot_logs.chat_time')
                ->limit($limit)
                ->select('inquiries.*', 'chatbot_logs.chat_time', DB::raw($hasMessageCol ? 'chatbot_logs.message as inquiry_message' : 'NULL as inquiry_message'))
                ->get()
                ->each(function ($inq) use (&$items) {
                    $items->push([
                        'icon'        => 'bi-chat-dots',
                        'color'       => '#e67e22',
                        'title'       => 'New chatbot inquiry',
                        'description' => $inq->inquiry_message ?: ('Inquiry #' . ($inq->inquiry_id ?? '')),
                        'time'        => $inq->chat_time,
                    ]);
                });
        }

        return $items
            ->filter(fn ($item) => !empty($item['time']))
            ->sortByDesc(fn ($item) => Carbon::parse($item['time']))
            ->take($limit)
            ->map(function ($item) {
                $item['time_human'] = Carbon::parse($item['time'])->diffForHumans();
                return $item;
            })
            ->values()
            ->toArray();
    }
}