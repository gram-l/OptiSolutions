    @php
        $previous = $diagnostic['periods']['previous'];
        $current = $diagnostic['periods']['current'];
        $labelFor = fn ($category) => ucwords(str_replace('_', ' ', $category));
        $percent = fn ($count, $total) => $total ? number_format($count / $total * 100, 1) . '%' : 'N/A';
    @endphp
    <div class="diagnostic-scroll">
        <table class="diagnostic-table">
            <caption>Review comparison for the selected doctor and specialty</caption>
            <thead><tr><th scope="col">Measure</th><th scope="col">{{ $previous['label'] }}</th><th scope="col">{{ $current['label'] }}</th></tr></thead>
            <tbody>
                <tr><th scope="row">Negative written reviews</th><td>{{ $previous['negative'] }}</td><td>{{ $current['negative'] }}</td></tr>
                <tr><th scope="row">All classified written reviews</th><td>{{ $previous['classified'] }}</td><td>{{ $current['classified'] }}</td></tr>
                <tr><th scope="row">Negative-review rate</th><td>{{ $previous['negative_rate'] === null ? 'N/A' : $previous['negative_rate'].'%' }}</td><td>{{ $current['negative_rate'] === null ? 'N/A' : $current['negative_rate'].'%' }}</td></tr>
                <tr><th scope="row">Pending or unclassified written reviews</th><td>{{ $previous['pending'] }}</td><td>{{ $current['pending'] }}</td></tr>
                <tr><th scope="row">Rating-only submissions (excluded)</th><td>{{ $previous['rating_only'] }}</td><td>{{ $current['rating_only'] }}</td></tr>
            </tbody>
        </table>
    </div>
    <p>
        @if($previous['negative'] > 0)
            Negative-review count changed by {{ number_format(($current['negative'] - $previous['negative']) / $previous['negative'] * 100, 1) }}%.
        @else
            Negative-review count change: N/A (the previous count is zero).
        @endif
        @if($current['negative_rate'] !== null && $previous['negative_rate'] !== null)
            The negative-review rate changed by {{ number_format($current['negative_rate'] - $previous['negative_rate'], 1) }} percentage points.
        @else
            There are insufficient classified reviews to compare rates.
        @endif
    </p>
    <p>{{ $diagnostic['unlinked'] }} review(s) across both weeks lack a doctor or specialty link. Reviews missing the selected link are excluded when that filter is applied. Complaints are reviewed separately on the <a href="{{ route('admin_acc.feedback.index') }}">Feedback page</a>.</p>
    @if(!$diagnostic['available'])
        <p role="status">Concern analysis is unavailable or incomplete. Review counts remain available; concern comparisons cannot be generated. Try Compare again later.</p>
    @else
        <p>Unmatched negative reviews: {{ $previous['unmatched'] }} previously; {{ $current['unmatched'] }} currently. One review can mention several concerns, so concern shares may total more than 100%.</p>
        @if($current['negative'] < 5 || $previous['negative'] < 5)
            <p>At least one period has fewer than five negative reviews. Small numbers make comparisons sensitive to individual submissions.</p>
        @endif
        @forelse($diagnostic['categories'] as $category)
            @php
                $before = $previous['categories'][$category] ?? 0;
                $after = $current['categories'][$category] ?? 0;
                $increasedShare = $previous['negative'] > 0 && $current['negative'] > 0
                    && $after / $current['negative'] > $before / $previous['negative'];
            @endphp
            <article class="diagnostic-finding">
                <h4>{{ $labelFor($category) }}</h4>
                <p>{{ $labelFor($category) }} appeared in {{ $after }} of {{ $current['negative'] }} negative reviews ({{ $percent($after, $current['negative']) }}) in {{ $current['label'] }}, compared with {{ $before }} of {{ $previous['negative'] }} ({{ $percent($before, $previous['negative']) }}) in {{ $previous['label'] }}.</p>
                <p>
                    @if(!$previous['negative'] || !$current['negative'])
                        A period has no negative reviews, so changes in concern share cannot be assessed.
                    @elseif($increasedShare && $current['negative_rate'] > $previous['negative_rate'])
                        This concern became more common within negative reviews while the negative-review rate increased. It may help explain the change in dissatisfaction and warrants investigation.
                    @elseif($increasedShare)
                        This concern became more common within negative reviews, although the overall negative-review rate did not increase.
                    @else
                        This concern's share of negative reviews did not increase.
                    @endif
                </p>
                <details>
                    <summary>View supporting comments — {{ $after + $before }} review(s)</summary>
                    @foreach(['current' => $current, 'previous' => $previous] as $period)
                        @foreach($period['comments'] as $comment)
                            @if(in_array($category, $comment['categories'], true))
                                <div class="diagnostic-comment">
                                    <p><strong>{{ $period['label'] }} · Review #{{ $comment['id'] }}</strong> · {{ $comment['date'] }}<br>{{ $comment['doctor'] ?: 'Doctor not linked' }} · {{ $comment['specialty'] ?: 'Specialty not linked' }}</p>
                                    <blockquote>{{ $comment['text'] }}</blockquote>
                                    @foreach($comment['findings'] as $finding)
                                        @if(($finding['category'] ?? '') === $category)
                                            <p>Reported reason: {{ $finding['reported_reason'] ?? 'Unspecified' }}<br>Evidence: “{{ $finding['evidence'] ?? '' }}”</p>
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
    <p>Findings reflect lexicon matches and patient reports. They do not establish operational causes, severity, or a doctor's responsibility.</p>

