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
    @include('admin_acc.header')

    <div class="container">
        @include('admin_acc.sidebar')

        <main class="main-content">
            <div class="welcome-banner">
                <h2>Welcome, {{ Auth::user()->name }}!</h2>
                <p>Here's what's happening with your clinic today.</p>
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

                // ---- Patient Feedback defaults ----
                $sentimentTotal = ($positivePercent ?? 0) + ($neutralPercent ?? 0) + ($negativePercent ?? 0);
                $sentimentRows = [
                    ['label' => 'Positive', 'value' => $sentimentTotal > 0 ? ($positivePercent ?? 0) : 0, 'color' => '#0E62AA'],
                    ['label' => 'Neutral',  'value' => $sentimentTotal > 0 ? ($neutralPercent ?? 0)  : 0, 'color' => '#9DBCD4'],
                    ['label' => 'Negative', 'value' => $sentimentTotal > 0 ? ($negativePercent ?? 0) : 0, 'color' => '#062744'],
                ];

                // ---- Inquiry Volume defaults ----
                $hasInquiryData = isset($inquiryVolumeByDay) && count($inquiryVolumeByDay) > 0;
                $inquiryVolumeData = $hasInquiryData ? $inquiryVolumeByDay : array_fill(0, 7, 0);
            @endphp

            <!-- Analytics Cards -->
            <div class="analytics-grid">
                <div class="analytics-card">
                    <div class="card-title">Total Inquiries</div>
                    <div class="card-value" id="stat-total-inquiries">{{ $totalInquiries ?? 0 }}</div>
                    <div class="card-subtitle">Live count</div>
                </div>
                <div class="analytics-card">
                    <div class="card-title">Today's Appointments</div>
                    <div class="card-value" id="stat-todays-appointments">{{ $todaysAppointments ?? 0 }}</div>
                    <div class="card-subtitle"><span id="stat-pending-approval">{{ $pendingApproval ?? 0 }}</span> pending approval</div>
                </div>
                <div class="analytics-card">
                    <div class="card-title">Active Patients</div>
                    <div class="card-value" id="stat-active-patients">{{ $activePatients ?? 0 }}</div>
                    <div class="card-subtitle"><span id="stat-new-patients">{{ $newPatientsThisMonth ?? 0 }}</span> new this month</div>
                </div>
                <div class="analytics-card">
                    <div class="card-title">Patient Satisfaction</div>
                    <div class="card-value" id="stat-avg-rating">{{ $avgRating ?? 0 }}</div>
                    <div class="card-subtitle">⭐ Average rating</div>
                </div>
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
                <!-- 1) SERVICE DISTRIBUTION (doughnut) -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Service Distribution</h3>
                    </div>
                    <p style="color: #7f8c8d; font-size: 0.9rem;">Distribution of patient visits by service</p>
                    <div style="max-width: 340px; margin: 1rem auto 1.5rem;">
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
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Weekly Patient Visits</h3>
                    </div>
                    <p style="color: #7f8c8d; font-size: 0.9rem;">Visits over the last several weeks</p>
                    <div style="margin-top: 1rem;">
                        <canvas id="weeklyVisitsChart" height="240"></canvas>
                    </div>
                    <p id="weeklyVisitsEmptyNote" style="color: #7f8c8d; margin-top: 10px; font-size: 0.85rem; {{ $hasWeeklyData ? 'display:none;' : '' }}">
                        No visit data available yet.
                    </p>
                </div>

                <!-- 3) INQUIRY VOLUME (bar) -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Inquiry Volume (Last 7 Days)</h3>
                        <button class="filter-btn">This Week ▼</button>
                    </div>
                    <div style="margin-top: 1rem;">
                        <canvas id="inquiryVolumeChart" height="240"></canvas>
                    </div>
                    <p id="inquiryVolumeEmptyNote" style="color: #7f8c8d; margin-top: 10px; font-size: 0.85rem; {{ $hasInquiryData ? 'display:none;' : '' }}">
                        No inquiry data available yet.
                    </p>
                </div>

                <!-- 4) PATIENT FEEDBACK OVERVIEW (custom bars) -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Patient Feedback Overview</h3>
                    </div>
                    <div id="sentimentBars" class="sentiment-bars" style="margin-top: 1.25rem;">
                        @foreach($sentimentRows as $row)
                            <div class="sentiment-row" data-label="{{ $row['label'] }}" data-color="{{ $row['color'] }}" style="margin-bottom: 16px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px;">
                                    <span style="font-weight: 600;">
                                        @if($row['label'] === 'Positive') 😊 @elseif($row['label'] === 'Neutral') 😐 @else 😟 @endif
                                        {{ $row['label'] }}
                                    </span>
                                    <span class="sentiment-value" style="font-weight: 700; color: {{ $row['color'] }};">{{ $row['value'] }}%</span>
                                </div>
                                <div class="bar-container">
                                    <div class="sentiment-fill bar-fill" style="background: {{ $row['color'] }}; width: {{ $row['value'] }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p id="sentimentEmptyNote" style="color: #7f8c8d; margin-top: 10px; font-size: 0.85rem; {{ $sentimentTotal > 0 ? 'display:none;' : '' }}">
                        No feedback data available yet.
                    </p>
                    <div style="margin-top: 2rem; padding: 1rem; background: var(--light-gray); border-radius: 10px;">
                        <div style="font-size: 0.9rem; font-weight: 600; margin-bottom: 0.5rem;">Top Complaint:</div>
                        <div style="font-size: 0.85rem; color: #7f8c8d;">Long waiting times at reception</div>
                    </div>
                </div>
            </div>

            

        </main>
    </div>

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
                    legend: { display: hasServiceData, position: 'bottom' },
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

        async function refreshDashboard() {
            try {
                const res = await fetch(DASHBOARD_DATA_URL, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });
                if (!res.ok) throw new Error('Bad response: ' + res.status);
                const data = await res.json();

                document.getElementById('stat-total-inquiries').textContent = data.totalInquiries ?? 0;
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
                serviceChart.options.plugins.legend.display = hasServices;
                serviceChart.options.plugins.tooltip.enabled = hasServices;
                serviceChart.update();

                const listEl = document.getElementById('serviceDistributionList');
                listEl.innerHTML = hasServices
                    ? services.map(s => `
                        <div style="display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid var(--light-gray);">
                            <span>${s.service_type}</span>
                            <span>${s.total} visits</span>
                        </div>`).join('')
                    : '<p style="color:#7f8c8d; margin-top:10px; font-size:0.85rem;">No service data available yet.</p>';

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