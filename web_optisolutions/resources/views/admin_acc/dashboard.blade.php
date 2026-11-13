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

    <div class="container dashboard-layout">
        @include('admin_acc.sidebar')

        <main class="main-content admin-dashboard">
            <div class="welcome-banner">
                <div class="welcome-banner-text">
                    <h2>Welcome, {{ Auth::user()->name }}!</h2>
                    <p>Here's what's happening with your clinic today.</p>
                </div>
                @php
                    $clinicDate = now('Asia/Manila');
                @endphp
                <time datetime="{{ $clinicDate->toDateString() }}">{{ $clinicDate->format('l, j F Y') }}</time>
            </div>

            @php
                // ---- Service Distribution defaults ----
                $hasServiceData = isset($serviceDistribution) && $serviceDistribution->count() > 0;
                $serviceLabels = $hasServiceData ? $serviceDistribution->pluck('service_type') : collect(['No data']);
                $serviceTotals = $hasServiceData ? $serviceDistribution->pluck('total') : collect([1]);
                $serviceColors = ['#0E62AA', '#3969A8', '#5C8BC0', '#79A8CB', '#9DBCD4', '#00897B', '#43A047', '#FB8C00'];

                // ---- Weekly Patient Visits defaults ----
                $defaultWeekLabels = ['W-5', 'W-4', 'W-3', 'W-2', 'W-1', 'This wk'];
                $weeklyVisitsData = $weeklyVisits ?? array_fill(0, 6, 0);
                $weeklyVisitsLabels = $weekLabels ?? $defaultWeekLabels;

                // ---- Patient Feedback defaults ----
                $sentimentTotal = ($positivePercent ?? 0) + ($neutralPercent ?? 0) + ($negativePercent ?? 0);
                $sentimentRows = [
                    ['label' => 'Positive', 'value' => $sentimentTotal > 0 ? ($positivePercent ?? 0) : 0, 'color' => '#16a34a', 'icon' => 'bi-emoji-smile', 'avatarClass' => 'sentiment-avatar-positive'],
                    ['label' => 'Neutral',  'value' => $sentimentTotal > 0 ? ($neutralPercent ?? 0)  : 0, 'color' => '#94a3b8', 'icon' => 'bi-emoji-neutral', 'avatarClass' => 'sentiment-avatar-neutral'],
                    ['label' => 'Negative', 'value' => $sentimentTotal > 0 ? ($negativePercent ?? 0) : 0, 'color' => '#dc2626', 'icon' => 'bi-emoji-frown', 'avatarClass' => 'sentiment-avatar-negative'],
                ];

                // ---- Inquiry Volume defaults ----
                $inquiryVolumeData = $inquiryVolumeByDay ?? array_fill(0, 7, 0);
                $hasInquiryData = array_sum($inquiryVolumeData) > 0;


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
                        <i class="bi bi-chevron-right card-chevron" aria-hidden="true"></i>
                    </div>
                    <div class="card-value" id="stat-pending-inquiries">{{ $pendingInquiries ?? 0 }}</div>
                    <div class="card-subtitle"><span class="dot" id="pending-inquiries-dot" data-attention="{{ ($pendingInquiries ?? 0) > 0 ? 'true' : 'false' }}" aria-hidden="true"></span> Awaiting reply</div>
                </a>
                <a href="{{ url('/admin_acc/appointments') }}" class="analytics-card">
                    <div class="card-top-row">
                        <div class="card-icon-title">
                            <div class="card-icon-badge"><i class="bi bi-calendar-check"></i></div>
                            <div class="card-title">Today's Visits</div>
                        </div>
                        <i class="bi bi-chevron-right card-chevron" aria-hidden="true"></i>
                    </div>
                    <div class="card-value" id="stat-todays-appointments">{{ $todaysAppointments ?? 0 }}</div>
                    <div class="card-subtitle"><span class="dot" id="pending-approval-dot" data-attention="{{ ($pendingApproval ?? 0) > 0 ? 'true' : 'false' }}" aria-hidden="true"></span> <span id="stat-pending-approval">{{ $pendingApproval ?? 0 }}</span> pending approval</div>
                </a>
                <a href="{{ url('/admin_acc/patients') }}" class="analytics-card">
                    <div class="card-top-row">
                        <div class="card-icon-title">
                            <div class="card-icon-badge"><i class="bi bi-person"></i></div>
                            <div class="card-title"> Patient Count</div>
                        </div>
                        <i class="bi bi-chevron-right card-chevron" aria-hidden="true"></i>
                    </div>
                    <div class="card-value" id="stat-active-patients">{{ $activePatients ?? 0 }}</div>
                    <div class="card-subtitle"><span class="dot" aria-hidden="true"></span> <span id="stat-new-patients">{{ $newPatientsThisMonth ?? 0 }}</span> new this month</div>
                </a>
                <a href="{{ route('admin_acc.feedback.index') }}" class="analytics-card">
                    <div class="card-top-row">
                        <div class="card-icon-title">
                            <div class="card-icon-badge"><i class="bi bi-star-fill"></i></div>
                            <div class="card-title">Patient Satisfaction</div>
                        </div>
                        <i class="bi bi-chevron-right card-chevron" aria-hidden="true"></i>
                    </div>
                    <div class="card-value" id="stat-avg-rating">{{ $avgRating ?? 0 }} / 5</div>
                    <div class="card-subtitle"><i class="bi bi-star-fill dashboard-star" aria-hidden="true"></i> Average rating</div>
                </a>
            </div>

            <div class="charts-grid">
                @include('partials.dashboard_rating_leaders', ['compactAdminRatings' => true, 'ratingInterpretation' => $chartInterpretations['ratings']])

                <!-- 2) PATIENT FEEDBACK OVERVIEW (custom bars) -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-header-title">
                            <div class="chart-icon-badge"><i class="bi bi-emoji-smile"></i></div>
                            <div>
                                <h3>Patient Feedback Overview</h3>
                                <p class="panel-caption">Sentiment across analyzed reviews</p>
                            </div>
                        </div>
                    </div>
                    <div id="sentimentBars" class="sentiment-bars">
                        @foreach($sentimentRows as $row)
                            <div class="sentiment-row" data-label="{{ $row['label'] }}">
                                <div class="sentiment-row-inner">
                                    <div class="sentiment-avatar {{ $row['avatarClass'] }}"><i class="bi {{ $row['icon'] }}" aria-hidden="true"></i></div>
                                    <span class="sentiment-label">{{ $row['label'] }}</span>
                                    <div class="bar-container">
                                        <div class="sentiment-fill bar-fill" style="background: {{ $row['color'] }}; width: {{ $row['value'] }}%;"></div>
                                    </div>
                                    <span class="sentiment-value">{{ $row['value'] }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="chart-interpretation" data-chart-interpretation="sentiment">{{ $chartInterpretations['sentiment'] }}</p>
                    <div class="top-complaint-box">
                        <div class="top-complaint-icon"><i class="bi bi-chat-square-text"></i></div>
                        <div>
                            <div class="top-complaint-label">Reported concerns</div>
                            <p class="top-complaint-text">Comparisons and supporting reviews</p>
                            <button type="button" class="diagnostic-trigger" id="diagnostic-open" aria-haspopup="dialog" aria-controls="diagnostic-modal">
                                View details <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </button>
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
                    <p class="chart-interpretation" data-chart-interpretation="inquiry-status">{{ $chartInterpretations['inquiry-status'] }}</p>
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
                            @endif
                        </div>
                    </div>
                    <p class="chart-interpretation" data-chart-interpretation="services">{{ $chartInterpretations['services'] }}</p>
                </div>

                <div class="chart-card compact-chart-card visits-chart-card">
                    <div class="chart-header">
                        <div class="chart-header-title">
                            <div class="chart-icon-badge"><i class="bi bi-graph-up" aria-hidden="true"></i></div>
                            <div>
                                <h3>Patient Visits Trend</h3>
                                <p class="panel-caption" id="visits-period-description">{{ $visitPeriodLabel ?? "Weekly scheduled visits, including this week (ongoing)." }}</p>
                            </div>
                        </div>
                        @include('partials.dashboard_chart_filter', ['chartFilter' => 'visits'])
                    </div>
                    <p id="visits-filter-status" class="dashboard-chart-filter-status" role="status"></p>
                    <div class="compact-chart-plot">
                        <canvas id="weeklyVisitsChart" role="img" aria-label="Scheduled patient visits by week"></canvas>
                    </div>
                    <div class="visits-chart-legend">
                        <span>Completed weeks</span>
                        <span>This week (ongoing)</span>
                    </div>
                    <p class="chart-interpretation" data-chart-interpretation="visits">{{ $chartInterpretations['visits'] }}</p>
                </div>

                <div class="chart-card compact-chart-card inquiry-volume-card">
                    <div class="chart-header">
                        <div class="chart-header-title">
                            <div class="chart-icon-badge"><i class="bi bi-bar-chart" aria-hidden="true"></i></div>
                            <div>
                                <h3>Inquiry Volume</h3>
                                <p class="panel-caption" id="inquiry-period-description">{{ $inquiryPeriodLabel ?? "Inquiries received Monday–Sunday (Philippine time)." }}</p>
                            </div>
                        </div>
                        @include('partials.dashboard_chart_filter', ['chartFilter' => 'inquiry'])
                    </div>
                    <p id="inquiry-filter-status" class="dashboard-chart-filter-status" role="status"></p>
                    <div class="compact-chart-plot">
                        <canvas id="inquiryVolumeChart" role="img" aria-label="Inquiries received by day"></canvas>
                        <div id="inquiryVolumeEmptyNote" class="compact-chart-empty" @if($hasInquiryData) hidden @endif>
                            <strong id="inquiryVolumeEmptyTitle">{{ ($inquiryPeriod ?? 'this_week') === 'last_week' ? 'No inquiries last week' : 'No inquiries yet this week' }}</strong>
                            <span id="inquiryVolumeEmptyDetail" @if(($inquiryPeriod ?? 'this_week') === 'last_week') hidden @endif>New inquiries will show up here as they come in.</span>
                        </div>
                    </div>
                    <p class="chart-interpretation" data-chart-interpretation="inquiry-volume">{{ $chartInterpretations['inquiry-volume'] }}</p>
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

        const trendBlue = '#0e62aa';

        function compactChartOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        border: { display: false },
                        grid: { display: false },
                        ticks: { font: { size: 10 }, maxRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 5,
                        border: { display: false },
                        grid: { color: '#e5e7eb', drawTicks: false },
                        ticks: { precision: 0, maxTicksLimit: 6, padding: 8, font: { size: 10 } }
                    }
                }
            };
        }

        function visitsDatasets(visits) {
            const line = {
                label: 'Patient visits',
                borderColor: trendBlue,
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: trendBlue
            };
            const last = visits.length - 1;
            return [
                { ...line, data: visits.map((count, i) => i === last ? null : count),
                    backgroundColor: 'rgba(14, 98, 170, 0.10)', fill: 'origin' },
                { ...line, data: visits.map((count, i) => i >= last - 1 ? count : null),
                    label: 'This week (ongoing)', borderDash: [4, 3],
                    pointRadius: ({ dataIndex }) => dataIndex === last ? 3 : 0,
                    pointHitRadius: ({ dataIndex }) => dataIndex === last ? 4 : 0 }
            ];
        }

        const visitCountLabels = {
            id: 'visitCountLabels',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                ctx.save();
                ctx.font = '10px Segoe UI, sans-serif';
                ctx.fillStyle = Chart.defaults.color;
                ctx.textAlign = 'center';
                chart.data.datasets.forEach((dataset, index) => {
                    const last = dataset.data.length - 1;
                    chart.getDatasetMeta(index).data.forEach((point, i) => {
                        if (dataset.data[i] === null || (index === 1 && i !== last)) return;
                        ctx.fillText(dataset.data[i], point.x, point.y - 7);
                    });
                });
                ctx.restore();
            }
        };

        const weeklyChart = new Chart(document.getElementById('weeklyVisitsChart'), {
            type: 'line',
            data: {
                labels: @json($weekStartLabels ?? $weeklyVisitsLabels),
                datasets: visitsDatasets(@json($weeklyVisitsData))
            },
            options: { ...compactChartOptions(), layout: { padding: { top: 10, right: 8 } } },
            plugins: [visitCountLabels]
        });

        const inquiryChart = new Chart(document.getElementById('inquiryVolumeChart'), {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Inquiries',
                    data: @json($inquiryVolumeData),
                    backgroundColor: trendBlue,
                    borderRadius: 4,
                    maxBarThickness: 26
                }]
            },
            options: compactChartOptions()
        });

        function updateInquiryEmptyState(counts, period) {
            const empty = !counts.some(count => count > 0);
            const previous = period === 'last_week';
            document.getElementById('inquiryVolumeEmptyNote').hidden = !empty;
            document.getElementById('inquiryVolumeEmptyTitle').textContent =
                previous ? 'No inquiries last week' : 'No inquiries yet this week';
            document.getElementById('inquiryVolumeEmptyDetail').hidden = previous;
            inquiryChart.options.plugins.tooltip.enabled = !empty;
        }

        updateInquiryEmptyState(inquiryChart.data.datasets[0].data, @json($inquiryPeriod ?? 'this_week'));

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
                document.getElementById('pending-inquiries-dot').dataset.attention = data.pendingInquiries > 0;
                document.getElementById('pending-approval-dot').dataset.attention = data.pendingApproval > 0;
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
                    : '';

                const weekly = Array.isArray(data.weeklyVisits) ? data.weeklyVisits : [];
                weeklyChart.data.labels = data.weekStartLabels ?? data.weekLabels ?? weeklyChart.data.labels;
                weeklyChart.data.datasets = visitsDatasets(weekly);
                weeklyChart.update();

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

                const inquiryVolume = Array.isArray(data.inquiryVolumeByDay) ? data.inquiryVolumeByDay : [];
                inquiryChart.data.datasets[0].data = inquiryVolume;
                updateInquiryEmptyState(inquiryVolume, data.inquiryPeriod);
                inquiryChart.update();

                const resolvedCount = data.resolvedInquiries ?? 0;
                const unresolvedCount = data.unresolvedInquiries ?? 0;
                const hasStatusData = (resolvedCount + unresolvedCount) > 0;
                inquiryStatusChart.data.datasets[0].data = [resolvedCount, unresolvedCount];
                inquiryStatusChart.data.datasets[0].backgroundColor = hasStatusData ? ['#0E62AA', '#9DBCD4'] : [emptyGrey];
                inquiryStatusChart.options.plugins.tooltip.enabled = hasStatusData;
                inquiryStatusChart.update();
                document.getElementById('inquiryStatusResolvedValue').textContent = resolvedCount;
                document.getElementById('inquiryStatusUnresolvedValue').textContent = unresolvedCount;

                document.querySelectorAll('[data-chart-interpretation]').forEach(note => {
                    const interpretation = data.chartInterpretations?.[note.dataset.chartInterpretation];
                    if (typeof interpretation === 'string') note.textContent = interpretation;
                });
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
