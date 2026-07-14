<?php

namespace App\Http\Controllers;

use App\Models\ScheduleVisit;
use App\Models\Feedback;
use App\Models\Staff\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartController extends Controller
{
    // 1. Service Distribution (doughnut)
    public function serviceDistribution()
    {
        $data = ScheduleVisit::select('service_type', DB::raw('COUNT(*) as total'))
            ->groupBy('service_type')
            ->get();

        return response()->json([
            'labels' => $data->pluck('service_type'),
            'values' => $data->pluck('total'),
        ]);
    }

    // 2. Weekly Patient Visits (line)
    public function weeklyVisits()
    {
        $data = ScheduleVisit::select(DB::raw('YEARWEEK(visit_date) as week'), DB::raw('COUNT(*) as total'))
            ->where('visit_date', '>=', now()->subWeeks(8))
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        return response()->json([
            'labels' => $data->pluck('week'),
            'values' => $data->pluck('total'),
        ]);
    }

    // 3. Sentiment Analysis (star_rating buckets)
    public function sentimentAnalysis()
    {
        $data = Feedback::select(DB::raw("
                CASE
                    WHEN star_rating <= 2 THEN 'Negative'
                    WHEN star_rating = 3 THEN 'Neutral'
                    ELSE 'Positive'
                END as sentiment
            "), DB::raw('COUNT(*) as total'))
            ->groupBy('sentiment')
            ->get();

        return response()->json([
            'labels' => $data->pluck('sentiment'),
            'values' => $data->pluck('total'),
        ]);
    }

    // 4. Inquiry Volume per week (joined with chatbot_logs)
    public function inquiryVolume()
    {
        $data = Inquiry::join('chatbot_logs', 'inquiries.log_id', '=', 'chatbot_logs.log_id')
            ->select(DB::raw('YEARWEEK(chatbot_logs.chat_time) as week'), DB::raw('COUNT(*) as total'))
            ->where('chatbot_logs.chat_time', '>=', now()->subWeeks(8))
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        return response()->json([
            'labels' => $data->pluck('week'),
            'values' => $data->pluck('total'),
        ]);
    }
}