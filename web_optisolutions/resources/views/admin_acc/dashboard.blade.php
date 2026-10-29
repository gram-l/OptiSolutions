<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <title>OptiSolutions - Admin Dashboard</title>
    @vite([
        'resources/css/admin_css/dashboard.css',
        'resources/css/admin_css/sidebar.css',
        'resources/css/admin_css/header.css'
    ])
</head>
<body>
    @include('partials.mobile_dashboard_charts')
    @include('admin_acc.header')

    <div class="container">
        @include('admin_acc.sidebar')

        <main class="main-content admin-dashboard">
            <div class="welcome-banner">
                <div class="welcome-banner-text">
                    <h2>Welcome, {{ Auth::user()->name }}!</h2>
                    <p>Here's what's happening with your clinic today.</p>
                </div>
                <div class="welcome-banner-icon"><i class="bi bi-heart-pulse"></i></div>
            </div>

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


                // ---- Chat Inquiries Status defaults ----
                $resolvedInquiriesCount = $resolvedInquiries ?? 0;
                $unresolvedInquiriesCount = $unresolvedInquiries ?? 0;
                $hasInquiryStatusData = ($resolvedInquiriesCount + $unresolvedInquiriesCount) > 0;
            @endphp

            <!-- Analytics Cards -->
            <div class="analytics-grid">
                <a href="{{ route('admin_acc.chatbot_logs') }}" class="analytics-card">
                    <div class="card-top-row">
                        <div class="card-icon-title">
                            <div class="card-icon-badge"><i class="bi bi-file-earmark-text"></i></div>
                            <div class="card-title">Pending Inquiries</div>
                        </div>
                        <i class="bi bi-chevron-right card-chevron"></i>
                    </div>
                    <div class="card-value" id="stat-pending-inquiries">{{ $pendingInquiries ?? 0 }}</div>
                    <div class="card-subtitle"><span class="dot"></span> Awaiting reply</div>
                </a>
                <a href="{{ url('/admin_acc/appointments') }}" class="analytics-card">
                    <div class="card-top-row">
                        <div class="card-icon-title">
                            <div class="card-icon-badge"><i class="bi bi-calendar-check"></i></div>
                            <div class="card-title">Today's Scheduled Visits</div>
                        </div>
                        <i class="bi bi-chevron-right card-chevron"></i>
                    </div>
                    <div class="card-value" id="stat-todays-appointments">{{ $todaysAppointments ?? 0 }}</div>
                    <div class="card-subtitle"><span class="dot"></span> <span id="stat-pending-approval">{{ $pendingApproval ?? 0 }}</span> pending approval</div>
                </a>
                <a href="{{ url('/admin_acc/patients') }}" class="analytics-card">
                    <div class="card-top-row">
                        <div class="card-icon-title">
                            <div class="card-icon-badge"><i class="bi bi-person"></i></div>
                            <div class="card-title"> Patient Count</div>
                        </div>
                        <i class="bi bi-chevron-right card-chevron"></i>
                    </div>
                    <div class="card-value" id="stat-active-patients">{{ $activePatients ?? 0 }}</div>
                    <div class="card-subtitle"><span class="dot"></span> <span id="stat-new-patients">{{ $newPatientsThisMonth ?? 0 }}</span> new this month</div>
                </a>
                <a href="{{ route('admin_acc.feedback.index') }}" class="analytics-card">
                    <div class="card-top-row">
                        <div class="card-icon-title">
                            <div class="card-icon-badge"><i class="bi bi-star-fill"></i></div>
                            <div class="card-title">Patient Satisfaction</div>
                        </div>
                        <i class="bi bi-chevron-right card-chevron"></i>
                    </div>
                    <div class="card-value" id="stat-avg-rating">{{ $avgRating ?? 0 }} / 5</div>
                    <div class="card-subtitle">⭐ Average rating</div>
                </a>
            </div>

            <style>
                .charts-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 1.5rem;
                    margin-top: 1.5rem;
                }
                .charts-grid .chart-card {
                    margin-top: 0;
                }
                @media (max-width: 900px) {
                    .charts-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <div class="charts-grid">
                <!-- 1) SERVICE DISTRIBUTION (doughnut + side legend) -->
                @include('partials.dashboard_rating_leaders')

                <!-- 2) PATIENT FEEDBACK OVERVIEW (custom bars) -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-header-title">
                            <div class="chart-icon-badge"><i class="bi bi-emoji-smile"></i></div>
                            <h3>Patient Feedback Overview</h3>
                        </div>
                    </div>
                    <div id="sentimentBars" class="sentiment-bars" style="margin-top: 1.1rem;">
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
                    <p id="sentimentEmptyNote" style="color: #7f8c8d; margin-top: 10px; font-size: 0.85rem; {{ $sentimentTotal > 0 ? 'display:none;' : '' }}">
                        No feedback data available yet.
                    </p>
                    <div class="top-complaint-box">
                        <div class="top-complaint-icon"><i class="bi bi-chat-square-text"></i></div>
                        <div>
                            <div class="top-complaint-label">Reported concerns</div>
                            <div class="top-complaint-text"><a href="#diagnostic-title" id="diagnostic-open" aria-haspopup="dialog" aria-controls="diagnostic-modal">Click to view comparisons and supporting reviews</a></div>
                        </div>
                    </div>
                </div>

                <!-- 3) INQUIRY VOLUME (bar) -->
                <div class="chart-card inquiry-status-card">
                    <div class="chart-header">
                        <div class="chart-header-title">
                            <div class="chart-icon-badge"><i class="bi bi-chat-left-dots"></i></div>
                            <h3>Chat Inquiries Status</h3>
                        </div>
                    </div>
                    <p class="chart-subtitle">Resolved vs. in-progress chatbot inquiries</p>
                    <div class="donut-layout">
                        <div class="donut-chart-wrap">
                            <canvas id="inquiryStatusChart"></canvas>
                        </div>
                        <div class="donut-legend" id="inquiryStatusList">
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
                                    In Progress
                                </span>
                                <span class="donut-legend-value" id="inquiryStatusUnresolvedValue">{{ $unresolvedInquiriesCount }}</span>
                            </div>
                        </div>
                    </div>
                    <p id="inquiryStatusEmptyNote" style="color: #7f8c8d; margin-top: 10px; font-size: 0.85rem; {{ $hasInquiryStatusData ? 'display:none;' : '' }}">
                        No chat inquiry data available yet.
                    </p>
                </div>

                <!-- 4) PATIENT VISITS TREND (line) -->
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
                                <p style="color: #7f8c8d; font-size: 0.85rem;">No service data available yet.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 5) SENTIMENT TREND OVER TIME (line) -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-header-title">
                            <div class="chart-icon-badge"><i class="bi bi-graph-up"></i></div>
                            <h3>Patient Visits Trend</h3>
                        </div>
                        @include('partials.dashboard_chart_filter', ['chartFilter' => 'visits'])
                    </div>
                    <p class="chart-subtitle" id="visits-period-description">{{ $visitPeriodLabel ?? "Weekly scheduled visits, including this week (ongoing)." }}</p>
