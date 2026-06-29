
<aside class="sidebar" id="adminSidebar">

    {{-- Toggle Button --}}
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-chevron-left" id="toggleIcon"></i>
    </button>

    {{-- Logo --}}
    <div class="sidebar-logo">
        <img src="{{ asset('polyclinic_logo.png') }}" alt="logo">
        <span class="sidebar-logo-text">Polyclinic Admin</span>
    </div>

    {{-- Nav Items --}}
    <nav class="sidebar-nav">
        <div class="nav-item {{ request()->is('admin_acc/dashboard') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/dashboard'"
             title="Dashboard">
            <div class="nav-icon"><i class="bi bi-bar-chart-line"></i></div>
            <span class="nav-label">Dashboard</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/chatbot*') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/chatbot_logs'"
             title="Chatbot Inquiries">
            <div class="nav-icon"><i class="fa-regular fa-comment-dots"></i></div>
            <span class="nav-label">Chatbot Inquiries</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/appointments*') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/appointments'"
             title="Appointments">
            <div class="nav-icon"><i class="fa-solid fa-book-medical"></i></div>
            <span class="nav-label">Appointments</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/doctors*') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/doctors'"
             title="Manage Doctors">
            <div class="nav-icon"><i class="fa-solid fa-user-doctor"></i></div>
            <span class="nav-label">Manage Doctors</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/patients*') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/patients'"
             title="Patient Records">
            <div class="nav-icon"><i class="fa-regular fa-hospital"></i></div>
            <span class="nav-label">Patient Records</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/feedback*') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/feedback'"
             title="Patient Feedback">
            <div class="nav-icon"><i class="fa-regular fa-star"></i></div>
            <span class="nav-label">Patient Feedback</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/users*') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/users'"
             title="User Management">
            <div class="nav-icon"><i class="fa-solid fa-users"></i></div>
            <span class="nav-label">User Management</span>
        </div>
    </nav>

    {{-- Logout at bottom --}}
   <div class="nav-item logout-item" title="Logout" onclick="confirmLogout()">
    <div class="nav-icon"><i class="bi bi-box-arrow-right"></i></div>
    <span class="nav-label">Logout</span>
</div>
    </div>
</aside>

<script>
    (function () {
        const sidebar   = document.getElementById('adminSidebar');
        const toggle    = document.getElementById('sidebarToggle');
        const icon      = document.getElementById('toggleIcon');
        const COLLAPSED = 'sidebar-collapsed';

        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add(COLLAPSED);
            icon.classList.replace('bi-chevron-left', 'bi-chevron-right');
        }

        toggle.addEventListener('click', function () {
            const isCollapsed = sidebar.classList.toggle(COLLAPSED);
            icon.classList.toggle('bi-chevron-left', !isCollapsed);
            icon.classList.toggle('bi-chevron-right', isCollapsed);
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    })();
</script>
