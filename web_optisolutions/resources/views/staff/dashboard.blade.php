@extends('staff.layouts.app')

@section('content')
    @include('partials.mobile_dashboard_charts')
    <div class="portal-dashboard">
        @include('partials.portal_dashboard', [
            'staffDashboard' => true,
            'dashboardDataRoute' => 'staff.dashboard.data',
        ])
    </div>

    @include('admin_acc.partials.diagnostic_analytics', [
        'diagnosticDashboardRoute' => 'staff.dashboard',
        'diagnosticCompareRoute' => 'staff.dashboard.diagnostics',
        'diagnosticStaff' => true,
    ])
@endsection
