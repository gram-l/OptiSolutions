


<aside class="sidebar">
    <nav>
        <div class="nav-item {{ request()->is('admin_acc/dashboard') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/dashboard'">
            <div class="nav-icon"><i class="bi bi-bar-chart-line"></i></div>
            <span>Dashboard</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/chatbot*') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/chatbot_logs'">
            <div class="nav-icon"><i class="fa-regular fa-comment-dots"></i></div>
            <span>Chatbot Inquiries</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/appointments*') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/appointments'">
            <div class="nav-icon"><i class="fa-solid fa-book-medical"></i></div>
            <span>Appointments</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/doctors*') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/doctors'">
            <div class="nav-icon"><i class="fa-solid fa-user-doctor"></i></div>
            <span>Manage Doctors</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/patients*') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/patients'">
            <div class="nav-icon"><i class="fa-regular fa-hospital"></i></div>
            <span>Patient Records</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/feedback*') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/feedback'">
            <div class="nav-icon"><i class="fa-regular fa-star"></i></div>
            <span>Patient Feedback</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/user_management*') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/user_management'">
            <div class="nav-icon"><i class="fa-solid fa-users"></i></div>
            <span>User Management</span>
        </div>
        <div class="nav-item {{ request()->is('admin_acc/system_settings*') ? 'active' : '' }}"
             onclick="window.location.href='/admin_acc/system_settings'">
            <div class="nav-icon"><i class="fa-solid fa-cog"></i></div>
            <span>System Settings</span>
        </div>
    </nav>
    </nav>
</aside>