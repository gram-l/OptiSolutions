<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DiagnosticAnalyticsService
{
    public function dashboard(Request $request): array
    {
        $thisWeek = CarbonImmutable::now('Asia/Manila')->startOfWeek(CarbonImmutable::MONDAY);
        $latest = $thisWeek->subWeek();
        $doctors = DB::table('doctors')->orderBy('doctor_name')->get(['doctor_id', 'doctor_name', 'specialty']);
        $specialties = $doctors->pluck('specialty')->filter(fn ($value) => trim($value ?? '') !== '')
            ->unique()->sort()->values();
        $filters = $request->validate([
            'diagnostic_week' => ['bail', 'nullable', 'date_format:Y-m-d', 'after_or_equal:1970-01-05', 'before:' . $thisWeek->format('Y-m-d'),
                function ($attribute, $value, $fail) {
                    if (!CarbonImmutable::parse($value, 'Asia/Manila')->isMonday()) {
                        $fail('Select a Monday to compare a completed Monday–Sunday week.');
                    }
                }],
            'diagnostic_doctor' => ['nullable', 'integer', 'min:1', Rule::in($doctors->pluck('doctor_id')->all())],
            'diagnostic_specialty' => ['nullable', 'string', 'max:255', Rule::in($specialties->all())],
        ]);
        $selectedDoctor = $doctors->firstWhere('doctor_id', $filters['diagnostic_doctor'] ?? null);
        if ($selectedDoctor && !empty($filters['diagnostic_specialty'])
            && $selectedDoctor->specialty !== $filters['diagnostic_specialty']) {
            throw ValidationException::withMessages([
                'diagnostic_specialty' => 'Select the specialty registered for this doctor.',
            ]);
        }
        $currentStart = isset($filters['diagnostic_week'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['diagnostic_week'], 'Asia/Manila') : $latest;
        $previousStart = $currentStart->subWeek();
        $end = $currentStart->addWeek();

        $all = DB::table('feedback as f')
            ->leftJoin('sentiment_results as sr', 'sr.feedback_id', '=', 'f.feedback_id')
            ->leftJoin('schedule_visit as v', 'v.visit_id', '=', 'f.visit_id')
            ->leftJoin('doctors as d', 'd.doctor_id', '=', 'v.doctor_id')
            ->where('f.submitted_at', '>=', $previousStart->format('Y-m-d H:i:s'))
            ->where('f.submitted_at', '<', $end->format('Y-m-d H:i:s'))
            ->orderBy('f.submitted_at')
            ->get(['f.feedback_id', 'f.feedback_text', 'f.submitted_at', 'sr.sentiment_label',
                'v.doctor_id', 'd.specialty as doctor_specialty', 'd.doctor_name']);
        $unlinked = $all->filter(fn ($row) => !$row->doctor_id || !trim($row->doctor_specialty ?? ''))->count();
        $rows = $all->filter(fn ($row) =>
            (empty($filters['diagnostic_doctor']) || (string) $row->doctor_id === (string) $filters['diagnostic_doctor'])
            && (empty($filters['diagnostic_specialty']) || $row->doctor_specialty === $filters['diagnostic_specialty']));
        $negative = $rows->filter(fn ($row) => trim($row->feedback_text ?? '') !== ''
            && strtolower($row->sentiment_label ?? '') === 'negative');
        $items = $negative->map(fn ($row) => [
            'id' => $row->feedback_id, 'text' => $row->feedback_text, 'source' => 'feedback',
        ])->values()->all();
        $diagnosis = $items ? app(SentimentAnalysisService::class)->diagnose($items) : ['details' => []];
        $details = collect($diagnosis['details'] ?? [])->where('source', 'feedback')->keyBy('id');
        // A partial response must not silently turn missing analysis into "no concerns".
        $available = $diagnosis !== null && $negative->every(fn ($row) =>
            is_array($details->get($row->feedback_id)['matched_categories'] ?? null));
        $periods = [];
        foreach (['previous' => $previousStart, 'current' => $currentStart] as $key => $start) {
            $periodEnd = $start->addWeek()->format('Y-m-d H:i:s');
            $periodRows = $rows->filter(fn ($row) => $row->submitted_at >= $start->format('Y-m-d H:i:s')
                && $row->submitted_at < $periodEnd);
            $written = $periodRows->filter(fn ($row) => trim($row->feedback_text ?? '') !== '');
            $classified = $written->filter(fn ($row) => in_array(strtolower($row->sentiment_label ?? ''), ['positive', 'neutral', 'negative'], true));
            $negatives = $classified->filter(fn ($row) => strtolower($row->sentiment_label) === 'negative');
            $categoryCounts = [];
            $comments = [];
            foreach ($negatives as $row) {
                $detail = $details->get($row->feedback_id);
                $categories = array_values(array_unique($detail['matched_categories'] ?? []));
                foreach ($categories as $category) {
                    $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
                }
                $comments[] = [
                    'id' => $row->feedback_id, 'text' => $row->feedback_text, 'date' => $row->submitted_at,
                    'doctor' => $row->doctor_name, 'specialty' => $row->doctor_specialty,
                    'categories' => $categories, 'findings' => $detail['findings'] ?? [],
                ];
            }
            arsort($categoryCounts);
            $periods[$key] = [
                'label' => $start->format('M j, Y') . ' – ' . $start->addDays(6)->format('M j, Y'),
                'classified' => $classified->count(),
                'negative' => $negatives->count(),
                'negative_rate' => $classified->count() ? round($negatives->count() / $classified->count() * 100, 1) : null,
                'pending' => $written->count() - $classified->count(),
                'rating_only' => $periodRows->count() - $written->count(),
                'unmatched' => $available ? collect($comments)->filter(fn ($comment) => !$comment['categories'])->count() : null,
                'categories' => $categoryCounts, 'comments' => $comments,
            ];
        }
        $categories = array_values(array_unique(array_merge(array_keys($periods['current']['categories']), array_keys($periods['previous']['categories']))));
        usort($categories, fn ($a, $b) =>
            (($periods['current']['categories'][$b] ?? 0) <=> ($periods['current']['categories'][$a] ?? 0)) ?: strcmp($a, $b));

        return [
            'week' => $currentStart->format('Y-m-d'), 'latest_week' => $latest->format('Y-m-d'),
            'doctor' => $filters['diagnostic_doctor'] ?? '', 'specialty' => $filters['diagnostic_specialty'] ?? '',
            'doctors' => $doctors, 'specialties' => $specialties,
            'periods' => $periods, 'categories' => $available ? $categories : [],
            'available' => $available, 'unlinked' => $unlinked,
        ];
    }
}
