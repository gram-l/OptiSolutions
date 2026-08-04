@extends('staff.layouts.app')

@section('content')
<div class="container">

    <!-- WELCOME BANNER -->
    <div style="background: #1A56C4; border-radius: 20px; padding: 2rem 2.5rem; color: white; margin-bottom: 2rem;">
        <h2 style="margin: 0; font-weight: 700;">
            Welcome, {{ auth()->user()->name ?? 'Staff' }}!
        </h2>
        <p style="margin-top: 0.5rem; opacity: 0.9;">
            Here's what's happening at the clinic today
        </p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label" style="text-transform: uppercase; font-size: 0.75rem; font-weight: 600; color: #7f8c8d; letter-spacing: 0.5px; margin-bottom: 0.6rem;">
                Total Schedule Visit
            </div>
            <div class="stat-number" id="stat-schedule-visit">{{ $totalScheduleVisit ?? 0 }}</div>
            <div class="stat-sublabel" id="stat-schedule-visit-sub" style="font-size: 0.8rem; color: #b0b8c1; margin-top: 0.5rem;">
                Scheduled visits
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label" style="text-transform: uppercase; font-size: 0.75rem; font-weight: 600; color: #7f8c8d; letter-spacing: 0.5px; margin-bottom: 0.6rem;">
                Pending Inquiries
            </div>
            <div class="stat-number" id="stat-pending-inquiries">{{ $pendingInquiries ?? 0 }}</div>
            <div class="stat-sublabel" id="stat-pending-inquiries-sub" style="font-size: 0.8rem; color: #b0b8c1; margin-top: 0.5rem;">
                Awaiting reply
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label" style="text-transform: uppercase; font-size: 0.75rem; font-weight: 600; color: #7f8c8d; letter-spacing: 0.5px; margin-bottom: 0.6rem;">
                Active Doctors
            </div>
            <div class="stat-number" id="stat-active-doctors">{{ $activeDoctors ?? 0 }}</div>
            <div class="stat-sublabel" id="stat-active-doctors-sub" style="font-size: 0.8rem; color: #b0b8c1; margin-top: 0.5rem;">
                Currently practicing
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label" style="text-transform: uppercase; font-size: 0.75rem; font-weight: 600; color: #7f8c8d; letter-spacing: 0.5px; margin-bottom: 0.6rem;">
                Registered Patients
            </div>
            <div class="stat-number" id="stat-total-patients">{{ $totalPatients ?? 0 }}</div>
            <div class="stat-sublabel" id="stat-total-patients-sub" style="font-size: 0.8rem; color: #b0b8c1; margin-top: 0.5rem;">
                Total records
            </div>
        </div>
    </div>

    @php
        // ---- Service Distribution defaults ----
        $hasServiceData = isset($serviceDistribution) && $serviceDistribution->count() > 0;
        $serviceLabels = $hasServiceData ? $serviceDistribution->pluck('service_type') : collect(['No data']);
        $serviceTotals = $hasServiceData ? $serviceDistribution->pluck('total') : collect([1]);

        // ---- Weekly Patient Visits defaults ----
        $defaultWeekLabels = ['W-5', 'W-4', 'W-3', 'W-2', 'W-1', 'This wk'];
        $hasWeeklyData = isset($weeklyVisits) && count($weeklyVisits) > 0;
        $weeklyVisitsData = $hasWeeklyData ? $weeklyVisits : array_fill(0, 6, 0);
        $weeklyVisitsLabels = $weekLabels ?? $defaultWeekLabels;

        // ---- Sentiment Analysis defaults ----
        $sentimentTotal = ($positivePercent ?? 0) + ($neutralPercent ?? 0) + ($negativePercent ?? 0);
        $sentimentRows = [
            ['label' => 'Positive', 'value' => $sentimentTotal > 0 ? ($positivePercent ?? 0) : 0, 'color' => '#2ecc71'],
            ['label' => 'Neutral',  'value' => $sentimentTotal > 0 ? ($neutralPercent ?? 0)  : 0, 'color' => '#f39c12'],
            ['label' => 'Negative', 'value' => $sentimentTotal > 0 ? ($negativePercent ?? 0) : 0, 'color' => '#e74c3c'],
        ];

        // ---- Inquiry Volume defaults ----
        $hasInquiryData = isset($inquiryVolumeByDay) && count($inquiryVolumeByDay) > 0;
        $inquiryVolumeData = $hasInquiryData ? $inquiryVolumeByDay : array_fill(0, 7, 0);

        // ---- Recent Activities defaults ----
        $recentActivities = $recentActivities ?? [];
    @endphp

    <!-- 1) SERVICE DISTRIBUTION (doughnut) -->
    <div class="dashboard-card" style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); margin-top: 2rem;">
        <h3 style="color: var(--text-dark);">Service Distribution</h3>
        <p style="color: #7f8c8d; font-size: 0.9rem;">Distribution of patient visits by department</p>

        <div style="max-width: 380px; margin: 1rem auto 1.5rem;">
            <canvas id="serviceDistributionChart" height="260"></canvas>
        </div>

        <div id="serviceDistributionList">
            @if($hasServiceData)
                @foreach($serviceDistribution as $service)
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--light-gray);">
                        <span>{{ $service->service_type }}</span>
                        <span>{{ $service->total }} visits</span>
                    </div>
                @endforeach
            @else
                <p style="color: #7f8c8d; margin-top: 10px; font-size: 0.85rem;">No service data available yet.</p>
            @endif
        </div>
    </div>

    <!-- 2) WEEKLY PATIENT VISITS (line) -->
    <div class="dashboard-card" style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); margin-top: 2rem;">
        <h3 style="color: var(--text-dark);">Weekly Patient Visits</h3>
        <p style="color: #7f8c8d; font-size: 0.9rem;">Visits from schedule_visit over the last several weeks</p>

        <div style="margin-top: 1rem;">
            <canvas id="weeklyVisitsChart" height="240"></canvas>
        </div>

        <p id="weeklyVisitsEmptyNote" style="color: #7f8c8d; margin-top: 10px; font-size: 0.85rem; {{ $hasWeeklyData ? 'display:none;' : '' }}">
            No visit data available yet.
        </p>
    </div>

    <!-- 3) SENTIMENT ANALYSIS (custom bars) -->
    <div class="dashboard-card" style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); margin-top: 2rem;">
        <h3 style="color: var(--text-dark);">Sentiment Analysis</h3>
        <p style="color: #7f8c8d; font-size: 0.9rem;">Breakdown of recent patient feedback</p>

        <div id="sentimentBars" style="margin-top: 1.25rem;">
            @foreach($sentimentRows as $row)
                <div class="sentiment-row" data-label="{{ $row['label'] }}" data-color="{{ $row['color'] }}" style="margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px;">
                        <span style="font-weight: 600; color: var(--text-dark);">{{ $row['label'] }}</span>
                        <span class="sentiment-value" style="font-weight: 700; color: {{ $row['color'] }};">{{ $row['value'] }}%</span>
                    </div>
                    <div style="background: {{ $row['color'] }}1A; border-radius: 6px; height: 10px; overflow: hidden;">
                        <div class="sentiment-fill" style="background: {{ $row['color'] }}; width: {{ $row['value'] }}%; height: 100%; border-radius: 6px;"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <p id="sentimentEmptyNote" style="color: #7f8c8d; margin-top: 10px; font-size: 0.85rem; {{ $sentimentTotal > 0 ? 'display:none;' : '' }}">
            No feedback data available yet.
        </p>
    </div>

    <!-- 4) INQUIRY VOLUME PER WEEK (bar) -->
    <div class="dashboard-card" style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); margin-top: 2rem;">
        <h3 style="color: var(--text-dark);">Inquiry Volume per Week</h3>
        <p style="color: #7f8c8d; font-size: 0.9rem;">Inquiries received per day this week</p>

        <div style="margin-top: 1rem;">
            <canvas id="inquiryVolumeChart" height="240"></canvas>
        </div>

        <p id="inquiryVolumeEmptyNote" style="color: #7f8c8d; margin-top: 10px; font-size: 0.85rem; {{ $hasInquiryData ? 'display:none;' : '' }}">
            No inquiry data available yet.
        </p>
    </div>

    <!-- RECENT ACTIVITY -->
    <div class="dashboard-card" style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); margin-top: 2rem;">
        <h3 style="color: var(--text-dark);">Recent Activities</h3>
        <div id="recentActivityList">
            @forelse($recentActivities as $activity)
                <div style="padding: 0.8rem 0; border-bottom:1px solid var(--light-gray); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <i class="bi {{ $activity['icon'] }}" style="color: {{ $activity['color'] }}; margin-right: 10px;"></i>
                        <strong>{{ $activity['title'] }}</strong>
                        <span style="color: #7f8c8d;"> — {{ $activity['description'] }}</span>
                    </div>
                    <span style="color: #b0b8c1; font-size: 0.8rem; white-space: nowrap; margin-left: 1rem;">{{ $activity['time_human'] }}</span>
                </div>
            @empty
                <p id="recentActivityEmptyNote" style="color: #7f8c8d; margin-top: 10px; font-size: 0.85rem;">No recent activity yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- If staff.layouts.app already loads Chart.js elsewhere, remove this line to avoid loading it twice --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const navy = '#1A237E';

    function isDarkMode() {
        return document.body.classList.contains('dark-mode');
    }

    let emptyGrey = isDarkMode() ? '#3a4356' : '#e0e0e0';

    const DASHBOARD_DATA_URL = '{{ route("staff.dashboard.data") }}';
    const REFRESH_INTERVAL_MS = 30000; // 30 seconds — adjust as needed


    const hasServiceData = @json($hasServiceData);

    const serviceChart = new Chart(document.getElementById('serviceDistributionChart'), {
        type: 'doughnut',
        data: {
            labels: @json($serviceLabels),
            datasets: [{
                data: @json($serviceTotals),
                backgroundColor: hasServiceData
                    ? [navy, '#3949ab', '#5c6bc0', '#7986cb', '#9fa8da', '#00897b', '#43a047', '#fb8c00']
                    : [emptyGrey],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: hasServiceData, position: 'bottom', labels: {} },
                tooltip: { enabled: hasServiceData }
            }
        }
    });

    const weeklyChart = new Chart(document.getElementById('weeklyVisitsChart'), {
        type: 'line',
        data: {
            labels: @json($weeklyVisitsLabels),
            datasets: [{
                label: 'Patient Visits',
                data: @json($weeklyVisitsData),
                borderColor: navy,
                backgroundColor: 'rgba(26,35,126,0.08)',
                fill: true,
                tension: 0.35,
                pointBackgroundColor: navy
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    //numbering
                    ticks: { stepSize: 1, precision: 0 },
                    //numbering
                    suggestedMax: 6,
                    grid: {}
                },
                x: { grid: {} }
            }
        }
    });

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

    // ===== DARK MODE THEMING FOR CHARTS =====
    function applyChartTheme() {
        const dark = isDarkMode();
        const textColor = dark ? '#c7cedd' : '#5a6472';
        const gridColor = dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';

        [serviceChart, weeklyChart, inquiryChart].forEach(chart => {
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

    function renderRecentActivities(activities) {
        const container = document.getElementById('recentActivityList');
        if (!Array.isArray(activities) || activities.length === 0) {
            container.innerHTML = '<p style="color:#7f8c8d; margin-top:10px; font-size:0.85rem;">No recent activity yet.</p>';
            return;
        }
        container.innerHTML = activities.map(a => `
            <div style="padding: 0.8rem 0; border-bottom:1px solid var(--light-gray); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <i class="bi ${a.icon}" style="color: ${a.color}; margin-right: 10px;"></i>
                    <strong>${a.title}</strong>
                    <span style="color: #7f8c8d;"> — ${a.description}</span>
                </div>
                <span style="color: #b0b8c1; font-size: 0.8rem; white-space: nowrap; margin-left: 1rem;">${a.time_human}</span>
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
            serviceChart.data.datasets[0].backgroundColor = hasServices
                ? [navy, '#3949ab', '#5c6bc0', '#7986cb', '#9fa8da', '#00897b', '#43a047', '#fb8c00']
                : [emptyGrey];
            serviceChart.options.plugins.legend.display = hasServices;
            serviceChart.options.plugins.tooltip.enabled = hasServices;
            serviceChart.update();

            const listEl = document.getElementById('serviceDistributionList');
            if (hasServices) {
                listEl.innerHTML = services.map(s => `
                    <div style="display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid var(--light-gray);">
                        <span>${s.service_type}</span>
                        <span>${s.total} visits</span>
                    </div>
                `).join('');
            } else {
                listEl.innerHTML = '<p style="color:#7f8c8d; margin-top:10px; font-size:0.85rem;">No service data available yet.</p>';
            }

            // ---- 2) Weekly Patient Visits ----
            const weekly = Array.isArray(data.weeklyVisits) ? data.weeklyVisits : [];
            const hasWeekly = weekly.length > 0;
            weeklyChart.data.labels = data.weekLabels ?? weeklyChart.data.labels;
            weeklyChart.data.datasets[0].data = hasWeekly ? weekly : new Array(6).fill(0);
            weeklyChart.update();
            document.getElementById('weeklyVisitsEmptyNote').style.display = hasWeekly ? 'none' : 'block';

            // ---- 3) Sentiment Analysis ----
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

            // ---- 4) Inquiry Volume per Week ----
            const inquiryVolume = Array.isArray(data.inquiryVolumeByDay) ? data.inquiryVolumeByDay : [];
            const hasInquiry = inquiryVolume.length > 0;
            inquiryChart.data.datasets[0].data = hasInquiry ? inquiryVolume : new Array(7).fill(0);
            inquiryChart.update();
            document.getElementById('inquiryVolumeEmptyNote').style.display = hasInquiry ? 'none' : 'block';

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