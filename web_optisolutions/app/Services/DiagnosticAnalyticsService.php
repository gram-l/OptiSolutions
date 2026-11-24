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
    private const MIN_REVIEWS_FOR_RANKING = 5;
    private const ATTENTION_AVERAGE = 3.5;
    private const SAMPLE_LIMIT = 3;
    private const SAMPLE_LENGTH = 180;

    private const ISSUE_LABELS = [
        'waiting_time' => 'Waiting time',
        'staff_attitude' => 'Staff attitude',
        'consultation_fees' => 'Consultation fees',
        'medical_certificate' => 'Medical certificates',
        'doctor_expertise' => 'Doctor expertise',
        'doctor_availability' => 'Doctor availability',
        'appointment_scheduling' => 'Appointment scheduling',
        'staff_attentiveness' => 'Staff attentiveness',
        'billing_payment' => 'Billing and payment',
        'lab_results' => 'Lab results',
        'communication_information' => 'Communication and information',
        'online_system' => 'Online system',
        'cleanliness_hygiene' => 'Cleanliness and comfort',
        'privacy_confidentiality' => 'Privacy',
        'accessibility_facility' => 'Accessibility and facilities',
        'operating_hours' => 'Operating hours',
    ];

    private const ISSUE_ADVICE = [
        'waiting_time' => 'Review queue flow and appointment spacing at the busiest hours.',
        'staff_attitude' => 'Raise this with front-desk and clinic staff and check for repeat patterns.',
        'consultation_fees' => 'Check that fees are displayed clearly before the visit.',
        'medical_certificate' => 'Review how long medical certificates take and what patients are told to bring.',
        'doctor_expertise' => 'Share the feedback with the doctors concerned for a professional review.',
        'doctor_availability' => 'Check doctor schedules and how absences are announced to patients.',
        'appointment_scheduling' => 'Review booking slots, cancellations and confirmation messages.',
        'staff_attentiveness' => 'Check that patients are greeted and assisted promptly on arrival.',
        'billing_payment' => 'Review billing, receipts and accepted payment methods.',
        'lab_results' => 'Check turnaround time for lab results and how patients are told when they are ready.',
        'communication_information' => 'Make sure patients get clear explanations and replies to their questions.',
        'online_system' => 'Test the website, chatbot and online booking for errors.',
        'cleanliness_hygiene' => 'Inspect the waiting area, restrooms and room comfort.',
        'privacy_confidentiality' => 'Review how patient information is discussed and stored in public areas.',
        'accessibility_facility' => 'Check parking, signage and access for patients who need assistance.',
        'operating_hours' => 'Confirm the posted opening hours match what actually happens.',
    ];

    public function dashboard(Request $request, bool $forReport = false): array
    {
        $thisWeek = CarbonImmutable::now('Asia/Manila')->startOfWeek(CarbonImmutable::MONDAY);
        $latest = $thisWeek->subWeek();
        $doctors = \App\Support\DoctorServices::withLabels(DB::table('doctors')->orderBy('doctor_name')->get(['doctor_id', 'doctor_name']));
        $specialties = DoctorSpecialties::catalog();
        $weekRules = [
            Rule::excludeIf($forReport && in_array($request->input('report_scope'), ['all', 'date'], true)),
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
            'report_scope' => ['nullable', Rule::in(['all', 'date', 'weeks'])],
            'report_date' => ['exclude_unless:report_scope,date', 'required', 'date_format:Y-m-d'],
            'report_comments' => ['nullable', 'boolean'],
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
            $showComments = (bool) ($filters['report_comments'] ?? false);

            return $this->buildReport($rows, $negative, $ranges, $allDates, $showComments) + [
                'doctor' => $selectedDoctor?->doctor_name,
                'specialty' => $filters['diagnostic_specialty'] ?? null,
                'low_rating_max' => self::LOW_RATING_MAX,
                'scope' => $allDates ? 'all' : ($reportDate !== null ? 'date' : 'weeks'),
                'show_comments' => $showComments,
                'generated_at' => CarbonImmutable::now('Asia/Manila')->format('M j, Y g:i A'),
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

    private function buildReport(Collection $reviews, Collection $negative, array $ranges, bool $allDates, bool $showComments = false): array
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
            $overall = $this->summarizeReviews($periodReviews, $negative, $reviewMatches);
            $doctors = $periodReviews->groupBy(fn ($review) => $review->doctor_id ?? 0, preserveKeys: true)
                ->map(fn ($group) => ['label' => $group->first()->doctor_name ?: 'Doctor not linked']
                    + $this->summarizeReviews($group, $negative, $reviewMatches))->sortBy('label');
            $services = $periodReviews->groupBy(fn ($review) => trim($review->service_type ?? '') ?: 'Service not linked', preserveKeys: true)
                ->map(fn ($group, $label) => ['label' => $label]
                    + $this->summarizeReviews($group, $negative, $reviewMatches))->sortBy('label');
            $flagged = $periodReviews->filter(fn ($review, $key) => $negative->has($key)
                || ($this->isLowRating($review->star_rating) && trim($review->feedback_text ?? '') !== ''));
            $ranking = $this->rankIssues($flagged, $periodComplaints, $reviewMatches, $complaintMatches, $showComments);

            $periods[] = [
                'label' => $label,
                'overall' => $overall,
                'doctors' => $doctors,
                'services' => $services,
                'complaints' => $periodComplaints->count(),
                'complaint_categories' => $complaintCategories,
                'low_rate' => $this->percent($overall['low'], $overall['rated']),
                'negative_rate' => $this->percent($overall['negative'], $overall['reviews']),
                'issues' => $ranking['issues'],
                'issue_reports' => $ranking['total'],
                'uncategorized' => $ranking['uncategorized'],
                'doctor_breakdown' => $this->breakdown($doctors, fn ($key) => (int) $key === 0),
                'service_breakdown' => $this->breakdown($services, fn ($key) => $key === 'Service not linked'),
            ];
        }

        $available = $diagnosis['pending'] === 0;
        $comparison = count($periods) === 2 ? $this->compare($periods[0], $periods[1]) : null;
        $current = end($periods);
        $previous = count($periods) === 2 ? $periods[0] : null;

        return [
            'periods' => $periods,
            'available' => $available,
            'pending_issues' => $diagnosis['pending'],
            'comparison' => $comparison,
            'headline' => $this->headline($current, $previous, $available),
            'actions' => $this->recommend($current, $available),
        ];
    }

    private function percent(int $part, int $whole): ?int
    {
        return $whole > 0 ? (int) round($part / $whole * 100) : null;
    }

    private function issueLabel(string $category): string
    {
        return self::ISSUE_LABELS[$category] ?? ucfirst(str_replace('_', ' ', $category));
    }

    /**
     * Rank issue categories across unhappy reviews that have a comment and clinic complaints.
     * One report can match several categories, so shares can add up to more than 100%.
     */
    private function rankIssues(Collection $flagged, Collection $complaints, Collection $reviewMatches, Collection $complaintMatches, bool $withComments): array
    {
        $issues = [];
        $categorized = 0;
        $record = function (string $source, ?string $text, array $categories) use (&$issues, &$categorized, $withComments) {
            $categories = array_values(array_unique($categories));
            $categorized += $categories ? 1 : 0;
            foreach ($categories as $category) {
                $issues[$category] ??= ['category' => $category, 'label' => $this->issueLabel($category),
                    'complaints' => 0, 'reviews' => 0, 'samples' => []];
                $issues[$category][$source]++;
                $text = trim((string) $text);
                if ($withComments && $text !== '' && count($issues[$category]['samples']) < self::SAMPLE_LIMIT) {
                    $issues[$category]['samples'][] = \Illuminate\Support\Str::limit($text, self::SAMPLE_LENGTH);
                }
            }
        };
        foreach ($flagged as $review) {
            $record('reviews', $review->feedback_text, $reviewMatches->get($review->feedback_id)['matched_categories'] ?? []);
        }
        foreach ($complaints as $complaint) {
            $record('complaints', $complaint->complaint_text, $complaintMatches->get($complaint->complaint_id)['matched_categories'] ?? []);
        }
        $total = $flagged->count() + $complaints->count();
        $issues = collect($issues)->map(fn ($issue) => $issue + [
            'total' => $issue['complaints'] + $issue['reviews'],
            'share' => $this->percent($issue['complaints'] + $issue['reviews'], $total),
        ])->sort(fn ($a, $b) => ($b['total'] <=> $a['total']) ?: strcmp($a['label'], $b['label']))->values()->all();

        return ['issues' => $issues, 'total' => $total, 'uncategorized' => $total - $categorized];
    }

    /** Doctor or service rows, worst average first. Unlinked reviews are counted but not listed as a group. */
    private function breakdown(Collection $groups, callable $isUnlinked): array
    {
        $rows = $groups->reject(fn ($group, $key) => $isUnlinked($key))->map(fn ($group) => [
            'label' => $group['label'],
            'reviews' => $group['reviews'],
            'average' => $group['average'],
            'low_rate' => $this->percent($group['low'], $group['rated']),
            'few_reviews' => $group['reviews'] < self::MIN_REVIEWS_FOR_RANKING,
            'needs_attention' => $group['average'] !== null && $group['average'] < self::ATTENTION_AVERAGE
                && $group['reviews'] >= self::MIN_REVIEWS_FOR_RANKING,
        ])->sort(fn ($a, $b) => (($a['average'] ?? 6) <=> ($b['average'] ?? 6)) ?: strcmp($a['label'], $b['label']))
            ->values()->all();

        return [
            'rows' => $rows,
            'unlinked_reviews' => $groups->filter(fn ($group, $key) => $isUnlinked($key))->sum('reviews'),
            'min_reviews' => self::MIN_REVIEWS_FOR_RANKING,
            'attention_average' => self::ATTENTION_AVERAGE,
        ];
    }

    /** Current period against the previous one. `better` is true, false, or null when it has no good/bad direction. */
    private function compare(array $previous, array $current): array
    {
        $row = function (string $label, $now, $before, callable $format, ?bool $lowerIsBetter, int $precision = 0, ?callable $deltaFormat = null) {
            $delta = $now === null || $before === null ? null : round($now - $before, $precision);

            return [
                'label' => $label, 'current' => $now === null ? '—' : $format($now),
                'previous' => $before === null ? '—' : $format($before),
                'delta' => $delta === null ? '—' : ($delta > 0 ? '+' : ($delta < 0 ? '−' : '')) . ($deltaFormat ?? $format)(abs($delta)),
                'better' => $delta === null || $delta == 0 || $lowerIsBetter === null ? null : ($lowerIsBetter ? $delta < 0 : $delta > 0),
                'changed' => $delta !== null && $delta != 0,
            ];
        };
        $count = fn ($value) => number_format($value);
        $rating = fn ($value) => number_format($value, 1);
        $pct = fn ($value) => $value . '%';
        $points = fn ($value) => $value . ' pts';
        $now = $current['overall'];
        $before = $previous['overall'];

        return [
            'current_label' => $current['label'], 'previous_label' => $previous['label'],
            'rows' => [
                $row('Reviews received', $now['reviews'], $before['reviews'], $count, null),
                $row('Average rating (out of 5)', $now['average'] === null ? null : round($now['average'], 1),
                    $before['average'] === null ? null : round($before['average'], 1), $rating, false, 1),
                $row('Unhappy reviews (1–' . self::LOW_RATING_MAX . ' stars)', $current['low_rate'], $previous['low_rate'], $pct, true, 0, $points),
                $row('Reviews with a negative comment', $current['negative_rate'], $previous['negative_rate'], $pct, true, 0, $points),
                $row('Clinic complaints', $current['complaints'], $previous['complaints'], $count, true),
            ],
        ];
    }

    private function headline(array $current, ?array $previous, bool $available): string
    {
        $overall = $current['overall'];
        if ($overall['reviews'] === 0 && $current['complaints'] === 0) {
            return 'No reviews or complaints were recorded for this period.';
        }
        $parts = [];
        if ($overall['average'] !== null) {
            $average = round($overall['average'], 1);
            $parts[] = sprintf('Patients rated the clinic %s out of 5 across %d %s, and %d%% of rated reviews were unhappy (1–%d stars).',
                number_format($average, 1), $overall['reviews'], $overall['reviews'] === 1 ? 'review' : 'reviews',
                $current['low_rate'] ?? 0, self::LOW_RATING_MAX);
            $before = $previous['overall']['average'] ?? null;
            if ($before !== null) {
                $before = round($before, 1);
                $parts[] = $average > $before ? sprintf('This is up from %s in the previous period.', number_format($before, 1))
                    : ($average < $before ? sprintf('This is down from %s in the previous period.', number_format($before, 1))
                        : 'This is the same as the previous period.');
            }
        } elseif ($overall['reviews'] > 0) {
            $parts[] = sprintf('%d %s received, none with a star rating.', $overall['reviews'], $overall['reviews'] === 1 ? 'review was' : 'reviews were');
        }
        if ($current['issues'] && $current['issue_reports'] > 0) {
            $top = $current['issues'][0];
            $parts[] = sprintf('%s is the most reported issue (%d of %d %s).', $top['label'], $top['total'],
                $current['issue_reports'], $current['issue_reports'] === 1 ? 'report' : 'reports')
                . ($available ? '' : ' (Analysis is still pending for some responses.)');
        }

        return implode(' ', $parts);
    }

    private function recommend(array $current, bool $available): array
    {
        $actions = [];
        $reports = $current['issue_reports'];
        foreach (array_slice($current['issues'], 0, 3) as $issue) {
            if ($issue['total'] < 2) {
                continue;
            }
            $actions[] = sprintf('%s appears in %d%% of issue reports (%d of %d). %s', $issue['label'], $issue['share'] ?? 0,
                $issue['total'], $reports, self::ISSUE_ADVICE[$issue['category']] ?? 'Review these reports for a common cause.');
        }
        if ($available && $reports >= 5 && $current['uncategorized'] / $reports > 0.5) {
            $actions[] = sprintf('%d%% of issue reports did not match any category. Read them directly, and consider adding keywords for the missing topics.',
                $this->percent($current['uncategorized'], $reports));
        }
        $silent = $current['overall']['low_without_comment'];
        if ($silent >= 5 && $current['overall']['reviews'] > 0 && $silent / $current['overall']['reviews'] >= 0.25) {
            $actions[] = sprintf('%d unhappy ratings came with no comment. Consider following up to learn what went wrong.', $silent);
        }

        return $actions;
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