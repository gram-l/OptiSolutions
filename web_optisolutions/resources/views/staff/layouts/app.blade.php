<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PolyClinic Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/staff.css'])
    <style>
        body {
            background-color: #f0f4f8;
            position: relative;
        }

    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <div class="logo-section">
            <img src="{{ asset('images/polyclinic_logo.png') }}" alt="PolyClinic Logo" style="width: 42px; height: 42px; border-radius: 8px; object-fit: cover;">
            <h1>PolyClinic Staff</h1>
        </div>
        <div class="user-info">
            <div class="user-avatar">ST</div>
            <div>
                <strong>{{ Auth::user()->name ?? 'Staff User' }}</strong>
                <div style="font-size:0.75rem;">{{ Auth::user()->user_role ?? 'Staff' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>

    <div class="container">
        <div class="dashboard-layout">
<!-- SIDEBAR -->
    <aside class="sidebar">
        <a href="{{ route('staff.dashboard') }}" style="text-decoration: none; color: inherit; display: block;">
            <div class="nav-item {{ request()->routeIs('staff.dashboard*') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </div>
        </a>
        <a href="{{ route('staff.appointments') }}" style="text-decoration: none; color: inherit; display: block;">
            <div class="nav-item {{ request()->routeIs('staff.appointments*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check"></i> <span>Scheduled Visits</span>
            </div>
        </a>
        <a href="{{ route('staff.inquiries') }}" style="text-decoration: none; color: inherit; display: block;">
            <div class="nav-item {{ request()->routeIs('staff.inquiries*') ? 'active' : '' }}">
                <i class="bi bi-chat-dots"></i> <span>Chatbot Inquiries</span>
            </div>
        </a>
        <a href="{{ route('staff.doctors') }}" style="text-decoration: none; color: inherit; display: block;">
            <div class="nav-item {{ request()->routeIs('staff.doctors*') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i> <span>Manage Doctors</span>
            </div>
        </a>
        <a href="{{ route('staff.patients') }}" style="text-decoration: none; color: inherit; display: block;">
            <div class="nav-item {{ request()->routeIs('staff.patients*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> <span>Patient Records</span>
            </div>
        </a>
    </aside>

            <!-- MAIN CONTENT -->
            <main class="main-content">
                @if(session('success'))
                    <div class="popup-toast">{{ session('success') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    @vite(['resources/js/bootstrap.js'])
    @stack('scripts')
</body>
</html>