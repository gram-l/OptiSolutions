
<header class="header">

    {{-- Logo --}}
    <a class="logo-section" href="/admin_acc/dashboard">
        <img src="{{ asset('images/polyclinic_logo.png') }}" alt="Polyclinic Logo">
        <h1>Polyclinic Admin</h1>
    </a>

    {{-- Right Controls --}}
    <div class="header-controls">

        {{-- Notification Bell --}}
        <div class="notif-wrapper">
            <button class="icon-btn" id="notifBtn" aria-label="Notifications">
                <i class="bi bi-bell"></i>
                <span class="notif-dot" id="notifDot"></span>
            </button>

            <div class="dropdown" id="notifDropdown">
                <div class="dropdown-label">Notifications</div>

                <div class="notif-item">
                    <div class="notif-icon-circle"><i class="bi bi-chat-dots"></i></div>
                    <div class="notif-text">
                        <p>New chatbot inquiry from <strong>CHAT-006</strong></p>
                        <span>2 minutes ago</span>
                    </div>
                    <div class="notif-unread-dot"></div>
                </div>

                <div class="notif-item">
                    <div class="notif-icon-circle"><i class="bi bi-calendar-check"></i></div>
                    <div class="notif-text">
                        <p>Appointment confirmed — <strong>P-12345</strong> on June 15</p>
                        <span>1 hour ago</span>
                    </div>
                    <div class="notif-unread-dot"></div>
                </div>

                <div class="notif-item">
                    <div class="notif-icon-circle"><i class="bi bi-person-plus"></i></div>
                    <div class="notif-text">
                        <p>New patient record added by Staff</p>
                        <span>Yesterday</span>
                    </div>
                </div>

                <div class="dropdown-footer">
                    <a href="/admin_acc/notifications">View all notifications</a>
                </div>
            </div>
        </div>

        {{-- Avatar / Profile --}}
        <div class="avatar-wrapper">
            <button class="avatar-btn" id="avatarBtn" aria-label="Profile menu">
    @if (Auth::user()->profile_photo)
        <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Profile" class="avatar-img">
    @else
        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
    @endif
</button>

            <div class="dropdown" id="avatarDropdown">
                <div class="profile-header">
                    <div class="profile-avatar-lg">
                        @if (Auth::user()->profile_photo)
                            <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Profile" class="avatar-img">
                        @else
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        @endif
                    </div>
                    <div class="profile-info">
                        <p class="profile-name">{{ Auth::user()->name }}</p>
                        <p class="profile-email">{{ Auth::user()->email }}</p>
                    </div>
                </div>

                <a class="menu-item" href="#" onclick="openProfileModal(); return false;">
    <i class="bi bi-person"></i> Profile
</a>
                <a class="menu-item" href="/admin_acc/settings">
                    <i class="bi bi-gear"></i> Settings
                </a>
                <button class="menu-item danger" onclick="window.location.href='/auth/login'">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </div>
        </div>

    </div>
    @include('admin_acc.partials.profile')
</header>

<script>
    // ── Toggle helper ──────────────────────────────────────────────
    function toggleDropdown(btnId, dropdownId, otherDropdownId) {
        const btn      = document.getElementById(btnId);
        const dropdown = document.getElementById(dropdownId);
        const other    = document.getElementById(otherDropdownId);

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            other.classList.remove('open');            // close the other one
            dropdown.classList.toggle('open');
        });
    }

    toggleDropdown('notifBtn',  'notifDropdown',  'avatarDropdown');
    toggleDropdown('avatarBtn', 'avatarDropdown', 'notifDropdown');

    // Close both when clicking outside
    document.addEventListener('click', () => {
        document.getElementById('notifDropdown').classList.remove('open');
        document.getElementById('avatarDropdown').classList.remove('open');
    });

    // ── Notification badge ─────────────────────────────────────────
    // Count items that have an unread dot and show/hide the red badge
    const unreadCount = document.querySelectorAll('#notifDropdown .notif-unread-dot').length;
    if (unreadCount > 0) {
        document.getElementById('notifDot').classList.add('visible');
    }
</script>