    @php
        $previous = $diagnostic['periods']['previous'];
        $current = $diagnostic['periods']['current'];
        $labelFor = fn ($category) => ucwords(str_replace('_', ' ', $category));
        $change = $current['negative'] - $previous['negative'];
        $topCount = $current['categories'] ? max($current['categories']) : 0;
        $topCategories = array_keys(array_filter($current['categories'], fn ($count) => $count === $topCount));
    @endphp
    <p class="diagnostic-overview">
        <strong class="{{ $change > 0 ? 'diagnostic-increase' : ($change < 0 ? 'diagnostic-decrease' : 'diagnostic-unchanged') }}">
            @if($change > 0)
                Negative comments went up from {{ $previous['negative'] }} to {{ $current['negative'] }} this week.
            @elseif($change < 0)
                Negative comments went down from {{ $previous['negative'] }} to {{ $current['negative'] }} this week.
            @else
                Negative comments stayed at {{ $current['negative'] }} this week.
            @endif
        </strong>
        @if(!$diagnostic['available'])
            Problem analysis is currently unavailable or incomplete.
        @elseif($topCount > 0)
            {{ implode(', ', array_map($labelFor, $topCategories)) }} {{ count($topCategories) > 1 ? 'are tied as the most mentioned problems' : 'is the most mentioned problem' }}:
            {{ $topCount }} of {{ $current['negative'] }} negative comments this week
            (last week: @foreach($topCategories as $topCategory){{ $loop->first ? '' : '; ' }}{{ count($topCategories) > 1 ? $labelFor($topCategory) . ' ' : '' }}{{ $previous['categories'][$topCategory] ?? 0 }} of {{ $previous['negative'] }}@endforeach).
        @elseif($current['negative'] > 0)
            No problems were matched in this week's negative comments.
        @endif
    </p>
    <div class="diagnostic-scroll">
        <table class="diagnostic-table">
            <caption>Review comparison for the selected doctor and specialty</caption>
            <thead><tr><th scope="col">Measure</th><th scope="col">{{ $previous['label'] }}</th><th scope="col">{{ $current['label'] }}</th></tr></thead>
            <tbody>
                <tr><th scope="row">Negative comments</th><td>{{ $previous['negative'] }}</td><td>{{ $current['negative'] }}</td></tr>
                <tr><th scope="row">All classified comments</th><td>{{ $previous['classified'] }}</td><td>{{ $current['classified'] }}</td></tr>
                <tr><th scope="row">Share that were negative</th><td>{{ $previous['negative_rate'] === null ? 'N/A' : number_format($previous['negative_rate'], 1).'%' }}</td><td>{{ $current['negative_rate'] === null ? 'N/A' : number_format($current['negative_rate'], 1).'%' }}</td></tr>
                @if($previous['pending'] > 0 || $current['pending'] > 0)
                    <tr><th scope="row">Pending or unclassified comments</th><td>{{ $previous['pending'] }}</td><td>{{ $current['pending'] }}</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    <p>
        Negative comments: {{ $previous['negative'] }} → {{ $current['negative'] }}
        @if($change === 0)
            (no change).
        @else
            ({{ $change > 0 ? 'up' : 'down' }} by {{ abs($change) }}).
        @endif
        @if($current['negative_rate'] !== null && $previous['negative_rate'] !== null)
            Share that were negative: {{ number_format($previous['negative_rate'], 1) }}% → {{ number_format($current['negative_rate'], 1) }}%.
        @else
            Share that were negative: not enough classified comments to compare both weeks.
        @endif
    </p>
    <p class="diagnostic-note">{{ $diagnostic['unlinked'] }} review(s) across both weeks lack a doctor or specialty link. Reviews missing the selected link are excluded when that filter is applied.
        @if($diagnosticStaff ?? false)
            Complaints are reviewed separately by an administrator on the Feedback page.
        @else
            Complaints are reviewed separately on the <a href="{{ route('admin_acc.feedback.index') }}">Feedback page</a>.
        @endif
    </p>
    @if(!$diagnostic['available'])
        <p role="status">Concern analysis is unavailable or incomplete. Review counts remain available; concern comparisons cannot be generated. Try Compare again later.</p>
    @else
        <p class="diagnostic-note">Unmatched negative reviews: {{ $previous['unmatched'] }} previously; {{ $current['unmatched'] }} currently. One review can mention several concerns, so concern shares may total more than 100%.</p>
        @if($current['negative'] < 5 || $previous['negative'] < 5)
            <p class="diagnostic-note">At least one period has fewer than five negative reviews. Small numbers make comparisons sensitive to individual submissions.</p>
        @endif
        @forelse($diagnostic['categories'] as $category)
            @php
                $before = $previous['categories'][$category] ?? 0;
                $after = $current['categories'][$category] ?? 0;
            @endphp
            <article class="diagnostic-finding">
                <h4>{{ $labelFor($category) }}</h4>
                <p class="diagnostic-trend {{ $after > $before ? 'diagnostic-increase' : ($after < $before ? 'diagnostic-decrease' : 'diagnostic-unchanged') }}">
                    @if($after > $before)
                        ↑ Mentioned more than last week
                    @elseif($after < $before)
                        ↓ Mentioned less than last week
                    @else
                        → Mentioned as often as last week
                    @endif
                </p>
                <p>Mentioned in {{ $after }} of {{ $current['negative'] }} negative comments this week (last week: {{ $before }} of {{ $previous['negative'] }}).</p>
                <details>
                    <summary>View comments ({{ $after }} this week, {{ $before }} last week)</summary>
                    @foreach(['current' => $current, 'previous' => $previous] as $period)
                        @foreach($period['comments'] as $comment)
                            @if(in_array($category, $comment['categories'], true))
                                <div class="diagnostic-comment">
                                    <p class="diagnostic-comment-meta"><strong>{{ $period['label'] }} · Review #{{ $comment['id'] }}</strong> · {{ $comment['date'] }}<br>{{ $comment['doctor'] ?: 'Doctor not linked' }} · {{ $comment['specialty'] ?: 'Specialty not linked' }}</p>
                                    <blockquote>{{ $comment['text'] }}</blockquote>
                                    @foreach($comment['findings'] as $finding)
                                        @if(($finding['category'] ?? '') === $category)
                                            <p class="diagnostic-evidence">Reported reason: {{ $finding['reported_reason'] ?? 'Unspecified' }}</p>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    @endforeach
                </details>
            </article>
        @empty
            <p>No matched concerns in negative reviews for these periods and filters.</p>
        @endforelse
    @endif
    <p class="diagnostic-note">Findings reflect lexicon matches and patient reports. They do not establish operational causes, severity, or a doctor's responsibility.</p>

