<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\DoctorSpecialties;

class DiagnosticAnalyticsService
{
    private const LOW_RATING_MAX = 3;

    public function dashboard(Request $request, bool $forReport = false): array
    {
        $thisWeek = CarbonImmutable::now('Asia/Manila')->startOfWeek(CarbonImmutable::MONDAY);
        $latest = $thisWeek->subWeek();
        $doctors = \App\Support\DoctorServices::withLabels(DB::table('doctors')->orderBy('doctor_name')->get(['doctor_id', 'doctor_name']));
        $specialties = DoctorSpecialties::catalog();
        $weekRules = [
            Rule::excludeIf($forReport && $request->filled('report_scope')),
            'bail', 'nullable', 'date_format:Y-m-d', 'after_or_equal:1970-01-05', 'before:' . $thisWeek->format('Y-m-d'),
            function ($attribute, $value, $fail) {
                if (!CarbonImmutable::parse($value, 'Asia/Manila')->isMonday()) {
                    $fail('Select a Monday to compare a completed Monday–Sunday week.');
                }
            },
        ];
        $filters = $request->validate([
            'diagnostic_week' => $weekRules,
            'diagnostic_compare_week' => $weekRules,
            'diagnostic_doctor' => ['nullable', 'integer', 'min:1', Rule::in($doctors->pluck('doctor_id')->all())],
            'diagnostic_specialty' => ['nullable', 'string', 'max:255', Rule::in($specialties->all())],
        ] + ($forReport ? [
            'report_scope' => ['nullable', Rule::in(['all', 'date'])],
            'report_date' => ['exclude_unless:report_scope,date', 'required', 'date_format:Y-m-d'],
        ] : []));
        $selectedDoctor = $doctors->firstWhere('doctor_id', $filters['diagnostic_doctor'] ?? null);
        if ($selectedDoctor && !empty($filters['diagnostic_specialty'])
            && !in_array($filters['diagnostic_specialty'], DoctorSpecialties::split($selectedDoctor->specialty), true)) {
            throw ValidationException::withMessages([
                'diagnostic_specialty' => 'Select the specialty registered for this doctor.',
            ]);
        }
        $currentStart = isset($filters['diagnostic_week'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['diagnostic_week'], 'Asia/Manila') : $latest;
        $previousStart = isset($filters['diagnostic_compare_week'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['diagnostic_compare_week'], 'Asia/Manila')
            : $currentStart->subWeek();
        if ($previousStart->equalTo($currentStart)) {
            throw ValidationException::withMessages(['diagnostic_compare_week' => 'Choose a different week to compare.']);
        }
        $reportDate = isset($filters['report_date'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['report_date'], 'Asia/Manila') : null;
        $allDates = ($filters['report_scope'] ?? null) === 'all';
        $ranges = match (true) {
            $allDates => [['All dates', null, null]],
            $reportDate !== null => [[$reportDate->format('M j, Y'), $reportDate, $reportDate->addDay()]],
            default => array_map(fn ($start) => [
                $start->format('M j, Y') . ' – ' . $start->addDays(6)->format('M j, Y'),
                $start, $start->addWeek(),
            ], [$previousStart, $currentStart]),
        };

        $all = DB::table('feedback as f')
            ->leftJoin('sentiment_results as sr', 'sr.feedback_id', '=', 'f.feedback_id')
            ->leftJoin('schedule_visit as v', 'v.visit_id', '=', 'f.visit_id')
            ->leftJoin('doctors as d', 'd.doctor_id', '=', 'v.doctor_id')
            ->when(!$allDates, fn ($query) => $this->restrictToRanges($query, 'f.submitted_at', $ranges))
            ->orderBy('f.submitted_at')
            ->get(['f.feedback_id', 'f.feedback_text', 'f.submitted_at', 'sr.sentiment_label',
                'f.star_rating', 'v.doctor_id', 'd.doctor_name', 'v.service_type']);
        \App\Support\DoctorServices::withLabels($all, 'doctor_specialty');
        $unlinked = $all->filter(fn ($row) => !$row->doctor_id || !trim($row->doctor_specialty ?? ''))->count();
        $rows = $all->filter(fn ($row) =>
            (empty($filters['diagnostic_doctor']) || (string) $row->doctor_id === (string) $filters['diagnostic_doctor'])
            && (empty($filters['diagnostic_specialty']) || in_array($filters['diagnostic_specialty'], DoctorSpecialties::split($row->doctor_specialty), true)));
        $negative = $rows->filter(fn ($row) => trim($row->feedback_text ?? '') !== ''
            && strtolower($row->sentiment_label ?? '') === 'negative');
        if ($forReport) {
            return $this->buildReport($rows, $negative, $ranges, $allDates) + [
                'doctor' => $selectedDoctor?->doctor_name,
                'specialty' => $filters['diagnostic_specialty'] ?? null,
                'low_rating_max' => self::LOW_RATING_MAX,
            ];
        }
        $items = $negative->map(fn ($row) => [
            'id' => $row->feedback_id, 'text' => $row->feedback_text, 'source' => 'feedback',
        ]);
        $diagnosis = app(IssueAnalysisService::class)->resolve($items);
        $details = $diagnosis['details']->keyBy('id');
        $available = $diagnosis['pending'] === 0;
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
                    'rating' => $row->star_rating,
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
            'week' => $currentStart->format('Y-m-d'), 'compare_week' => $previousStart->format('Y-m-d'),
            'latest_week' => $latest->format('Y-m-d'),
            'doctor' => $filters['diagnostic_doctor'] ?? '', 'specialty' => $filters['diagnostic_specialty'] ?? '',
            'doctors' => $doctors, 'specialties' => $specialties,
            'periods' => $periods, 'categories' => $categories,
            'pending_issues' => $diagnosis['pending'],
            'available' => $available, 'unlinked' => $unlinked,
        ];
    }

    private function restrictToRanges(Builder $query, string $column, array $ranges): Builder
    {
        return $query->where(function (Builder $dates) use ($column, $ranges) {
            foreach ($ranges as [, $from, $until]) {
                $dates->orWhere(fn (Builder $range) => $range
                    ->where($column, '>=', $from->toDateTimeString())
                    ->where($column, '<', $until->toDateTimeString()));
            }
        });
    }

    private function buildReport(Collection $reviews, Collection $negative, array $ranges, bool $allDates): array
    {
        $complaints = DB::table('complaints')
            ->when(!$allDates, fn ($query) => $this->restrictToRanges($query, 'created_at', $ranges))
            ->get(['complaint_id', 'complaint_text', 'created_at']);
        $low = $reviews->filter(fn ($review) => $this->isLowRating($review->star_rating));
        $written = $negative->union($low->filter(fn ($review) => trim($review->feedback_text ?? '') !== ''));
        $items = $written->map(fn ($review) => [
            'id' => $review->feedback_id, 'text' => $review->feedback_text, 'source' => 'feedback',
        ])->values()->concat($complaints->map(fn ($complaint) => [
            'id' => $complaint->complaint_id, 'text' => $complaint->complaint_text, 'source' => 'complaint',
        ]));
        $diagnosis = app(IssueAnalysisService::class)->resolve($items);
        $details = $diagnosis['details']->groupBy('source');
        $reviewMatches = $details->get('feedback', collect())->keyBy('id');
        $complaintMatches = $details->get('complaint', collect())->keyBy('id');

        $periods = [];
        foreach ($ranges as [$label, $start, $end]) {
            $from = $start?->toDateTimeString();
            $until = $end?->toDateTimeString();
            $periodReviews = $from === null ? $reviews : $reviews->filter(fn ($review) => $review->submitted_at >= $from && $review->submitted_at < $until);
            $periodComplaints = $from === null ? $complaints : $complaints->filter(fn ($complaint) => $complaint->created_at >= $from && $complaint->created_at < $until);
            $complaintCategories = [];
            foreach ($periodComplaints as $complaint) {
                foreach (array_unique($complaintMatches->get($complaint->complaint_id)['matched_categories'] ?? []) as $category) {
                    $complaintCategories[$category] = ($complaintCategories[$category] ?? 0) + 1;
                }
            }
            arsort($complaintCategories);
            $periods[] = [
                'label' => $label,
                'overall' => $this->summarizeReviews($periodReviews, $negative, $reviewMatches),
                'doctors' => $periodReviews->groupBy(fn ($review) => $review->doctor_id ?? 0, preserveKeys: true)
                    ->map(fn ($group) => ['label' => $group->first()->doctor_name ?: 'Doctor not linked']
                        + $this->summarizeReviews($group, $negative, $reviewMatches))->sortBy('label'),
                'services' => $periodReviews->groupBy(fn ($review) => trim($review->service_type ?? '') ?: 'Service not linked', preserveKeys: true)
                    ->map(fn ($group, $label) => ['label' => $label]
                        + $this->summarizeReviews($group, $negative, $reviewMatches))->sortBy('label'),
                'complaints' => $periodComplaints->count(),
                'complaint_categories' => $complaintCategories,
            ];
        }

        return ['periods' => $periods, 'available' => $diagnosis['pending'] === 0, 'pending_issues' => $diagnosis['pending']];
    }

    private function summarizeReviews(Collection $reviews, Collection $negative, Collection $matches): array
    {
        $ratings = $reviews->whereBetween('star_rating', [1, 5]);
        $low = $ratings->filter(fn ($review) => $this->isLowRating($review->star_rating));
        $categories = [];
        foreach ($reviews as $key => $review) {
            $isNegative = $negative->has($key);
            $isLow = $low->has($key);
            foreach (array_unique($matches->get($review->feedback_id)['matched_categories'] ?? []) as $category) {
                $categories[$category] ??= ['negative' => 0, 'low' => 0, 'negative_ratings' => []];
                $categories[$category]['negative'] += (int) $isNegative;
                $categories[$category]['low'] += (int) $isLow;
                if ($isNegative) {
                    $rating = $ratings->has($key) ? (int) $review->star_rating : 'Unrated';
                    $categories[$category]['negative_ratings'][$rating] = ($categories[$category]['negative_ratings'][$rating] ?? 0) + 1;
                }
            }
        }
        ksort($categories);

        return [
            'reviews' => $reviews->count(),
            'rated' => $ratings->count(),
            'average' => $ratings->avg('star_rating'),
            'low' => $low->count(),
            'low_without_comment' => $low->filter(fn ($review) => trim($review->feedback_text ?? '') === '')->count(),
            'negative' => $reviews->intersectByKeys($negative)->count(),
            'categories' => $categories,
        ];
    }

    private function isLowRating(?int $rating): bool
    {
        return $rating !== null && $rating >= 1 && $rating <= self::LOW_RATING_MAX;
    }
}
