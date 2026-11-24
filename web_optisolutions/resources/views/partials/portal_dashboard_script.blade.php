@include('partials.dashboard_chart_filters_script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const navy = '#062744';
        const emptyGrey = '#e0e0e0';

        const DASHBOARD_DATA_URL = '{{ route($dashboardDataRoute) }}';
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

        function esc(value) {
            return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
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
                const data = await chartFilters.load(DASHBOARD_DATA_URL);
                if (!data) return;
                window.updateDashboardRatingLeaders(data);

                document.getElementById('stat-pending-inquiries').textContent = data.pendingInquiries ?? 0;
                document.getElementById('stat-todays-appointments').textContent = data.todaysAppointments ?? data.todaysScheduledVisits ?? 0;
                document.getElementById('stat-active-patients').textContent = data.activePatients ?? data.totalPatients ?? 0;
                document.getElementById('pending-inquiries-dot').dataset.attention = data.pendingInquiries > 0;
                const availableDoctors = document.getElementById('stat-active-doctors');
                if (availableDoctors) {
                    availableDoctors.textContent = data.availableDoctorsToday ?? 0;
                    renderRecentActivities(data.recentActivities);
                } else {
                    document.getElementById('stat-pending-approval').textContent = data.pendingApproval ?? 0;
                    document.getElementById('pending-approval-dot').dataset.attention = data.pendingApproval > 0;
                    document.getElementById('stat-new-patients').textContent = data.newPatientsThisMonth ?? 0;
                    document.getElementById('stat-avg-rating').textContent = (data.avgRating ?? 0) + ' / 5';
                }

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
                                ${esc(s.service_type)}
                            </span>
                            <span class="donut-legend-value">${esc(s.total)} visits</span>
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
                console.error('Dashboard refresh failed:', err);
            }
        }

        setInterval(refreshDashboard, REFRESH_INTERVAL_MS);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) refreshDashboard();
        });
    });
</script>