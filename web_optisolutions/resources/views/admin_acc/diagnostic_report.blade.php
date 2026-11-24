@php
    $periods = $report['periods'];
    $current = end($periods);
    $comparison = $report['comparison'];
    $overall = $current['overall'];
    $rating = fn ($value) => $value === null ? 'No ratings' : number_format($value, 1) . ' / 5';
    $pct = fn ($value) => $value === null ? '—' : $value . '%';
    $kpiRows = $comparison['rows'] ?? [];
    $kpis = [
        ['Reviews received', number_format($overall['reviews']), $kpiRows[0] ?? null],
        ['Average rating', $rating($overall['average']), $kpiRows[1] ?? null],
        ['Unhappy reviews (1–' . $report['low_rating_max'] . ' stars)', $pct($current['low_rate']), $kpiRows[2] ?? null],
        ['Reviews with a negative comment', $pct($current['negative_rate']), $kpiRows[3] ?? null],
        ['Clinic complaints', number_format($current['complaints']), $kpiRows[4] ?? null],
    ];
    $arrow = fn ($row) => $row && $row['changed'] ? (str_starts_with($row['delta'], '+') ? '▲' : '▼') : '■';
    $tone = fn ($row) => $row && $row['better'] !== null ? ($row['better'] ? 'good' : 'bad') : 'flat';
    $issues = $current['issues'];
    $topIssues = array_slice($issues, 0, 5);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnostic Report | OptiSolutions</title>
    <style>
        :root { --ink: #062744; --muted: #4d6478; --line: #b7c9d8; --soft: #eef4fa; --brand: #0e62aa; --good: #14683a; --bad: #a61b1b; }
        * { box-sizing: border-box; }
        body { max-width: 56rem; margin: 0 auto; padding: 1.5rem 1rem 3rem; color: var(--ink); font: 1rem/1.5 system-ui, sans-serif; }
        h1 { margin: 0 0 .25rem; font-size: 1.6rem; line-height: 1.2; }
        h2 { margin: 0 0 .6rem; padding-bottom: .3rem; border-bottom: 2px solid var(--line); font-size: 1.1rem; line-height: 1.3; }
        h3 { margin: 1rem 0 .3rem; font-size: .95rem; }
        section { margin: 1.6rem 0; }
        p { margin: .3rem 0; }
        .meta { margin: 0; color: var(--muted); font-size: .875rem; }
        .small { font-size: .8125rem; color: var(--muted); }
        button { margin-top: .75rem; padding: .55rem 1rem; background: var(--brand); color: #fff; border: 0; border-radius: 6px; font: inherit; cursor: pointer; }
        .headline { margin: 1.25rem 0 .75rem; padding: .85rem 1rem; background: var(--soft); border-left: 5px solid var(--brand); font-size: 1.05rem; font-weight: 600; }
        .status { margin: .75rem 0; padding: .6rem .9rem; border: 1px solid #c58a00; background: #fff7df; font-size: .9rem; }
        .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(9.5rem, 1fr)); gap: .5rem; margin: 0; padding: 0; list-style: none; }
        .kpis li { padding: .65rem .75rem; border: 1px solid var(--line); border-radius: 6px; }
        .kpi-label { display: block; color: var(--muted); font-size: .78rem; line-height: 1.3; }
        .kpi-value { display: block; margin: .15rem 0; font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
        .kpi-change { display: block; font-size: .78rem; font-weight: 600; }
        .good { color: var(--good); } .bad { color: var(--bad); } .flat { color: var(--muted); }
        .table-scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: .4rem 0; font-size: .9rem; }
        th, td { padding: .4rem .6rem; border: 1px solid var(--line); text-align: left; vertical-align: top; }
        thead th { background: #d3e4f3; color: #123b63; font-size: .78rem; text-transform: uppercase; letter-spacing: .02em; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
        .bar { display: inline-block; height: .6rem; min-width: 2px; background: var(--brand); border-radius: 2px; vertical-align: middle; }
        tr.muted-row td, tr.muted-row th { color: var(--muted); font-style: italic; }
        .flag { display: inline-block; padding: 0 .4rem; border-radius: 3px; background: #fde4e4; color: var(--bad); font-size: .75rem; font-weight: 700; }
        .flag.neutral { background: #eceff2; color: var(--muted); }
        blockquote { margin: .25rem 0 .25rem .5rem; padding-left: .75rem; border-left: 3px solid var(--line); font-size: .875rem; }
        ol.actions { margin: .4rem 0; padding-left: 1.25rem; }
        ol.actions li { margin: .35rem 0; }
        .method { font-size: .8125rem; color: var(--muted); }
        .method ul { margin: .3rem 0; padding-left: 1.25rem; }
        @page { size: A4; margin: 0; }
        @media print {
            body { max-width: none; padding: 12mm 14mm; color: #111; font-size: 10pt; }
            button { display: none; }
            .table-scroll { overflow: visible; }
            section { margin: 1rem 0; break-inside: avoid; }
            h2, h3 { break-after: avoid; }
            tr, .kpis li, blockquote { break-inside: avoid; }
            .kpis { grid-template-columns: repeat(5, 1fr); }
            .kpis li { padding: .5rem; }
            .kpi-value { font-size: 1.2rem; }
            .headline, thead th, .flag, .bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <header>
        <h1>Patient Feedback Diagnostic Report</h1>
        <p class="meta"><strong>Period:</strong> {{ $current['label'] }}@if($comparison) (compared with {{ $comparison['previous_label'] }})@endif@if($report['scope'] === 'weeks') · Week-by-week comparison@endif</p>
        <p class="meta"><strong>Filters:</strong> {{ $report['doctor'] ?? 'All doctors' }} · {{ $report['specialty'] ?? 'All specialties' }}</p>
        <p class="meta">Prepared {{ $report['generated_at'] }} (Philippine time) · OptiSolutions</p>
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </header>

    @unless($report['available'])
        <p class="status" role="status">Issue analysis is still pending for {{ $report['pending_issues'] }} response(s). Issue counts reflect the results available so far.</p>
    @endunless

    <p class="headline">{{ $report['headline'] }}</p>

    <section aria-labelledby="summary">
        <h2 id="summary">Summary{{ $comparison ? ' (change versus the previous period)' : '' }}</h2>
        <ul class="kpis">
            @foreach($kpis as [$label, $value, $row])
                <li>
                    <span class="kpi-label">{{ $label }}</span>
                    <span class="kpi-value">{{ $value }}</span>
                    @if($row)
                        <span class="kpi-change {{ $tone($row) }}">{{ $arrow($row) }} {{ $row['changed'] ? $row['delta'] : 'No change' }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
        @unless($comparison)
            <p class="small">This report covers a single period, so there is no earlier period to compare against. Use “Selected weeks” in Generate report to see changes.</p>
        @endunless
    </section>

    @if($comparison)
        <section aria-labelledby="trend">
            <h2 id="trend">How this period compares</h2>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Measure</th>
                            <th scope="col" class="num">{{ $comparison['current_label'] }}</th>
                            <th scope="col" class="num">{{ $comparison['previous_label'] }}</th>
                            <th scope="col" class="num">Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparison['rows'] as $row)
                            <tr>
                                <th scope="row">{{ $row['label'] }}</th>
                                <td class="num">{{ $row['current'] }}</td>
                                <td class="num">{{ $row['previous'] }}</td>
                                <td class="num {{ $tone($row) }}">{{ $arrow($row) }} {{ $row['changed'] ? $row['delta'] : 'No change' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small">Green means better, red means worse. “pts” are percentage points. Reviews received has no good or bad direction.</p>
        </section>
    @endif

    <section aria-labelledby="issues">
        <h2 id="issues">Top issues patients raised</h2>
        @if($current['issue_reports'] === 0)
            <p>No complaints or unhappy comments were recorded in this period.</p>
        @else
            <p class="small">Based on {{ $current['issue_reports'] }} {{ Str::plural('report', $current['issue_reports']) }}: {{ $current['complaints'] }} clinic {{ Str::plural('complaint', $current['complaints']) }} and {{ $current['issue_reports'] - $current['complaints'] }} unhappy {{ Str::plural('review', $current['issue_reports'] - $current['complaints']) }} with a comment.</p>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Issue</th>
                            <th scope="col" class="num">Complaints</th>
                            <th scope="col" class="num">Reviews</th>
                            <th scope="col" class="num">Total</th>
                            <th scope="col">Share of reports</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topIssues as $issue)
                            <tr>
                                <th scope="row">{{ $issue['label'] }}</th>
                                <td class="num">{{ $issue['complaints'] }}</td>
                                <td class="num">{{ $issue['reviews'] }}</td>
                                <td class="num">{{ $issue['total'] }}</td>
                                <td><span class="bar" style="width: {{ max(0, min(100, $issue['share'] ?? 0)) * 0.9 }}px" aria-hidden="true"></span> {{ $pct($issue['share']) }}</td>
                            </tr>
                        @endforeach
                        @if(count($issues) > count($topIssues))
                            @php($rest = array_slice($issues, count($topIssues)))
                            <tr>
                                <th scope="row">{{ count($rest) }} other {{ Str::plural('issue', count($rest)) }}</th>
                                <td class="num">{{ array_sum(array_column($rest, 'complaints')) }}</td>
                                <td class="num">{{ array_sum(array_column($rest, 'reviews')) }}</td>
                                <td class="num">{{ array_sum(array_column($rest, 'total')) }}</td>
                                <td>{{ collect($rest)->pluck('label')->implode(', ') }}</td>
                            </tr>
                        @endif
                        <tr class="muted-row">
                            <th scope="row">No category matched</th>
                            <td colspan="2"></td>
                            <td class="num">{{ $current['uncategorized'] }}</td>
                            <td>{{ $pct($current['issue_reports'] ? (int) round($current['uncategorized'] / $current['issue_reports'] * 100) : null) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="small">A report can match more than one issue, so shares can add up to more than 100%. “No category matched” means the text did not contain any tracked keyword. These reports still need to be read.@unless($report['available']) Counts are incomplete while analysis is pending.@endunless</p>
        @endif

        @if($overall['low_without_comment'] > 0)
            <p><strong>{{ $overall['low_without_comment'] }}</strong> unhappy {{ Str::plural('rating', $overall['low_without_comment']) }} (1–{{ $report['low_rating_max'] }} stars) came with no comment, so the reason is unknown. These are a chance to follow up with patients.</p>
        @endif
    </section>

    @if($report['show_comments'] && collect($topIssues)->contains(fn ($issue) => $issue['samples']))
        <section aria-labelledby="comments">
            <h2 id="comments">What patients said</h2>
            @foreach(array_slice($topIssues, 0, 3) as $issue)
                @if($issue['samples'])
                    <h3>{{ $issue['label'] }}</h3>
                    @foreach($issue['samples'] as $sample)
                        <blockquote>{{ $sample }}</blockquote>
                    @endforeach
                @endif
            @endforeach
            <p class="small">Short excerpts for context only. Names are not included. Keep this report private.</p>
        </section>
    @endif

    <section aria-labelledby="breakdown">
        <h2 id="breakdown">Ratings by doctor and by service</h2>
        @foreach(['By doctor' => [$current['doctor_breakdown'], 'doctor'], 'By service' => [$current['service_breakdown'], 'service']] as $title => [$breakdown, $noun])
            <h3>{{ $title }}</h3>
            @if($breakdown['rows'])
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">{{ ucfirst($noun) }}</th>
                                <th scope="col" class="num">Reviews</th>
                                <th scope="col" class="num">Average rating</th>
                                <th scope="col" class="num">Unhappy reviews</th>
                                <th scope="col">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($breakdown['rows'] as $row)
                                <tr>
                                    <th scope="row">{{ $row['label'] }}</th>
                                    <td class="num">{{ $row['reviews'] }}</td>
                                    <td class="num">{{ $rating($row['average']) }}</td>
                                    <td class="num">{{ $pct($row['low_rate']) }}</td>
                                    <td>
                                        @if($row['needs_attention'])<span class="flag">Needs attention</span>
                                        @elseif($row['few_reviews'])<span class="flag neutral">Only {{ $row['reviews'] }} {{ Str::plural('review', $row['reviews']) }}</span>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="small">Worst average first. “Needs attention” means an average below {{ number_format($breakdown['attention_average'], 1) }} with at least {{ $breakdown['min_reviews'] }} reviews.</p>
            @elseif($current['overall']['reviews'] > 0)
                <p>This breakdown is not available: none of the {{ $current['overall']['reviews'] }} {{ Str::plural('review', $current['overall']['reviews']) }} are linked to a {{ $noun }}. Reviews appear here only when they come from a recorded visit.</p>
            @else
                <p>No reviews in this period.</p>
            @endif
            @if($breakdown['rows'] && $breakdown['unlinked_reviews'] > 0)
                <p class="small">{{ $breakdown['unlinked_reviews'] }} {{ Str::plural('review', $breakdown['unlinked_reviews']) }} not linked to a {{ $noun }} {{ $breakdown['unlinked_reviews'] === 1 ? 'is' : 'are' }} left out of this table but counted in the totals above.</p>
            @endif
        @endforeach
    </section>

    <section aria-labelledby="actions">
        <h2 id="actions">Suggested next steps</h2>
        @if($report['actions'])
            <ol class="actions">
                @foreach($report['actions'] as $action)
                    <li>{{ $action }}</li>
                @endforeach
            </ol>
            <p class="small">Suggestions come from keyword counts and do not prove a cause. Check the underlying feedback before acting.</p>
        @else
            <p>Nothing stands out in this period that needs a specific follow-up.</p>
        @endif
    </section>

    <section class="method" aria-labelledby="method">
        <h2 id="method">How to read this report</h2>
        <ul>
            <li>Unhappy reviews are ratings of 1–{{ $report['low_rating_max'] }} stars, including ratings with no comment.</li>
            <li>Clinic complaints are filed separately from reviews, so they are counted separately and are not part of the review totals.</li>
            <li>Issue categories come from keyword matches. They are indicators for further investigation, not confirmed causes.</li>
            <li>All dates and times use Philippine time.</li>
        </ul>
    </section>
</body>
</html>