<p id="visits-filter-status" class="dashboard-chart-filter-status" role="status"></p>
                    <div>
                        <canvas id="weeklyVisitsChart" height="220"></canvas>
                    </div>
                    <p id="weeklyVisitsEmptyNote" style="color: #7f8c8d; margin-top: 10px; font-size: 0.85rem; {{ $hasWeeklyData ? 'display:none;' : '' }}">
                        No visit data available yet.
                    </p>
                </div>

                <!-- 6) CHAT INQUIRIES: RESOLVED VS IN PROGRESS (donut) -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-header-title">
                            <div class="chart-icon-badge"><i class="bi bi-bar-chart"></i></div>
                            <h3>Inquiry Volume</h3>
                        </div>
                        @include('partials.dashboard_chart_filter', ['chartFilter' => 'inquiry'])
                    </div>
                    <p class="chart-subtitle" id="inquiry-period-description">{{ $inquiryPeriodLabel ?? "Inquiries received Monday–Sunday (Philippine time)." }}</p>
<p id="inquiry-filter-status" class="dashboard-chart-filter-status" role="status"></p>
                    <div>
                        <canvas id="inquiryVolumeChart" height="220"></canvas>
                    </div>
                    <p id="inquiryVolumeEmptyNote" style="color: #7f8c8d; margin-top: 10px; font-size: 0.85rem; {{ $hasInquiryData ? 'display:none;' : '' }}">
                        No inquiry data available yet.
                    </p>
                </div>
            </div>

            

        </main>
    </div>
    @include('admin_acc.partials.diagnostic_analytics')

    @include('partials.dashboard_chart_filters_script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const navy = '#062744';
        const emptyGrey = '#e0e0e0';

        const DASHBOARD_DATA_URL = '{{ route("admin.dashboard.data") }}';
        const REFRESH_INTERVAL_MS = 30000;

        const hasServiceData = @json($hasServiceData);

        const serviceChart = new Chart(document.getElementById('serviceDistributionChart'), {
            type: 'doughnut',
            data: {
                labels: @json($serviceLabels),
                datasets: [{
                    data: @json($serviceTotals),
                    backgroundColor: hasServiceData
                        ? ['#0E62AA', '#3969A8', '#5C8BC0', '#79A8CB', '#9DBCD4', '#00897B', '#43A047', '#FB8C00']
                        : [emptyGrey],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: hasServiceData }
                }
            }
        });

        window.matchMedia('(max-width: 768px)').addEventListener('change', event => {
            serviceChart.options.plugins.legend.display = false;
            serviceChart.resize();
            serviceChart.update('none');
        });

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
                scales: { y: { beginAtZero: true, ticks: { precision: 0 }, suggestedMax: 5 } }
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
                scales: { y: { beginAtZero: true, ticks: { precision: 0 }, suggestedMax: 5 } }
            }
        });

        const hasInquiryStatusData = @json($hasInquiryStatusData);

        const inquiryStatusChart = new Chart(document.getElementById('inquiryStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Resolved', 'In Progress'],
                datasets: [{
                    data: [@json($resolvedInquiriesCount), @json($unresolvedInquiriesCount)],
                    backgroundColor: hasInquiryStatusData ? ['#0E62AA', '#9DBCD4'] : [emptyGrey],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: hasInquiryStatusData }
                }
            }
        });

        const chartFilters = window.createDashboardChartFilters(refreshDashboard);

        async function refreshDashboard() {
            try {
                const data = await chartFilters.load(DASHBOARD_DATA_URL);
                if (!data) return;
                window.updateDashboardRatingLeaders(data);

                document.getElementById('stat-pending-inquiries').textContent = data.pendingInquiries ?? 0;
                document.getElementById('stat-todays-appointments').textContent = data.todaysAppointments ?? 0;
                document.getElementById('stat-pending-approval').textContent = data.pendingApproval ?? 0;
                document.getElementById('stat-active-patients').textContent = data.activePatients ?? 0;
                document.getElementById('stat-new-patients').textContent = data.newPatientsThisMonth ?? 0;
                document.getElementById('stat-avg-rating').textContent = data.avgRating ?? 0;

                const services = Array.isArray(data.serviceDistribution) ? data.serviceDistribution : [];
                const hasServices = services.length > 0;
                serviceChart.data.labels = hasServices ? services.map(s => s.service_type) : ['No data'];
                serviceChart.data.datasets[0].data = hasServices ? services.map(s => s.total) : [1];
                serviceChart.data.datasets[0].backgroundColor = hasServices
                    ? ['#0E62AA', '#3969A8', '#5C8BC0', '#79A8CB', '#9DBCD4', '#00897B', '#43A047', '#FB8C00']
                    : [emptyGrey];
                serviceChart.options.plugins.legend.display = false;
                serviceChart.options.plugins.tooltip.enabled = hasServices;
                serviceChart.update();

                const listEl = document.getElementById('serviceDistributionList');
                const legendColors = ['#0E62AA', '#3969A8', '#5C8BC0', '#79A8CB', '#9DBCD4', '#00897B', '#43A047', '#FB8C00'];
                listEl.innerHTML = hasServices
                    ? services.map((s, i) => `
                        <div class="donut-legend-row">
                            <span class="donut-legend-label">
                                <span class="donut-legend-dot" style="background:${legendColors[i % legendColors.length]};"></span>
                                ${s.service_type}
                            </span>
                            <span class="donut-legend-value">${s.total} visits</span>
                        </div>`).join('')
                    : '<p style="color:#7f8c8d; font-size:0.85rem;">No service data available yet.</p>';

                const weekly = Array.isArray(data.weeklyVisits) ? data.weeklyVisits : [];
                const hasWeekly = weekly.length > 0;
                weeklyChart.data.labels = data.weekLabels ?? weeklyChart.data.labels;
                weeklyChart.data.datasets[0].data = hasWeekly ? weekly : new Array(6).fill(0);
                weeklyChart.update();
                document.getElementById('weeklyVisitsEmptyNote').style.display = hasWeekly ? 'none' : 'block';

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

                const inquiryVolume = Array.isArray(data.inquiryVolumeByDay) ? data.inquiryVolumeByDay : [];
                const hasInquiry = inquiryVolume.length > 0;
                inquiryChart.data.datasets[0].data = hasInquiry ? inquiryVolume : new Array(7).fill(0);
                inquiryChart.update();
                document.getElementById('inquiryVolumeEmptyNote').style.display = hasInquiry ? 'none' : 'block';

                const resolvedCount = data.resolvedInquiries ?? 0;
                const unresolvedCount = data.unresolvedInquiries ?? 0;
                const hasStatusData = (resolvedCount + unresolvedCount) > 0;
                inquiryStatusChart.data.datasets[0].data = [resolvedCount, unresolvedCount];
                inquiryStatusChart.data.datasets[0].backgroundColor = hasStatusData ? ['#0E62AA', '#9DBCD4'] : [emptyGrey];
                inquiryStatusChart.options.plugins.tooltip.enabled = hasStatusData;
                inquiryStatusChart.update();
                document.getElementById('inquiryStatusResolvedValue').textContent = resolvedCount;
                document.getElementById('inquiryStatusUnresolvedValue').textContent = unresolvedCount;
                document.getElementById('inquiryStatusEmptyNote').style.display = hasStatusData ? 'none' : 'block';
            } catch (err) {
                console.error('Admin dashboard refresh failed:', err);
            }
        }

        setInterval(refreshDashboard, REFRESH_INTERVAL_MS);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) refreshDashboard();
        });
    });
    </script>
</body>
</html>
