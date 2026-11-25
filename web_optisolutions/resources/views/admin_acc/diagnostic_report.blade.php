<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnostic Report | OptiSolutions</title>
    <style>
        :root { --navy: #17365d; --ink: #1d2b3a; --muted: #5b6b7b; --line: #d5dde5; --band: #f3f6f9; --accent: #1f5f9e; --amber: #8a5a00; --amber-bg: #fdf7e8; --amber-line: #e6cf94; }
        * { box-sizing: border-box; }
        body { max-width: 56rem; margin: 0 auto; padding: 2.5rem 1.25rem 3rem; color: var(--ink); background: #fff; font: 0.975rem/1.6 "Segoe UI", system-ui, -apple-system, sans-serif; }
        h1, h2, h3 { font-family: Georgia, "Times New Roman", serif; color: var(--navy); line-height: 1.25; font-weight: 700; }
        .letterhead { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; border-bottom: 3px solid var(--navy); padding-bottom: 1rem; margin-bottom: 1.25rem; }
        .print-btn { flex: none; margin-top: 1.15rem; white-space: nowrap; }
        .org { margin: 0; font: 600 .75rem/1.4 "Segoe UI", system-ui, sans-serif; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); }
        h1 { margin: .15rem 0 .2rem; font-size: 2rem; }
        .subtitle { margin: 0; color: var(--muted); }
        .meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); gap: .5rem 1.5rem; margin: 0 0 1.25rem; padding: .85rem 1rem; background: var(--band); border: 1px solid var(--line); border-radius: 4px; font-size: .875rem; }
        .meta dt { font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
        .meta dd { margin: 0; font-weight: 600; }
        button { padding: .5rem 1rem; background: var(--navy); color: #fff; border: 0; border-radius: 4px; font: 600 .875rem "Segoe UI", system-ui, sans-serif; cursor: pointer; }
        section.period { margin: 2.25rem 0 0; }
        h2 { margin: 0 0 1rem; padding: .5rem .85rem; font-size: 1.25rem; background: var(--navy); color: #fff; border-radius: 3px; }
        h3 { margin: 1.9rem 0 .5rem; padding-bottom: .3rem; border-bottom: 1px solid var(--line); font-size: 1.1rem; }
        h3 .no { color: var(--accent); margin-right: .35rem; }
        .summary { border: 1px solid var(--line); border-left: 5px solid var(--navy); border-radius: 3px; padding: .9rem 1.2rem; margin: 0 0 .5rem; background: #fff; }
        .summary h3 { margin-top: 0; border: 0; padding: 0; }
        .summary ul { margin: .25rem 0 0; padding-left: 1.15rem; }
        .summary li { margin: .35rem 0; }
        .alert { background: var(--amber-bg); border: 1px solid var(--amber-line); border-radius: 3px; padding: .7rem 1rem; margin: .75rem 0; color: #4d3500; font-size: .9rem; }
        .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; margin: .75rem 0; }
        .card { border: 1px solid var(--line); border-top: 3px solid var(--accent); border-radius: 3px; padding: .7rem .9rem; background: #fff; }
        .card .label { font-size: .7rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--muted); }
        .card .value { font: 700 1.8rem/1.25 Georgia, "Times New Roman", serif; color: var(--navy); }
        .card .hint { font-size: .8rem; color: var(--muted); }
        .table-scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: .4rem 0 .4rem; font-size: .9rem; }
        caption { text-align: left; margin-bottom: .35rem; font-size: .8rem; font-weight: 600; color: var(--muted); }
        th, td { padding: .5rem .7rem; border-bottom: 1px solid var(--line); vertical-align: top; text-align: left; }
        thead th { background: var(--band); color: var(--navy); font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; border-top: 1px solid var(--line); border-bottom: 2px solid var(--navy); }
        tbody tr:nth-child(even) { background: #fafbfc; }
        tbody th { font-weight: 600; }
        td.num, th.num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        tr.muted td, tr.muted th { color: var(--muted); font-style: italic; }
        small { color: var(--muted); font-weight: 400; }
        .note { margin: .3rem 0 .75rem; font-size: .825rem; color: var(--muted); }
        .bars { list-style: none; margin: .5rem 0 .75rem; padding: 0; }
        .bars li { display: grid; grid-template-columns: 14rem 1fr 3.25rem; gap: .7rem; align-items: center; margin: .35rem 0; font-size: .9rem; }
        .bar { height: .7rem; background: var(--band); border-radius: 2px; overflow: hidden; }
        .bar span { display: block; height: 100%; background: var(--accent); print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .pct { text-align: right; font-variant-numeric: tabular-nums; color: var(--muted); }
        .notes { margin-top: 2.25rem; padding-top: .6rem; border-top: 1px solid var(--line); font-size: .8rem; color: var(--muted); }
        .notes h2 { margin: 0 0 .3rem; padding: 0; background: none; color: var(--muted); border-radius: 0; font: 700 .72rem "Segoe UI", system-ui, sans-serif; letter-spacing: .08em; text-transform: uppercase; }
        .notes ol { margin: 0; padding-left: 1.1rem; }
        .notes li { margin: .2rem 0; }
        footer { margin-top: 2rem; padding-top: .6rem; border-top: 1px solid var(--line); font-size: .75rem; color: var(--muted); display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        @media screen and (max-width: 40rem) { .cards { grid-template-columns: repeat(2, 1fr); } .letterhead { flex-wrap: wrap; } .bars li { grid-template-columns: 1fr 3.25rem; } .bars .bar { grid-column: 1 / -1; } }
        @page { margin: 14mm; }
        @media print {
            body { max-width: none; padding: 0; font-size: 10pt; }
            button { display: none; }
            .table-scroll { overflow: visible; }
            h2, h3 { break-after: avoid; }
            tr, .card, .cards, .summary, .alert, .bars li, .notes { break-inside: avoid; }
            caption, .note { break-after: avoid; }
            thead { display: table-header-group; }
            .cards { grid-template-columns: repeat(4, 1fr); gap: .5rem; }
            .card { padding: .5rem .6rem; }
            .card .label { font-size: .6rem; letter-spacing: .04em; }
            .card .value { font-size: 1.4rem; }
            .card .hint { font-size: .7rem; }
            .meta { grid-template-columns: repeat(3, 1fr); }
            .bars li { grid-template-columns: 13rem 1fr 3rem; }
            .bars .bar { grid-column: auto; }
            h3 { margin-top: 1.4rem; }
            section.period { margin-top: 1.5rem; }
            h2, thead th, .meta, tbody tr:nth-child(even), .alert, tr.muted td { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
@php
    $pct = fn ($part, $whole) => $whole ? round($part / $whole * 100) : 0;
    $issueNames = [
        'cleanliness_hygiene' => 'Cleanliness / Hygiene',
        'communication_information' => 'Communication / Information',
        'billing_payment' => 'Billing / Payment',
        'privacy_confidentiality' => 'Privacy / Confidentiality',
        'accessibility_facility' => 'Accessibility / Facility',
    ];
    $issueLabel = fn ($category) => $issueNames[$category] ?? ucwords(str_replace('_', ' ', $category));
    $ratingWord = fn ($avg) => $avg === null ? '' : ($avg >= 4.5 ? 'Excellent' : ($avg >= 4 ? 'Good' : ($avg >= 3 ? 'Fair' : 'Needs attention')));
    $low = $report['low_rating_max'];
    $doctorFilter = $report['doctor'] ?? 'All doctors';
    $specialtyFilter = $report['specialty'] ?? 'All specialties';
    $generated = now('Asia/Manila')->format('M j, Y, g:i A');
@endphp
    <header class="letterhead">
        <div class="letterhead-text">
            <p class="org">OptiSolutions &middot; Patient Feedback Analysis</p>
            <h1>Diagnostic Report</h1>
            <p class="subtitle">Summary of patient reviews and complaints for administrative review</p>
        </div>
        <button type="button" class="print-btn" onclick="window.print()">Print / Save as PDF</button>
    </header>

    <dl class="meta">
        <div><dt>Doctor filter</dt><dd>{{ $doctorFilter }}</dd></div>
        <div><dt>Specialty filter</dt><dd>{{ $specialtyFilter }}</dd></div>
        <div><dt>Generated</dt><dd>{{ $generated }} (PHT)</dd></div>
    </dl>

    @unless($report['available'])
        <div class="alert" role="status">
            <strong>Analysis in progress.</strong> Issue analysis is pending for {{ $report['pending_issues'] }} response(s). Issue counts below only include the results available so far.
        </div>
    @endunless

    @foreach($report['periods'] as $period)
        @php
            $o = $period['overall'];
            $complaintTotal = $period['complaints'];
            $complaintRanks = collect($period['complaint_categories'])->sortDesc();
            $issueRanks = collect($o['categories'])
                ->map(fn ($c, $k) => ['key' => $k] + $c)
                ->sortByDesc(fn ($c) => [$c['negative'], $c['low']])->values();
            $topReviewIssue = $issueRanks->first(fn ($c) => $c['negative'] > 0);
            $topComplaint = $complaintRanks->isNotEmpty() ? [$complaintRanks->keys()->first(), $complaintRanks->first()] : null;
            $uncategorised = $o['negative'] - ($o['negative_categorised'] ?? 0);
            $doctorGroups = collect($period['doctors']);
            $serviceGroups = collect($period['services']);
            $unlinkedDoctor = $doctorGroups->firstWhere('label', 'Doctor not linked')['reviews'] ?? 0;
            $unlinkedService = $serviceGroups->firstWhere('label', 'Service not linked')['reviews'] ?? 0;
            $mostlyUnlinked = $o['reviews'] > 0 && max($unlinkedDoctor, $unlinkedService) / $o['reviews'] > 0.5;
        @endphp
        <section class="period" aria-labelledby="period-{{ $loop->index }}">
            <h2 id="period-{{ $loop->index }}">Reporting period: {{ $period['label'] }}</h2>

            @if(!$o['reviews'] && !$complaintTotal)
                <p>No reviews or complaints were found for this period and filter.</p>
                </section>
                @continue
            @endif

            <div class="summary">
                <h3>Executive summary</h3>
                <ul>
                    @if($o['reviews'])
                        <li>Patients rate the clinic <strong>{{ $o['average'] === null ? 'N/A' : number_format($o['average'], 1) . ' out of 5' }}</strong> on average{{ $o['average'] !== null ? ' (' . strtolower($ratingWord($o['average'])) . ')' : '' }}, based on {{ $o['rated'] }} rated {{ Str::plural('review', $o['rated']) }}.</li>
                        <li><strong>{{ $o['low'] }} of {{ $o['reviews'] }}</strong> {{ Str::plural('review', $o['reviews']) }} ({{ $pct($o['low'], $o['reviews']) }}%) gave a low rating of 1–{{ $low }} stars{{ $o['low_without_comment'] ? '; ' . $o['low_without_comment'] . ' of them left no comment, so the reason is unknown' : '' }}.</li>
                        <li><strong>{{ $o['negative'] }}</strong> {{ Str::plural('review', $o['negative']) }} contained a negative comment.</li>
                    @else
                        <li>No reviews were submitted in this period.</li>
                    @endif
                    @if($topReviewIssue)
                        <li>Most common issue in negative review comments: <strong>{{ $issueLabel($topReviewIssue['key']) }}</strong> ({{ $topReviewIssue['negative'] }} {{ Str::plural('comment', $topReviewIssue['negative']) }}).</li>
                    @endif
                    @if($topComplaint)
                        <li>Most common complaint: <strong>{{ $issueLabel($topComplaint[0]) }}</strong>, {{ $topComplaint[1] }} of {{ $complaintTotal }} complaints ({{ $pct($topComplaint[1], $complaintTotal) }}%).</li>
                    @endif
                    @if($mostlyUnlinked)
                        @php $linked = $o['reviews'] - max($unlinkedDoctor, $unlinkedService); @endphp
                        <li>{{ $linked > 0 ? 'Only ' . $linked . ' of ' . $o['reviews'] : 'None of the ' . $o['reviews'] }} reviews are linked to a doctor or service, so results by doctor or service are not yet reliable.</li>
                    @endif
                </ul>
            </div>
            <p class="note">Notes on how to read these figures appear at the end of the report.</p>

            @if($o['reviews'])
                <h3><span class="no">1.</span>Patient review indicators</h3>
                <div class="cards">
                    <div class="card">
                        <div class="label">Total reviews</div>
                        <div class="value">{{ $o['reviews'] }}</div>
                        <div class="hint">{{ $o['rated'] }} with a star rating</div>
                    </div>
                    <div class="card">
                        <div class="label">Average rating</div>
                        <div class="value">{{ $o['average'] === null ? 'N/A' : number_format($o['average'], 1) . ' / 5' }}</div>
                        <div class="hint">{{ $ratingWord($o['average']) }}</div>
                    </div>
                    <div class="card">
                        <div class="label">Low ratings (1–{{ $low }} stars)</div>
                        <div class="value">{{ $o['low'] }}</div>
                        <div class="hint">{{ $pct($o['low'], $o['reviews']) }}% of reviews · {{ $o['low_without_comment'] }} without a comment</div>
                    </div>
                    <div class="card">
                        <div class="label">Negative comments</div>
                        <div class="value">{{ $o['negative'] }}</div>
                        <div class="hint">{{ $pct($o['negative'], $o['reviews']) }}% of reviews · comments read as negative</div>
                    </div>
                </div>

                <h3><span class="no">2.</span>Issues identified in review comments</h3>
                @if($issueRanks->isNotEmpty())
                    <div class="table-scroll">
                        <table>
                            <caption>Issue categories, most frequent first</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Issue</th>
                                    <th scope="col" class="num">Negative comments</th>
                                    <th scope="col" class="num">Low-rated reviews mentioning it</th>
                                    <th scope="col">Stars given on negative comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($issueRanks as $row)
                                    <tr>
                                        <th scope="row">{{ $issueLabel($row['key']) }}</th>
                                        <td class="num">{{ $row['negative'] }}</td>
                                        <td class="num">{{ $row['low'] }}</td>
                                        <td>
                                            @forelse(collect($row['negative_ratings'])->sortKeys() as $rating => $count)
                                                {{ $rating === 'Unrated' ? 'No rating' : $rating . '/5' }} ({{ $count }}){{ $loop->last ? '' : ', ' }}
                                            @empty
                                                N/A
                                            @endforelse
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="note">
                        The two count columns are tallied separately, so one review can appear in both.
                        @if($o['negative'] && $report['available'])
                            {{ $o['negative'] - $uncategorised }} of {{ $o['negative'] }} negative {{ Str::plural('comment', $o['negative']) }} matched an issue category; {{ $uncategorised }} did not match any, so this table does not cover every negative comment.
                        @endif
                    </p>
                @elseif($report['available'])
                    <p>No issue categories matched in negative or low-rated review comments.</p>
                @endif

                <h3><span class="no">3.</span>Results by doctor and by service</h3>
                @if($mostlyUnlinked)
                    <div class="alert">
                        <strong>Limited data.</strong> Most reviews are not linked to a doctor or service
                        ({{ $unlinkedDoctor }} of {{ $o['reviews'] }} have no doctor, {{ $unlinkedService }} of {{ $o['reviews'] }} have no service).
                        Use these tables as a rough guide only, and avoid comparing doctors or services from them.
                    </div>
                @endif
                @foreach(['Doctor' => $doctorGroups, 'Service' => $serviceGroups] as $kind => $groups)
                    <div class="table-scroll">
                        <table>
                            <caption>By {{ strtolower($kind) }}</caption>
                            <thead>
                                <tr>
                                    <th scope="col">{{ $kind }}</th>
                                    <th scope="col" class="num">Reviews</th>
                                    <th scope="col" class="num">Average rating</th>
                                    <th scope="col" class="num">Low ratings</th>
                                    <th scope="col" class="num">Negative comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groups as $group)
                                    @php $notLinked = str_ends_with($group['label'], 'not linked'); @endphp
                                    <tr @class(['muted' => $notLinked])>
                                        <th scope="row">
                                            {{ $group['label'] }}
                                            @if($group['reviews'] < 5 && !$notLinked)<br><small>Few reviews; interpret with caution</small>@endif
                                        </th>
                                        <td class="num">{{ $group['reviews'] }}</td>
                                        <td class="num">{{ $group['average'] === null ? 'N/A' : number_format($group['average'], 1) . ' / 5' }}</td>
                                        <td class="num">{{ $group['low'] }}</td>
                                        <td class="num">{{ $group['negative'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
                <p class="note">&ldquo;Not linked&rdquo; means the review is not connected to a visit, so the doctor or service is unknown.</p>
            @endif

            <h3><span class="no">4.</span>Clinic-wide complaints</h3>
            <p class="note">
                {{ $complaintTotal }} {{ Str::plural('complaint', $complaintTotal) }} received. Complaints are separate from reviews and are not tied to a visit,
                so they describe the whole clinic and are not added to the review figures above.
            </p>
            @if($report['available'] || $complaintRanks->isNotEmpty())
                @php $maxComplaint = $complaintRanks->max() ?: 1; @endphp
                <ul class="bars">
                    @forelse($complaintRanks as $category => $count)
                        <li>
                            <span>{{ $issueLabel($category) }} ({{ $count }})</span>
                            <span class="bar" aria-hidden="true"><span style="width: {{ round($count / $maxComplaint * 100) }}%"></span></span>
                            <span class="pct">{{ $pct($count, $complaintTotal) }}%</span>
                        </li>
                    @empty
                        <li>No issue categories matched in complaints.</li>
                    @endforelse
                </ul>
                @if($complaintRanks->isNotEmpty())
                    <p class="note">Percentages show each issue as a share of all {{ $complaintTotal }} complaints. A complaint can match more than one issue, so the total may exceed 100%.</p>
                @endif
            @endif
        </section>
    @endforeach

    <section class="notes" aria-labelledby="notes-title">
        <h2 id="notes-title">Notes</h2>
        <ol>
            <li><strong>Low rating</strong> means 1 to {{ $low }} stars, with or without a comment. <strong>Negative comment</strong> means a comment the system classified as negative, at any star rating. One review can be both, so do not add the two figures together.</li>
            <li><strong>Issue categories</strong> are assigned by keyword matching. They are indicators for further investigation, not confirmed findings.</li>
            <li><strong>Complaints</strong> are separate from reviews. They have no star rating and are not part of the review figures.</li>
            <li>All periods use Philippine time.</li>
        </ol>
    </section>

    <footer>
        <span>OptiSolutions &middot; Diagnostic Report</span>
        <span>Confidential &mdash; for authorized administrative use only</span>
    </footer>
</body>
</html>