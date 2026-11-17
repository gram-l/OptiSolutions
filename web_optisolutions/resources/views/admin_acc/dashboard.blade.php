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

        <main class="main-content portal-dashboard">
            @include('partials.portal_dashboard', [
                'dashboardDataRoute' => 'admin.dashboard.data',
                'ratingsOverviewUrl' => route('admin_acc.feedback.index'),
            ])
        </main>
    </div>
    @include('admin_acc.partials.diagnostic_analytics')

    @stack('scripts')
</body>
</html>
