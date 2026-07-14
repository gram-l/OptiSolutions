@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number" id="stat-schedule-visit">{{ $totalScheduleVisit ?? 0 }}</div>
            <div class="stat-label">Total Schedule Visit</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="stat-pending-inquiries">{{ $pendingInquiries ?? 0 }}</div>
            <div class="stat-label">Pending Inquiries</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="stat-active-doctors">{{ $activeDoctors ?? 0 }}</div>
            <div class="stat-label">Active Doctors</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="stat-total-patients">{{ $totalPatients ?? 0 }}</div>
            <div class="stat-label">Registered Patients</div>
        </div>
    </div>

    {{--
        NOTE ON DEFAULTS:
        Each chart below ALWAYS renders its <canvas>, the same way the Flutter
        (mobile) dashboard always renders PieChart/LineChart/BarChart even when
        the underlying list is empty. If the controller doesn't pass a variable,
        or passes an empty collection, we fall back to a zero-filled dataset so
        the chart still shows its axes/shape instead of just a text message.
    --}}
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
    @endphp

    <!-- 1) SERVICE DISTRIBUTION (doughnut) -->
    <div style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); margin-top: 2rem;">
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
    <div style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); margin-top: 2rem;">
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
    <div style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); margin-top: 2rem;">
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
    <div style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); margin-top: 2rem;">
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
    <div style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); margin-top: 2rem;">
        <h3 style="color: var(--text-dark);">Recent Activity</h3>
        <div style="padding: 0.8rem 0; border-bottom:1px solid var(--light-gray);">
            <i class="bi bi-chat-right-text" style="color:var(--primary-deep-blue); margin-right: 10px;"></i>
            Welcome to the Staff Dashboard!
        </div>
        <div style="padding: 0.8rem 0; border-bottom:1px solid var(--light-gray);">
            <i class="bi bi-calendar-check" style="color:var(--primary-deep-blue); margin-right: 10px;"></i>
            <span id="activity-schedule-visit">{{ $totalScheduleVisit ?? 0 }}</span> total schedule visits
        </div>
        <div style="padding: 0.8rem 0;">
            <i class="bi bi-people" style="color:var(--primary-deep-blue); margin-right: 10px;"></i>
            <span id="activity-total-patients">{{ $totalPatients ?? 0 }}</span> registered patients
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
    const emptyGrey = '#e0e0e0';

    // ------------------------------------------------------------------
    // IMPORTANT: this URL must point to a JSON endpoint in your Laravel
    // app that returns fresh data from the database (see the controller
    // method + route sample provided separately). Update the path below
    // to match whatever route you register.
    // ------------------------------------------------------------------
    const DASHBOARD_DATA_URL = '{{ route("staff.dashboard.data") }}';
    const REFRESH_INTERVAL_MS = 30000; // 30 seconds — adjust as needed

    // ---- Initial chart setup using the PHP-rendered values (first paint) ----
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
                backgroundColor: 'rgba(26,35,126,0.08)',
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

    // ------------------------------------------------------------------
    // Polling: fetch fresh JSON from the server every REFRESH_INTERVAL_MS
    // and update the charts + stat cards WITHOUT reloading the page.
    // ------------------------------------------------------------------
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
            document.getElementById('activity-schedule-visit').textContent = data.totalScheduleVisit ?? 0;
            document.getElementById('activity-total-patients').textContent = data.totalPatients ?? 0;

            // ---- 1) Service Distribution ----
            const services = Array.isArray(data.serviceDistribution) ? data.serviceDistribution : [];
            const hasServices = services.length > 0;
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