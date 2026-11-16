<header class="dashboard-heading">
    <h1><span class="dashboard-heading-icon" aria-hidden="true"><i class="bi bi-house-door"></i></span> Dashboard</h1>
    <button type="button" class="diagnostic-trigger" id="diagnostic-open" aria-haspopup="dialog" aria-controls="diagnostic-modal">
        <i class="bi bi-bar-chart-line-fill" aria-hidden="true"></i> Diagnostic Analytics
    </button>
</header>

@php
    $staffDashboard = $staffDashboard ?? false;

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
    <a href="{{ $staffDashboard ? route('staff.inquiries') : route('admin_acc.chatbot_logs') }}" class="analytics-card">
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
    <a href="{{ $staffDashboard ? route('staff.appointments') : url('/admin_acc/appointments') }}" class="analytics-card">
        <div class="card-top-row">
            <div class="card-icon-title">
                <div class="card-icon-badge"><i class="bi bi-calendar-check"></i></div>
                <div class="card-title">Today's Visits</div>
            </div>
            <i class="bi bi-chevron-right card-chevron" aria-hidden="true"></i>
        </div>
        <div class="card-value" id="stat-todays-appointments">{{ $todaysAppointments ?? $todaysScheduledVisits ?? 0 }}</div>
    @if($staffDashboard)
        <div class="card-subtitle"><span class="dot" aria-hidden="true"></span> Scheduled for today</div>
    @else
            <div class="card-subtitle"><span class="dot" id="pending-approval-dot" data-attention="{{ ($pendingApproval ?? 0) > 0 ? 'true' : 'false' }}" aria-hidden="true"></span> <span id="stat-pending-approval">{{ $pendingApproval ?? 0 }}</span> pending approval</div>
    @endif
    </a>
    <a href="{{ $staffDashboard ? route('staff.patients') : url('/admin_acc/patients') }}" class="analytics-card">
        <div class="card-top-row">
            <div class="card-icon-title">
                <div class="card-icon-badge"><i class="bi bi-person"></i></div>
                <div class="card-title"> Patient Count</div>
            </div>
            <i class="bi bi-chevron-right card-chevron" aria-hidden="true"></i>
        </div>
        <div class="card-value" id="stat-active-patients">{{ $activePatients ?? $totalPatients ?? 0 }}</div>
    @if($staffDashboard)
        <div class="card-subtitle"><span class="dot" aria-hidden="true"></span> Total records</div>
    @else
            <div class="card-subtitle"><span class="dot" aria-hidden="true"></span> <span id="stat-new-patients">{{ $newPatientsThisMonth ?? 0 }}</span> new this month</div>
    @endif
    </a>
    @if($staffDashboard)
    <a href="{{ route('staff.doctors') }}" class="analytics-card">
            <div class="card-top-row">
                <div class="card-icon-title">
                    <div class="card-icon-badge"><i class="bi bi-person-badge"></i></div>
                    <span class="card-title">Available Doctors Today</span>
                </div>
                <i class="bi bi-chevron-right card-chevron"></i>
            </div>
            <div class="card-value" id="stat-active-doctors">{{ $availableDoctorsToday ?? 0 }}</div>
            <div class="card-subtitle"><span class="dot"></span> <span id="stat-active-doctors-sub">Scheduled for today's weekday</span></div>
        </a>
    @else
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
    @endif
</div>

<div class="charts-grid">
    @include('partials.dashboard_rating_leaders', ['compactAdminRatings' => true, 'ratingInterpretation' => $chartInterpretations['ratings'] ?? ''])

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
        <p class="chart-interpretation" data-chart-interpretation="sentiment">{{ $chartInterpretations['sentiment'] ?? '' }}</p>
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
        <p class="chart-interpretation" data-chart-interpretation="inquiry-status">{{ $chartInterpretations['inquiry-status'] ?? '' }}</p>
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
        <p class="chart-interpretation" data-chart-interpretation="services">{{ $chartInterpretations['services'] ?? '' }}</p>
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
        <p class="chart-interpretation" data-chart-interpretation="visits">{{ $chartInterpretations['visits'] ?? '' }}</p>
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
        <p class="chart-interpretation" data-chart-interpretation="inquiry-volume">{{ $chartInterpretations['inquiry-volume'] ?? '' }}</p>
    </div>
</div>

@if($staffDashboard)
    <!-- RECENT ACTIVITIES -->
    <div class="chart-card recent-activity-card">
        <div class="chart-header" style="margin-bottom: 0.9rem;">
            <div class="chart-header-title">
                <div class="chart-icon-badge"><i class="bi bi-clock-history"></i></div>
                <h3>Recent Activities</h3>
            </div>
        </div>
        <div id="recentActivityList">
            @forelse(($recentActivities ?? []) as $activity)
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
@endif

@push('scripts')
    @include('partials.portal_dashboard_script')
@endpush
