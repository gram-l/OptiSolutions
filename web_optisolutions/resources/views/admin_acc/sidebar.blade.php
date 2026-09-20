<aside class="sidebar">
    <script>
        // Applied immediately (before the rest of the sidebar renders) so
        // there's no flash of the expanded sidebar on page load when the
        // user last left it collapsed.
        if (localStorage.getItem('sidebarCollapsed') === '1') {
            document.currentScript.closest('aside').classList.add('collapsed');
        }
    </script>

    <div class="sidebar-brand-row">
        <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Collapse sidebar">
            <i class="bi bi-chevron-double-left"></i>
        </button>
    </div>

    <nav>
        <div class="nav-item {{ request()->is('admin_acc/dashboard') ? 'active' : '' }}"
             title="Dashboard" data-label="Dashboard"
             onclick="window.location.href='/admin_acc/dashboard'">
            <div class="nav-icon"><i class="bi bi-bar-chart-line"></i></div>
            <span>Dashboard</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/chatbot*') ? 'active' : '' }}"
             title="Chatbot Inquiries" data-label="Chatbot Inquiries"
             onclick="window.location.href='/admin_acc/chatbot_logs'">
            <div class="nav-icon"><i class="fa-regular fa-comment-dots"></i></div>
            <span>Chatbot Inquiries</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/appointments*') ? 'active' : '' }}"
             title="Appointments" data-label="Appointments"
             onclick="window.location.href='/admin_acc/appointments'">
            <div class="nav-icon"><i class="fa-solid fa-book-medical"></i></div>
            <span>Scheduled Visits</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/doctors*') ? 'active' : '' }}"
             title="Manage Doctors" data-label="Manage Doctors"
             onclick="window.location.href='/admin_acc/doctors'">
            <div class="nav-icon"><i class="fa-solid fa-user-doctor"></i></div>
            <span>Manage Doctors</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/patients*') ? 'active' : '' }}"
             title="Patient Records" data-label="Patient Records"
             onclick="window.location.href='/admin_acc/patients'">
            <div class="nav-icon"><i class="fa-regular fa-hospital"></i></div>
            <span>Patient Records</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/feedback*') ? 'active' : '' }}"
             title="Patient Feedback" data-label="Patient Feedback"
             onclick="window.location.href='/admin_acc/feedback'">
            <div class="nav-icon"><i class="fa-regular fa-star"></i></div>
            <span>Patient Feedback</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/user_management*') ? 'active' : '' }}"
             title="User Management" data-label="User Management"
             onclick="window.location.href='/admin_acc/user_management'">
            <div class="nav-icon"><i class="fa-solid fa-users"></i></div>
            <span>User Management</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/system_settings*') ? 'active' : '' }}"
             title="System Settings" data-label="System Settings"
             onclick="window.location.href='/admin_acc/system_settings'">
            <div class="nav-icon"><i class="fa-solid fa-cog"></i></div>
            <span>System Settings</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/chatbot-commands*') ? 'active' : '' }}"
             title="Chatbot Commands" data-label="Chatbot Commands"
             onclick="window.location.href='/admin_acc/chatbot-commands'">
            <div class="nav-icon"><i class="fa-solid fa-terminal"></i></div>
            <span>Chatbot Commands</span>
        </div>
    </nav>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
    (function () {
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        const icon = toggleBtn.querySelector('i');

        function syncIcon() {
            icon.className = sidebar.classList.contains('collapsed')
                ? 'bi bi-chevron-double-right'
                : 'bi bi-chevron-double-left';
            toggleBtn.setAttribute('aria-label', sidebar.classList.contains('collapsed') ? 'Expand sidebar' : 'Collapse sidebar');
        }
        syncIcon();

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
            syncIcon();
        });

        // ── Mobile: hamburger-triggered slide-out drawer ──────────────
        // Independent of the desktop collapse toggle above — on phones
        // the sidebar is off-canvas by default (see sidebar.css) and
        // slides in over the page instead of squeezing the layout.
        const menuBtn = document.getElementById('mobileMenuBtn');
        const overlay = document.getElementById('sidebarOverlay');

        function openMobileSidebar() {
            sidebar.classList.add('mobile-open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileSidebar() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        menuBtn?.addEventListener('click', openMobileSidebar);
        overlay?.addEventListener('click', closeMobileSidebar);

        sidebar.querySelectorAll('.nav-item').forEach((item) => {
            item.addEventListener('click', closeMobileSidebar);
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) closeMobileSidebar();
        });
    })();
</script>