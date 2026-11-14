<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnostic Report | OptiSolutions</title>
    <style>
        body { max-width: 70rem; margin: 2rem auto; padding: 0 1rem; color: #062744; font: 1rem/1.5 system-ui, sans-serif; }
        h1, h2, h3 { line-height: 1.25; }
        h2 { border-bottom: 1px solid #b7c9d8; padding-bottom: .5rem; }
        section { margin: 2.5rem 0; }
        button { padding: .6rem 1rem; background: #0e62aa; color: #fff; border: 0; border-radius: 6px; font: inherit; cursor: pointer; }
        .table-scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        caption { text-align: left; margin-bottom: .5rem; font-weight: 600; }
        th, td { padding: .5rem .65rem; border: 1px solid #b7c9d8; vertical-align: top; text-align: left; }
        thead th { background: #eef5fc; }
        @media print {
            body { margin: 0; padding: 0; color: #111; font-size: 10pt; }
            button { display: none; }
            .table-scroll { overflow: visible; }
            h2, h3 { break-after: avoid; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <h1>Diagnostic Report</h1>
    <p>Review filters: {{ $report['doctor'] ?? 'All doctors' }} · {{ $report['specialty'] ?? 'All specialties' }}</p>
    <p>Periods use Philippine time. Low ratings are 1–{{ $report['low_rating_max'] }} stars, including ratings submitted without a comment.</p>
    <p>Issue categories come from keyword matches and are indicators for further investigation. A response can match several categories. A negative comment with a low rating can appear in both counts.</p>
    <button type="button" onclick="window.print()">Print / Save as PDF</button>

    @unless($report['available'])
        <p role="status">Issue analysis is pending for {{ $report['pending_issues'] }} response(s). Issue counts reflect the results available so far.</p>
    @endunless

    @php
        $issueLabel = fn ($category) => ucwords(str_replace('_', ' ', $category));
    @endphp
    @foreach($report['periods'] as $period)
        <section aria-labelledby="period-{{ $loop->index }}">
            <h2 id="period-{{ $loop->index }}">{{ $period['label'] }}</h2>
            @foreach(['All selected reviews' => [$period['overall'] + ['label' => 'Overall']], 'By doctor' => $period['doctors'], 'By service' => $period['services']] as $title => $groups)
                <div class="table-scroll">
                    <table>
                        <caption>{{ $title }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Group</th>
                                <th scope="col">Reviews</th>
                                <th scope="col">Rated reviews</th>
                                <th scope="col">Average rating</th>
                                <th scope="col">Low ratings</th>
                                <th scope="col">Low ratings without comments</th>
                                <th scope="col">Negative comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($groups as $group)
                                <tr>
                                    <th scope="row">{{ $group['label'] }}</th>
                                    <td>{{ $group['reviews'] }}</td>
                                    <td>{{ $group['rated'] }}</td>
                                    <td>{{ $group['average'] === null ? 'N/A' : number_format($group['average'], 1) . ' / 5' }}</td>
                                    <td>{{ $group['low'] }}</td>
                                    <td>{{ $group['low_without_comment'] }}</td>
                                    <td>{{ $group['negative'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7">No reviews in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(collect($groups)->contains(fn ($group) => $group['categories'] !== []))
                    <div class="table-scroll">
                        <table>
                            <caption>Issue categories · {{ $title }}</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Group</th>
                                    <th scope="col">Issue category</th>
                                    <th scope="col">Negative comments</th>
                                    <th scope="col">Ratings on negative comments</th>
                                    <th scope="col">Comments with low ratings</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groups as $group)
                                    @foreach($group['categories'] as $category => $counts)
                                        <tr>
                                            <th scope="row">{{ $group['label'] }}</th>
                                            <td>{{ $issueLabel($category) }}</td>
                                            <td>{{ $counts['negative'] }}</td>
                                            <td>
                                                @forelse(collect($counts['negative_ratings'])->sortKeys() as $rating => $count)
                                                    {{ $rating === 'Unrated' ? $rating : $rating . '/5' }} ({{ $count }}){{ $loop->last ? '' : ', ' }}
                                                @empty
                                                    —
                                                @endforelse
                                            </td>
                                            <td>{{ $counts['low'] }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif($report['available'] && $loop->first)
                    <p>No issue categories matched in negative or low-rated review comments.</p>
                @endif
            @endforeach

            <h3>Clinic-wide complaints</h3>
            <p>{{ $period['complaints'] }} complaint(s). Standalone complaints have no visit link, so this section covers the entire clinic.</p>
            @if($report['available'] || $period['complaint_categories'])
                <ul>
                    @forelse($period['complaint_categories'] as $category => $count)
                        <li>{{ $issueLabel($category) }} ({{ $count }})</li>
                    @empty
                        <li>No issue categories matched in complaints.</li>
                    @endforelse
                </ul>
            @endif
        </section>
    @endforeach
</body>
</html>
