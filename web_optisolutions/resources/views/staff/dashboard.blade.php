@extends('staff.layouts.app')

@section('content')
<div class="container">
<div class="dash-page">

    @php
        // ---- Service Distribution defaults ----
        $hasServiceData = isset($serviceDistribution) && $serviceDistribution->count() > 0;
        $serviceLabels = $hasServiceData ? $serviceDistribution->pluck('service_type') : collect(['No data']);
        $serviceTotals = $hasServiceData ? $serviceDistribution->pluck('total') : collect([1]);
        $serviceColors = ['#0E62AA', '#3969A8', '#5C8BC0', '#79A8CB', '#9DBCD4', '#00897B', '#43A047', '#FB8C00'];

        // ---- Weekly Patient Visits defaults ----
        $defaultWeekLabels = ['W-5', 'W-4', 'W-3', 'W-2', 'W-1', 'This wk'];
        $hasWeeklyData = isset($weeklyVisits) && count($weeklyVisits) > 0;
        $weeklyVisitsData = $hasWeeklyData ? $weeklyVisits : array_fill(0, 6, 0);
        $weeklyVisitsLabels = $weekLabels ?? $defaultWeekLabels;

        // ---- Patient Feedback defaults ----
        $sentimentTotal = ($positivePercent ?? 0) + ($neutralPercent ?? 0) + ($negativePercent ?? 0);
        $sentimentRows = [
            ['label' => 'Positive', 'value' => $sentimentTotal > 0 ? ($positivePercent ?? 0) : 0, 'color' => '#0E62AA', 'emoji' => '😊', 'avatarClass' => 'sentiment-avatar-positive'],
            ['label' => 'Neutral',  'value' => $sentimentTotal > 0 ? ($neutralPercent ?? 0)  : 0, 'color' => '#9DBCD4', 'emoji' => '😐', 'avatarClass' => 'sentiment-avatar-neutral'],
            ['label' => 'Negative', 'value' => $sentimentTotal > 0 ? ($negativePercent ?? 0) : 0, 'color' => '#062744', 'emoji' => '😟', 'avatarClass' => 'sentiment-avatar-negative'],
        ];

        // ---- Inquiry Volume defaults ----
        $hasInquiryData = isset($inquiryVolumeByDay) && count($inquiryVolumeByDay) > 0;
        $inquiryVolumeData = $hasInquiryData ? $inquiryVolumeByDay : array_fill(0, 7, 0);

        // ---- Sentiment Trend defaults (empty until the controller supplies these) ----
        $sentimentTrendLabelsData   = $sentimentTrendLabels ?? $defaultWeekLabels;
        $sentimentTrendPositiveData = $sentimentTrendPositive ?? array_fill(0, 6, 0);
        $sentimentTrendNeutralData  = $sentimentTrendNeutral ?? array_fill(0, 6, 0);
        $sentimentTrendNegativeData = $sentimentTrendNegative ?? array_fill(0, 6, 0);
        $hasSentimentTrendData = (array_sum($sentimentTrendPositiveData) + array_sum($sentimentTrendNeutralData) + array_sum($sentimentTrendNegativeData)) > 0;

        // ---- Chat Inquiries Status defaults (empty until the controller supplies these) ----
        $resolvedInquiriesCount   = $resolvedInquiries ?? 0;
        $unresolvedInquiriesCount = $unresolvedInquiries ?? 0;
        $hasInquiryStatusData = ($resolvedInquiriesCount + $unresolvedInquiriesCount) > 0;

        // ---- Recent Activities defaults ----
        $recentActivities = $recentActivities ?? [];
    @endphp

    <!-- WELCOME BANNER -->
    <div class="welcome-banner">
        <div class="welcome-banner-text">
            <h2>Welcome, {{ auth()->user()->name ?? 'Staff' }}!</h2>
            <p>Here's what's happening at the clinic today</p>
        </div>
        <div class="welcome-banner-icon"><i class="bi bi-heart-pulse"></i></div>
    </div>

    <!-- STAT CARDS -->
    <div class="analytics-grid">
        <a href="{{ route('staff.appointments') }}" class="analytics-card">
            <div class="card-top-row">
                <div class="card-icon-title">
                    <div class="card-icon-badge"><i class="bi bi-calendar-check"></i></div>
                    <span class="card-title">Total Schedule Visit</span>
                </div>
                <i class="bi bi-chevron-right card-chevron"></i>
            </div>
            <div class="card-value" id="stat-schedule-visit">{{ $totalScheduleVisit ?? 0 }}</div>
            <div class="card-subtitle"><span class="dot"></span> <span id="stat-schedule-visit-sub">Scheduled visits</span></div>
        </a>

        <a href="{{ route('staff.inquiries') }}" class="analytics-card">
            <div class="card-top-row">
                <div class="card-icon-title">
                    <div class="card-icon-badge"><i class="bi bi-file-earmark-text"></i></div>
                    <span class="card-title">Pending Inquiries</span>
                </div>
                <i class="bi bi-chevron-right card-chevron"></i>
            </div>
            <div class="card-value" id="stat-pending-inquiries">{{ $pendingInquiries ?? 0 }}</div>
            <div class="card-subtitle"><span class="dot"></span> <span id="stat-pending-inquiries-sub">Awaiting reply</span></div>
        </a>

        <a href="{{ route('staff.doctors') }}" class="analytics-card">
            <div class="card-top-row">
                <div class="card-icon-title">
                    <div class="card-icon-badge"><i class="bi bi-person-badge"></i></div>
                    <span class="card-title">Active Doctors</span>
                </div>
                <i class="bi bi-chevron-right card-chevron"></i>
            </div>
            <div class="card-value" id="stat-active-doctors">{{ $activeDoctors ?? 0 }}</div>
            <div class="card-subtitle"><span class="dot"></span> <span id="stat-active-doctors-sub">Currently practicing</span></div>
        </a>

        <a href="{{ route('staff.patients') }}" class="analytics-card">
            <div class="card-top-row">
                <div class="card-icon-title">
                    <div class="card-icon-badge"><i class="bi bi-person"></i></div>
                    <span class="card-title">Registered Patients</span>
                </div>
                <i class="bi bi-chevron-right card-chevron"></i>
            </div>
            <div class="card-value" id="stat-total-patients">{{ $totalPatients ?? 0 }}</div>
            <div class="card-subtitle"><span class="dot"></span> <span id="stat-total-patients-sub">Total records</span></div>
        </a>
    </div>

    <!-- ROW 1: SERVICE DISTRIBUTION + PATIENT FEEDBACK -->
    <div class="charts-section">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-header-title">
                    <div class="chart-icon-badge"><i class="bi bi-pie-chart"></i></div>
                    <h3>Service Distribution</h3>
                </div>
            </div>
            <p class="chart-subtitle">Distribution of patient visits by service</p>

            <div class="donut-layout">
                <div class="donut-chart-wrap">
                    <canvas id="serviceDistributionChart"></canvas>
                </div>
                <div class="donut-legend" id="serviceDistributionList">
                    @if($hasServiceData)
                        @foreach($serviceDistribution as $i => $service)
                            <div class="donut-legend-row">
                                <span class="donut-legend-label">
                                    <span class="donut-legend-dot" style="background: {{ $serviceColors[$i % count($serviceColors)] }};"></span>
                                    {{ $service->service_type }}
                                </span>
                                <span class="donut-legend-value">{{ $service->total }} visits</span>
                            </div>
                        @endforeach
                    @else
                        <p class="chart-empty-note">No service data available yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-header-title">
                    <div class="chart-icon-badge"><i class="bi bi-emoji-smile"></i></div>
                    <h3>Patient Feedback Overview</h3>
                </div>
            </div>
            <p class="chart-subtitle">Breakdown of recent patient feedback</p>

            <div class="sentiment-bars" id="sentimentBars">
                @foreach($sentimentRows as $row)
                    <div class="sentiment-row" data-label="{{ $row['label'] }}" data-color="{{ $row['color'] }}">
                        <div class="sentiment-row-inner">
                            <div class="sentiment-avatar {{ $row['avatarClass'] }}">{{ $row['emoji'] }}</div>
                            <span class="sentiment-label">{{ $row['label'] }}</span>
                            <div class="bar-container">
                                <div class="sentiment-fill bar-fill" style="background: {{ $row['color'] }}; width: {{ $row['value'] }}%;"></div>
                            </div>
                            <span class="sentiment-value" style="color: {{ $row['color'] }};">{{ $row['value'] }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <p id="sentimentEmptyNote" class="chart-empty-note" style="{{ $sentimentTotal > 0 ? 'display:none;' : '' }}">
                No feedback data available yet.
            </p>
        </div>
    </div>

    <!-- ROW 2: INQUIRY VOLUME + PATIENT VISITS TREND -->
    <div class="charts-section">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-header-title">
                    <div class="chart-icon-badge"><i class="bi bi-bar-chart"></i></div>
                    <h3>Inquiry Volume</h3>
                </div>
                <span class="filter-btn">This Week</span>
            </div>
            <p class="chart-subtitle">Inquiries received per day this week</p>

            <canvas id="inquiryVolumeChart" height="220"></canvas>

            <p id="inquiryVolumeEmptyNote" class="chart-empty-note" style="{{ $hasInquiryData ? 'display:none;' : '' }}">
                No inquiry data available yet.
            </p>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-header-title">
                    <div class="chart-icon-badge"><i class="bi bi-graph-up"></i></div>
                    <h3>Patient Visits Trend</h3>
                </div>
                <span class="filter-btn">Last 6 Weeks</span>
            </div>
            <p class="chart-subtitle">Visits over the past 6 weeks</p>

            <canvas id="weeklyVisitsChart" height="220"></canvas>

            <p id="weeklyVisitsEmptyNote" class="chart-empty-note" style="{{ $hasWeeklyData ? 'display:none;' : '' }}">
                No visit data available yet.
            </p>
        </div>
    </div>

    <!-- ROW 3: SENTIMENT TREND + CHAT INQUIRIES STATUS -->
    <div class="charts-section">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-header-title">
                    <div class="chart-icon-badge"><i class="bi bi-graph-up-arrow"></i></div>
                    <h3>Sentiment Trend</h3>
                </div>
                <span class="filter-btn">Last 6 Weeks</span>
            </div>
            <p class="chart-subtitle">Weekly feedback sentiment — positive vs. neutral vs. negative</p>

            <canvas id="sentimentTrendChart" height="220"></canvas>

            <p id="sentimentTrendEmptyNote" class="chart-empty-note" style="{{ $hasSentimentTrendData ? 'display:none;' : '' }}">
                No sentiment trend data available yet.
            </p>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-header-title">
                    <div class="chart-icon-badge"><i class="bi bi-chat-square-text"></i></div>
                    <h3>Chat Inquiries Status</h3>
                </div>
            </div>
            <p class="chart-subtitle">Resolved vs. in-progress chatbot inquiries</p>

            <div class="donut-layout">
                <div class="donut-chart-wrap">
                    <canvas id="inquiryStatusChart"></canvas>
                </div>
                <div class="donut-legend">
                    <div class="donut-legend-row">
                        <span class="donut-legend-label">
                            <span class="donut-legend-dot" style="background: #0E62AA;"></span>
                            Resolved
                        </span>
                        <span class="donut-legend-value" id="inquiryStatusResolvedValue">{{ $resolvedInquiriesCount }}</span>
                    </div>
                    <div class="donut-legend-row">
                        <span class="donut-legend-label">
                            <span class="donut-legend-dot" style="background: #9DBCD4;"></span>
                            In progress
                        </span>
                        <span class="donut-legend-value" id="inquiryStatusUnresolvedValue">{{ $unresolvedInquiriesCount }}</span>
                    </div>
                </div>
            </div>

            <p id="inquiryStatusEmptyNote" class="chart-empty-note" style="{{ $hasInquiryStatusData ? 'display:none;' : '' }}">
                No inquiry status data available yet.
            </p>
        </div>
    </div>

    <!-- RECENT ACTIVITIES -->
    <div class="chart-card">
        <div class="chart-header" style="margin-bottom: 0.9rem;">
            <div class="chart-header-title">
                <div class="chart-icon-badge"><i class="bi bi-clock-history"></i></div>
                <h3>Recent Activities</h3>
            </div>
        </div>
        <div id="recentActivityList">
            @forelse($recentActivities as $activity)
                <div class="activity-item">
                    <div class="activity-icon"><i class="bi {{ $activity['icon'] }}"></i></div>
                    <div class="activity-details">
                        <div class="activity-title">{{ $activity['title'] }}</div>
                        <div class="activity-desc">{{ $activity['description'] }}</div>
                    </div>
                    <span class="activity-time">{{ $activity['time_human'] }}</span>
                </div>
            @empty
                <p id="recentActivityEmptyNote" class="chart-empty-note">No recent activity yet.</p>
            @endforelse
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
{{-- If staff.layouts.app already loads Chart.js elsewhere, remove this line to avoid loading it twice --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Admin dashboard palette
    const navy  = '#062744';
    const blue  = '#0E62AA';
    const light = '#9DBCD4';
    const PALETTE = ['#0E62AA', '#3969A8', '#5C8BC0', '#79A8CB', '#9DBCD4', '#00897B', '#43A047', '#FB8C00'];

    function isDarkMode() {
        return document.body.classList.contains('dark-mode');
    }

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    let emptyGrey = isDarkMode() ? '#3a4356' : '#e0e0e0';

    const DASHBOARD_DATA_URL = '{{ route("staff.dashboard.data") }}';
    const REFRESH_INTERVAL_MS = 30000; // 30 seconds — adjust as needed

    const hasServiceData = @json($hasServiceData);
    const hasInquiryStatusData = @json($hasInquiryStatusData);

    // ---- 1) Service Distribution (doughnut; legend is the list beside it) ----
    const serviceChart = new Chart(document.getElementById('serviceDistributionChart'), {
        type: 'doughnut',
        data: {
            labels: @json($serviceLabels),
            datasets: [{
                data: @json($serviceTotals),
                backgroundColor: hasServiceData ? PALETTE : [emptyGrey],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '58%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: hasServiceData }
            }
        }
    });

    // ---- Patient Visits Trend (line) ----
    const weeklyChart = new Chart(document.getElementById('weeklyVisitsChart'), {
        type: 'line',
        data: {
            labels: @json($weeklyVisitsLabels),
            datasets: [{
                label: 'Patient Visits',
                data: @json($weeklyVisitsData),
                borderColor: navy,
                backgroundColor: 'rgba(6,39,68,0.08)',
                fill: true,
                tension: 0.35,
                pointBackgroundColor: navy
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, suggestedMax: 5, grid: {} },
                x: { grid: {} }
            }
        }
    });

    // ---- Inquiry Volume (bar) ----
    const inquiryChart = new Chart(document.getElementById('inquiryVolumeChart'), {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Inquiries',
                data: @json($inquiryVolumeData),
                backgroundColor: navy,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 }, suggestedMax: 5, grid: {} }, x: { grid: {} } }
        }
    });

    // ---- Sentiment Trend (3-line chart) ----
    const sentimentTrendChart = new Chart(document.getElementById('sentimentTrendChart'), {
        type: 'line',
        data: {
            labels: @json($sentimentTrendLabelsData),
            datasets: [
                { label: 'Positive', data: @json($sentimentTrendPositiveData), borderColor: blue,  backgroundColor: blue,  tension: 0.35, pointBackgroundColor: blue },
                { label: 'Neutral',  data: @json($sentimentTrendNeutralData),  borderColor: light, backgroundColor: light, tension: 0.35, pointBackgroundColor: light },
                { label: 'Negative', data: @json($sentimentTrendNegativeData), borderColor: navy,  backgroundColor: navy,  tension: 0.35, pointBackgroundColor: navy }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, suggestedMax: 5, grid: {} },
                x: { grid: {} }
            }
        }
    });

    // ---- Chat Inquiries Status (doughnut) ----
    const inquiryStatusChart = new Chart(document.getElementById('inquiryStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Resolved', 'In progress'],
            datasets: [{
                data: hasInquiryStatusData ? [@json($resolvedInquiriesCount), @json($unresolvedInquiriesCount)] : [1],
                backgroundColor: hasInquiryStatusData ? [blue, light] : [emptyGrey],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '58%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: hasInquiryStatusData }
            }
        }
    });

    // ===== DARK MODE THEMING FOR CHARTS =====
    const allCharts = [serviceChart, weeklyChart, inquiryChart, sentimentTrendChart, inquiryStatusChart];

    function applyChartTheme() {
        const dark = isDarkMode();
        const textColor = dark ? '#c7cedd' : '#5a6472';
        const gridColor = dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';

        allCharts.forEach(chart => {
            if (!chart) return;

            if (chart.options.plugins && chart.options.plugins.legend) {
                chart.options.plugins.legend.labels = chart.options.plugins.legend.labels || {};
                chart.options.plugins.legend.labels.color = textColor;
            }

            if (chart.options.scales) {
                Object.values(chart.options.scales).forEach(scale => {
                    scale.ticks = scale.ticks || {};
                    scale.ticks.color = textColor;
                    scale.grid = scale.grid || {};
                    scale.grid.color = gridColor;
                });
            }

            chart.update();
        });
    }

    applyChartTheme();

    // Re-theme charts the moment Dark Mode toggle switch is used (see app.blade.php)
    window.addEventListener('staffThemeChange', applyChartTheme);

    function serviceLegendHtml(services) {
        return services.map((s, i) => `
            <div class="donut-legend-row">
                <span class="donut-legend-label">
                    <span class="donut-legend-dot" style="background: ${PALETTE[i % PALETTE.length]};"></span>
                    ${esc(s.service_type)}
                </span>
                <span class="donut-legend-value">${esc(s.total)} visits</span>
            </div>
        `).join('');
    }

    function renderRecentActivities(activities) {
        const container = document.getElementById('recentActivityList');
        if (!Array.isArray(activities) || activities.length === 0) {
            container.innerHTML = '<p class="chart-empty-note">No recent activity yet.</p>';
            return;
        }
        container.innerHTML = activities.map(a => `
            <div class="activity-item">
                <div class="activity-icon"><i class="bi ${esc(a.icon)}"></i></div>
                <div class="activity-details">
                    <div class="activity-title">${esc(a.title)}</div>
                    <div class="activity-desc">${esc(a.description)}</div>
                </div>
                <span class="activity-time">${esc(a.time_human)}</span>
            </div>
        `).join('');
    }

    async function refreshDashboard() {
        try {
            const res = await fetch(DASHBOARD_DATA_URL, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!res.ok) throw new Error('Bad response: ' + res.status);
            const data = await res.json();

            // ---- Stat cards ----
            document.getElementById('stat-schedule-visit').textContent = data.totalScheduleVisit ?? 0;
            document.getElementById('stat-pending-inquiries').textContent = data.pendingInquiries ?? 0;
            document.getElementById('stat-active-doctors').textContent = data.activeDoctors ?? 0;
            document.getElementById('stat-total-patients').textContent = data.totalPatients ?? 0;

            // ---- 1) Service Distribution ----
            const services = Array.isArray(data.serviceDistribution) ? data.serviceDistribution : [];
            const hasServices = services.length > 0;
            emptyGrey = isDarkMode() ? '#3a4356' : '#e0e0e0';
            serviceChart.data.labels = hasServices ? services.map(s => s.service_type) : ['No data'];
            serviceChart.data.datasets[0].data = hasServices ? services.map(s => s.total) : [1];
            serviceChart.data.datasets[0].backgroundColor = hasServices ? PALETTE : [emptyGrey];
            serviceChart.options.plugins.tooltip.enabled = hasServices;
            serviceChart.update();

            const listEl = document.getElementById('serviceDistributionList');
            listEl.innerHTML = hasServices
                ? serviceLegendHtml(services)
                : '<p class="chart-empty-note">No service data available yet.</p>';

            // ---- 2) Patient Visits Trend ----
            const weekly = Array.isArray(data.weeklyVisits) ? data.weeklyVisits : [];
            const hasWeekly = weekly.length > 0;
            weeklyChart.data.labels = data.weekLabels ?? weeklyChart.data.labels;
            weeklyChart.data.datasets[0].data = hasWeekly ? weekly : new Array(6).fill(0);
            weeklyChart.update();
            document.getElementById('weeklyVisitsEmptyNote').style.display = hasWeekly ? 'none' : 'block';

            // ---- 3) Inquiry Volume ----
            const inquiryVolume = Array.isArray(data.inquiryVolumeByDay) ? data.inquiryVolumeByDay : [];
            const hasInquiry = inquiryVolume.length > 0;
            inquiryChart.data.datasets[0].data = hasInquiry ? inquiryVolume : new Array(7).fill(0);
            inquiryChart.update();
            document.getElementById('inquiryVolumeEmptyNote').style.display = hasInquiry ? 'none' : 'block';

            // ---- 4) Patient Feedback Overview ----
            const sentimentTotal = (data.positivePercent ?? 0) + (data.neutralPercent ?? 0) + (data.negativePercent ?? 0);
            const sentimentValues = {
                Positive: sentimentTotal > 0 ? (data.positivePercent ?? 0) : 0,
                Neutral: sentimentTotal > 0 ? (data.neutralPercent ?? 0) : 0,
                Negative: sentimentTotal > 0 ? (data.negativePercent ?? 0) : 0,
            };
            document.querySelectorAll('#sentimentBars .sentiment-row').forEach(row => {
                const label = row.dataset.label;
                const value = sentimentValues[label] ?? 0;
                row.querySelector('.sentiment-value').textContent = value + '%';
                row.querySelector('.sentiment-fill').style.width = value + '%';
            });
            document.getElementById('sentimentEmptyNote').style.display = sentimentTotal > 0 ? 'none' : 'block';

            // ---- 5) Sentiment Trend (only if the controller sends it) ----
            if (Array.isArray(data.sentimentTrendPositive)) {
                const pos = data.sentimentTrendPositive;
                const neu = Array.isArray(data.sentimentTrendNeutral) ? data.sentimentTrendNeutral : new Array(pos.length).fill(0);
                const neg = Array.isArray(data.sentimentTrendNegative) ? data.sentimentTrendNegative : new Array(pos.length).fill(0);
                const sum = a => a.reduce((t, n) => t + Number(n || 0), 0);
                sentimentTrendChart.data.labels = data.sentimentTrendLabels ?? sentimentTrendChart.data.labels;
                sentimentTrendChart.data.datasets[0].data = pos;
                sentimentTrendChart.data.datasets[1].data = neu;
                sentimentTrendChart.data.datasets[2].data = neg;
                sentimentTrendChart.update();
                document.getElementById('sentimentTrendEmptyNote').style.display =
                    (sum(pos) + sum(neu) + sum(neg)) > 0 ? 'none' : 'block';
            }

            // ---- 6) Chat Inquiries Status (only if the controller sends it) ----
            if (data.resolvedInquiries !== undefined || data.unresolvedInquiries !== undefined) {
                const resolved = Number(data.resolvedInquiries ?? 0);
                const unresolved = Number(data.unresolvedInquiries ?? 0);
                const hasStatus = (resolved + unresolved) > 0;
                emptyGrey = isDarkMode() ? '#3a4356' : '#e0e0e0';
                inquiryStatusChart.data.datasets[0].data = hasStatus ? [resolved, unresolved] : [1];
                inquiryStatusChart.data.datasets[0].backgroundColor = hasStatus ? [blue, light] : [emptyGrey];
                inquiryStatusChart.options.plugins.tooltip.enabled = hasStatus;
                inquiryStatusChart.update();
                document.getElementById('inquiryStatusResolvedValue').textContent = resolved;
                document.getElementById('inquiryStatusUnresolvedValue').textContent = unresolved;
                document.getElementById('inquiryStatusEmptyNote').style.display = hasStatus ? 'none' : 'block';
            }

            // ---- Recent Activities ----
            renderRecentActivities(data.recentActivities);
        } catch (err) {
            console.error('Dashboard refresh failed:', err);
        }
    }

    // Poll every REFRESH_INTERVAL_MS
    setInterval(refreshDashboard, REFRESH_INTERVAL_MS);

    // Also refresh immediately whenever the staff switches back to this tab
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) refreshDashboard();
    });
});
</script>
@endpush