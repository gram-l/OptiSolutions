<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\ScheduleVisit;
use App\Models\Staff\Doctor;
use App\Models\Staff\Patient;
use App\Models\Staff\Inquiry;
use App\Models\Staff\AppNotification;
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

    
        $pendingInquiries = Inquiry::whereNull('inquiry_reply')
            ->orWhere('inquiry_reply', '')
            ->count();

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


    private function buildRecentActivities(int $limit = 5): array
    {
        if (!Schema::hasTable('app_notifications')) {
            return [];
        }

        return AppNotification::orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($notif) {
                [$icon, $color] = $this->iconAndColorForType($notif->type);

                return [
                    'icon'        => $icon,
                    'color'       => $color,
                    'title'       => $notif->title,
                    'description' => $notif->message,
                    'time'        => $notif->created_at,
                    'time_human'  => $notif->created_at
                        ? $notif->created_at->diffForHumans()
                        : '',
                ];
            })
            ->values()
            ->toArray();
    }

    private function iconAndColorForType(?string $type): array
    {
        return match ($type) {
            'inquiry'     => ['bi-chat-dots', '#e67e22'],
            'schedule visit' => ['bi-calendar-check', '#2980b9'],
            'patient'     => ['bi-people', '#8e44ad'],
            'doctor'      => ['bi-person-badge', '#16a085'],
            default       => ['bi-bell', '#7f8c8d'],
        };
    }
}