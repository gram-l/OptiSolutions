<div class="chart-card rating-leaders-card">
    <div class="chart-header">
        <div class="chart-header-title">
            <div class="chart-icon-badge"><i class="bi bi-star"></i></div>
            <h3>Star Ratings Summary</h3>
        </div>
    </div>
    <p class="chart-subtitle">Top 3 by average star rating · All time</p>
    <div class="rating-leaders-scroll" tabindex="0" role="region" aria-label="Leading doctor and service ratings">
    <div class="rating-leaders-columns">
        @foreach(['Doctors' => ($leadingDoctors ?? []), 'Services' => ($leadingServices ?? [])] as $heading => $leaders)
            <section>
                <h4>{{ $heading }}</h4>
                <ol id="rating-leaders-{{ strtolower($heading) }}" class="rating-leaders-list">
                    @forelse($leaders as $leader)
                        <li><span class="rating-leader-name">{{ $loop->iteration }}. {{ $leader->name }}</span>
                            <span class="rating-leader-score"><i class="bi bi-star-fill" aria-hidden="true"></i> {{ number_format($leader->average_rating, 1) }}<span class="rating-leader-count"> / 5 ratings</span></span>
                        </li>
                    @empty
                        <li class="rating-leaders-empty">No linked ratings yet.</li>
                    @endforelse
                </ol>
            </section>
        @endforeach
    </div>
    <p class="rating-leaders-note">Based on ratings linked to a scheduled visit. Equal averages are ordered by rating count.</p>
    </div>
</div>
<style>
    .chart-card.rating-leaders-card { display:flex; flex-direction:column; min-height:0; min-width:0; contain:size; align-self:stretch; overflow:hidden; }
    .rating-leaders-card > .chart-header,.rating-leaders-card > .chart-subtitle { flex-shrink:0; }
    .rating-leaders-scroll { flex:1; min-height:0; overflow-y:auto; overscroll-behavior:contain; padding-right:.4rem; scrollbar-width:thin; }
    .rating-leaders-scroll:focus-visible { outline:2px solid #0e62aa; outline-offset:-2px; }
    .rating-leaders-columns { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1.5rem; }
    .rating-leaders-columns h4 { color:#062744; margin:.5rem 0; font-size:1rem; }
    .rating-leaders-list { list-style:none; padding:0; margin:0; }
    .rating-leaders-list li { display:flex; flex-direction:column; gap:.3rem; padding:.7rem 0; border-bottom:1px solid #e2e8f0; }
    .rating-leader-name { color:#334155; font-size:.9rem; overflow-wrap:anywhere; }
    .rating-leader-score { color:#8a5700; font-weight:700; font-size:.9rem; }
    .rating-leader-count,.rating-leaders-note,.rating-leaders-empty { color:#64748b; font-size:.8rem; font-weight:400; }
    .rating-leaders-note { margin:1rem 0 0; }
    body.dark-mode .rating-leaders-columns h4,body.dark-mode .rating-leader-name { color:#d9e5f5; }
    body.dark-mode .rating-leader-score { color:#f5ce73; }
    body.dark-mode :is(.rating-leader-count,.rating-leaders-note,.rating-leaders-empty) { color:#adbdcf; }
    @media(max-width:600px) { .rating-leaders-columns { grid-template-columns:1fr; gap:1rem; } }
    @media(max-width:900px) { .chart-card.rating-leaders-card { contain:none; height:400px; box-sizing:border-box; } }
</style>
