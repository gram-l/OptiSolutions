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
</head>
<body>
    <script>
        if (localStorage.getItem('staffDarkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }

        if (localStorage.getItem('staffSidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
    </script>

    <!-- HEADER -->
    <div class="header">
        <div class="logo-section">
            <img src="{{ asset('images/polyclinic_logo.png') }}" alt="PolyClinic Logo" style="width: 42px; height: 42px; border-radius: 8px; object-fit: cover;">
            <h1>PolyClinic Staff</h1>
        </div>

        <div class="header-actions">
            <!-- NOTIFICATION BELL -->
            <div class="avatar-dropdown">
                <div class="notification-bell" onclick="toggleNotifPanel()">
                    <i class="bi bi-bell"></i>
                    <span class="notif-dot" id="notifDot" style="display:none;"></span>
                </div>
            </div>

            <!-- USER AVATAR -->
            <div class="avatar-dropdown">
                <div class="user-avatar" onclick="toggleUserDropdown()">
                    @if(Auth::user() && Auth::user()->profile_photo)
                        <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                    @else
                        {{ Auth::user() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'S' }}{{ Auth::user() && str_word_count(Auth::user()->name) > 1 ? strtoupper(substr(explode(' ', Auth::user()->name)[1], 0, 1)) : 'T' }}
                    @endif
                </div>
                <div class="dropdown-menu" id="staffDropdown">
                    <div class="dropdown-header">
                        <div class="user-avatar">
                            {{ Auth::user() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'S' }}{{ Auth::user() && str_word_count(Auth::user()->name) > 1 ? strtoupper(substr(explode(' ', Auth::user()->name)[1], 0, 1)) : 'T' }}
                        </div>
                        <div>
                            <strong>{{ Auth::user()->name ?? 'Staff User' }}</strong>
                            <div class="dropdown-email">{{ Auth::user()->email ?? '' }}</div>
                        </div>
                    </div>
                    <a href="#" class="dropdown-item" onclick="event.preventDefault(); openProfileModal();">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a href="#" class="dropdown-item" onclick="event.preventDefault(); openSettingsModal();">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item dropdown-logout">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- NOTIFICATION PANEL -->
    <div class="notif-panel-overlay" id="notifPanelOverlay" onclick="if(event.target === this) closeNotifPanel();">
        <div class="notif-panel" id="notifPanel">
            <div class="notif-panel-header">
                <h3>Notifications</h3>
                <button class="notif-panel-close" onclick="closeNotifPanel();" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="notif-tabs" id="notifTabs">
                <button class="notif-tab active" data-filter="all" onclick="setNotifFilter('all')">
                    All <span class="notif-tab-count" id="notifCountAll" style="display:none;"></span>
                </button>
                <button class="notif-tab" data-filter="inquiry" onclick="setNotifFilter('inquiry')">Chatbot</button>
                <button class="notif-tab" data-filter="schedule_visit" onclick="setNotifFilter('schedule_visit')">Appointments</button>
                <button class="notif-tab" data-filter="patient" onclick="setNotifFilter('patient')">Patients</button>
                <button class="notif-tab" data-filter="system" onclick="setNotifFilter('system')">System</button>
            </div>

            <div class="notif-panel-actions">
                <button type="button" onclick="markAllNotifsRead()">Mark all as read</button>
            </div>

            <div id="notifList" class="notif-list">
                <div class="notif-empty">Loading...</div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="dashboard-layout">

            <!-- SIDEBAR -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <div class="sidebar-brand">
                        <img src="{{ asset('images/polyclinic_logo.png') }}" alt="PolyClinic" class="sidebar-brand-icon">
                        <span class="sidebar-brand-text">Polyclinic</span>
                    </div>
                    <button
                        type="button"
                        id="sidebarToggleBtn"
                        class="sidebar-toggle-btn"
                        onclick="toggleSidebar()"
                        aria-label="Collapse sidebar"
                        title="Collapse sidebar"
                    >
                        <i class="bi bi-chevron-double-left"></i>
                    </button>
                </div>

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

    // PROFILE MODAL
<div class="profile-modal-overlay" id="profileModalOverlay" onclick="if(event.target === this) closeProfileModal();">
    <div class="profile-modal">
        <button class="profile-modal-close" onclick="closeProfileModal();">
            <i class="bi bi-arrow-left"></i>
        </button>

        <div class="profile-modal-photo-wrap">
            <div class="profile-modal-photo-circle">
                <div class="profile-modal-photo" id="profileModalPhoto">
                    @if(Auth::user() && Auth::user()->profile_photo)
                        <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Profile photo">
                    @else
                        {{ Auth::user() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'S' }}
                    @endif
                </div>
                <div class="profile-modal-photo-edit" onclick="document.getElementById('profilePhotoInput').click();">
                    <i class="bi bi-camera-fill" style="font-size:0.85rem;"></i>
                </div>
            </div>
            <div class="profile-modal-badge">Clinic Staff</div>
        </div>

        <form id="profilePhotoForm" method="POST" action="{{ route('staff.profile.photo') }}" enctype="multipart/form-data" style="display:none;">
            @csrf
            <input type="file" id="profilePhotoInput" name="profile_photo" accept="image/*" onchange="document.getElementById('profilePhotoForm').submit();">
        </form>

        <div class="profile-modal-section-title">Personal Information</div>

        <div class="profile-modal-field">
            <i class="bi bi-person"></i>
            <div>
                <div class="profile-modal-field-label">Name</div>
                <div class="profile-modal-field-value">{{ Auth::user()->name ?? '-' }}</div>
            </div>
        </div>

        <div class="profile-modal-field">
            <i class="bi bi-envelope"></i>
            <div>
                <div class="profile-modal-field-label">Email</div>
                <div class="profile-modal-field-value">{{ Auth::user()->email ?? '-' }}</div>
            </div>
        </div>

        <div class="profile-modal-field">
            <i class="bi bi-telephone"></i>
            <div>
                <div class="profile-modal-field-label">Contact</div>
                <div class="profile-modal-field-value">{{ Auth::user()->phone_number ?? '-' }}</div>
            </div>
        </div>

        <div class="profile-modal-field">
            <i class="bi bi-briefcase"></i>
            <div>
                <div class="profile-modal-field-label">Staff ID</div>
                <div class="profile-modal-field-value">{{ Auth::user()->user_id ?? Auth::user()->id ?? '-' }}</div>
            </div>
        </div>

        <div class="profile-modal-field">
            <i class="bi bi-building"></i>
            <div>
                <div class="profile-modal-field-label">Role</div>
                <div class="profile-modal-field-value">{{ ucfirst(Auth::user()->user_role ?? 'Staff') }}</div>
            </div>
        </div>
    </div>
</div>

    // SETTINGS MODAL 
<div class="profile-modal-overlay" id="settingsModalOverlay" onclick="if(event.target === this) closeSettingsModal();">
    <div class="settings-panel">
        <button class="settings-panel-close" onclick="closeSettingsModal();" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="settings-panel-header">
            <div class="settings-panel-title"><i class="bi bi-gear"></i> Settings</div>
            <p class="settings-panel-subtitle">Manage your account preferences</p>
        </div>

        <div class="settings-panel-body">

            {{-- LEFT: sidebar nav --}}
            <div class="settings-sidebar">
                <div class="settings-sidebar-label">General</div>
                <button type="button" class="settings-nav-item active" data-tab="notifications" onclick="switchSettingsTab('notifications')">
                    <i class="bi bi-bell"></i> Notifications
                </button>
                <button type="button" class="settings-nav-item" data-tab="appearance" onclick="switchSettingsTab('appearance')">
                    <i class="bi bi-palette"></i> Appearance
                </button>

                <div class="settings-sidebar-label">Account</div>
                <button type="button" class="settings-nav-item" data-tab="profile" onclick="switchSettingsTab('profile')">
                    <i class="bi bi-person"></i> Profile
                </button>
                <button type="button" class="settings-nav-item" data-tab="security" onclick="switchSettingsTab('security')">
                    <i class="bi bi-shield-lock"></i> Security
                </button>
                <button type="button" class="settings-nav-item" data-tab="privacy" onclick="switchSettingsTab('privacy')">
                    <i class="bi bi-shield-check"></i> Data &amp; Privacy
                </button>

                <div class="settings-sidebar-label">Clinic</div>
                <button type="button" class="settings-nav-item" data-tab="clinicinfo" onclick="switchSettingsTab('clinicinfo')">
                    <i class="bi bi-building"></i> Clinic Info
                </button>
                <button type="button" class="settings-nav-item" data-tab="workinghours" onclick="switchSettingsTab('workinghours')">
                    <i class="bi bi-clock"></i> Working Hours
                </button>

                <form method="POST" action="{{ route('logout') }}" class="settings-logout-form">
                    @csrf
                    <button type="submit" class="settings-nav-item settings-logout">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>

            {{-- RIGHT: content pane --}}
            <div class="settings-content">

                {{-- NOTIFICATIONS --}}
                <div class="settings-tab-panel active" id="settings-tab-notifications">
                    <div class="settings-content-title"><i class="bi bi-bell"></i> Notification Settings</div>
                    <p class="settings-content-subtitle">Choose what you get notified about</p>

                    <div class="settings-toggle-row">
                        <div class="settings-item-text">
                            <div class="settings-item-title">New appointment bookings</div>
                            <div class="settings-item-desc">Alert when a patient books an appointment</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" class="notif-pref-toggle" data-pref="appointments" onchange="saveNotifPref('appointments', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="settings-toggle-row">
                        <div class="settings-item-text">
                            <div class="settings-item-title">New chatbot inquiries</div>
                            <div class="settings-item-desc">Alert when a patient message needs a reply</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" class="notif-pref-toggle" data-pref="inquiries" onchange="saveNotifPref('inquiries', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="settings-toggle-row">
                        <div class="settings-item-text">
                            <div class="settings-item-title">New patient records</div>
                            <div class="settings-item-desc">Alert when a new patient record is added</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" class="notif-pref-toggle" data-pref="patients" onchange="saveNotifPref('patients', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="settings-toggle-row">
                        <div class="settings-item-text">
                            <div class="settings-item-title">Email notifications</div>
                            <div class="settings-item-desc">Also send the above to your email</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" class="notif-pref-toggle" data-pref="email" onchange="saveNotifPref('email', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                {{-- APPEARANCE --}}
                <div class="settings-tab-panel" id="settings-tab-appearance">
                    <div class="settings-content-title"><i class="bi bi-palette"></i> Appearance</div>
                    <p class="settings-content-subtitle">Customize how the dashboard looks</p>

                    <div class="settings-toggle-row">
                        <div class="settings-item-text">
                            <div class="settings-item-title">Dark Mode</div>
                            <div class="settings-item-desc">Switch the dashboard theme</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="darkModeToggle" onchange="toggleDarkMode(this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                {{-- PROFILE --}}
                <div class="settings-tab-panel" id="settings-tab-profile">
                    <div class="settings-content-title"><i class="bi bi-person-badge"></i> My Profile</div>
                    <p class="settings-content-subtitle">View your personal details</p>

                    <div class="profile-modal-photo-wrap" style="margin: 1rem 0 1.5rem;">
                        <div class="profile-modal-photo-circle">
                            <div class="profile-modal-photo">
                                @if(Auth::user() && Auth::user()->profile_photo)
                                    <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Profile photo">
                                @else
                                    {{ Auth::user() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'S' }}
                                @endif
                            </div>
                        </div>
                        <div class="profile-modal-badge">Clinic Staff</div>
                    </div>

                    <div class="profile-modal-field">
                        <i class="bi bi-person"></i>
                        <div>
                            <div class="profile-modal-field-label">Name</div>
                            <div class="profile-modal-field-value">{{ Auth::user()->name ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="profile-modal-field">
                        <i class="bi bi-envelope"></i>
                        <div>
                            <div class="profile-modal-field-label">Email</div>
                            <div class="profile-modal-field-value">{{ Auth::user()->email ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="profile-modal-field">
                        <i class="bi bi-telephone"></i>
                        <div>
                            <div class="profile-modal-field-label">Contact</div>
                            <div class="profile-modal-field-value">{{ Auth::user()->phone_number ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="profile-modal-field">
                        <i class="bi bi-briefcase"></i>
                        <div>
                            <div class="profile-modal-field-label">Staff ID</div>
                            <div class="profile-modal-field-value">{{ Auth::user()->user_id ?? Auth::user()->id ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="profile-modal-field">
                        <i class="bi bi-building"></i>
                        <div>
                            <div class="profile-modal-field-label">Role</div>
                            <div class="profile-modal-field-value">{{ ucfirst(Auth::user()->user_role ?? 'Staff') }}</div>
                        </div>
                    </div>
                </div>

                {{-- SECURITY --}}
                <div class="settings-tab-panel" id="settings-tab-security">
                    <div class="settings-content-title"><i class="bi bi-shield-lock"></i> Security</div>
                    <p class="settings-content-subtitle">Manage your password and account security</p>

                    @if($errors->has('current_password') || $errors->has('password'))
                        <div class="error-message show">
                            {{ $errors->first('current_password') ?: $errors->first('password') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('staff.profile.password') }}" id="changePasswordForm">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required autocomplete="current-password">
                        </div>

                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="password" required autocomplete="new-password" minlength="8">
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="password_confirmation" required autocomplete="new-password" minlength="8">
                        </div>

                        <button type="submit" class="btn-sm btn-primary" style="padding: 0.7rem 2rem; margin-top: 0.5rem;">Update Password</button>
                    </form>
                </div>

                {{-- DATA & PRIVACY --}}
                <div class="settings-tab-panel" id="settings-tab-privacy">
                    <div class="settings-content-title"><i class="bi bi-shield-check"></i> Data &amp; Privacy</div>
                    <p class="settings-content-subtitle">How patient data is handled</p>

                    <ul class="privacy-list">
                        <li>Patient records are encrypted and stored securely.</li>
                        <li>Only authorized staff can access patient information.</li>
                        <li>Staff access is limited based on assigned roles and permissions.</li>
                        <li>Patient information must not be shared with unauthorized persons.</li>
                        <li>All access to patient data is logged for accountability.</li>
                        <li>Any suspected unauthorized access must be reported to the administrator.</li>
                    </ul>
                </div>

                {{-- CLINIC INFO --}}
                <div class="settings-tab-panel" id="settings-tab-clinicinfo">
                    <div class="settings-content-title"><i class="bi bi-building"></i> Clinic Info</div>
                    <p class="settings-content-subtitle">Basic clinic details</p>

                    <div class="info-modal-field">
                        <div class="info-modal-label">Clinic Name</div>
                        <div class="info-modal-value">PolyClinic Lipa</div>
                    </div>
                    <div class="info-modal-field">
                        <div class="info-modal-label">Address</div>
                        <div class="info-modal-value">TM Kalaw St., Lipa City, Batangas 4217</div>
                    </div>
                    <div class="info-modal-field">
                        <div class="info-modal-label">Contact No.</div>
                        <div class="info-modal-value">0985 475 5511</div>
                    </div>
                </div>

                {{-- WORKING HOURS --}}
                <div class="settings-tab-panel" id="settings-tab-workinghours">
                    <div class="settings-content-title"><i class="bi bi-clock"></i> Working Hours</div>
                    <p class="settings-content-subtitle">Clinic operating schedule</p>

                    <div class="info-modal-field">
                        <div class="info-modal-label">Operating Hours</div>
                        <div class="info-modal-value">Monday - Friday: 8:00 AM - 6:00 PM | Saturday: 9:00 AM - 1:00 PM</div>
                    </div>
                </div>

            </div>
        </div>

        <div style="text-align:center; color:#b0b8c1; font-size:0.75rem; margin-top:14px;">
            PolyClinic Staff v2.0
        </div>
    </div>
</div>

    <script>
        // SIDEBAR COLLAPSE/EXPAND
        function toggleSidebar() {
            const collapsed = document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('staffSidebarCollapsed', collapsed);

            const btn = document.getElementById('sidebarToggleBtn');
            if (btn) {
                btn.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
                btn.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            }
        }

        //  USER AVATAR DROPDOWN
        function toggleUserDropdown() {
            document.getElementById('staffDropdown').classList.toggle('show');
        }

        //  NOTIFICATION PANEL 
        function toggleNotifPanel() {
            document.getElementById('notifPanelOverlay').classList.toggle('show');
        }
        function closeNotifPanel() {
            document.getElementById('notifPanelOverlay').classList.remove('show');
        }

        //  PROFILE MODAL
        function openProfileModal() {
            document.getElementById('staffDropdown').classList.remove('show');
            document.getElementById('profileModalOverlay').classList.add('show');
        }
        function closeProfileModal() {
            document.getElementById('profileModalOverlay').classList.remove('show');
        }

        //  SETTINGS MODAL 
        function openSettingsModal() {
            document.getElementById('staffDropdown').classList.remove('show');
            document.getElementById('settingsModalOverlay').classList.add('show');
        }
        function closeSettingsModal() {
            document.getElementById('settingsModalOverlay').classList.remove('show');
        }

        @if($errors->has('current_password') || $errors->has('password'))
            openSettingsModal();
            switchSettingsTab('security');
        @endif


        function switchSettingsTab(tab) {
            document.querySelectorAll('.settings-nav-item[data-tab]').forEach(function (btn) {
                btn.classList.toggle('active', btn.dataset.tab === tab);
            });
            document.querySelectorAll('.settings-tab-panel').forEach(function (panel) {
                panel.classList.toggle('active', panel.id === 'settings-tab-' + tab);
            });
        }


        const notifPrefDefaults = { appointments: true, inquiries: true, patients: true, email: false };

        const notifPrefKeyByType = {
            schedule_visit: 'appointments',
            inquiry: 'inquiries',
            patient: 'patients',
        };

        function loadNotifPrefs() {
            document.querySelectorAll('.notif-pref-toggle').forEach(function (input) {
                const key = input.dataset.pref;
                input.checked = getNotifPref(key);
            });
        }

        function getNotifPref(key) {
            const saved = localStorage.getItem('staffNotifPref_' + key);
            return saved !== null ? saved === 'true' : notifPrefDefaults[key];
        }

        function saveNotifPref(key, value) {
            localStorage.setItem('staffNotifPref_' + key, value);

            refreshNotifDisplay();
        }


        function isNotifTypeEnabled(type) {
            const prefKey = notifPrefKeyByType[type];
            if (!prefKey) return true; 
            return getNotifPref(prefKey);
        }

        loadNotifPrefs();

        //  DARK MODE
        function toggleDarkMode(isOn) {
            document.body.classList.toggle('dark-mode', isOn);
            localStorage.setItem('staffDarkMode', isOn);
            window.dispatchEvent(new CustomEvent('staffThemeChange', { detail: { dark: isOn } }));
        }

        document.getElementById('darkModeToggle').checked = document.body.classList.contains('dark-mode');


        window.addEventListener('click', function (e) {
            const userDropdown = document.getElementById('staffDropdown');
            const userWrapper = document.querySelector('.user-avatar')?.closest('.avatar-dropdown');
            if (userDropdown && userWrapper && !userWrapper.contains(e.target)) {
                userDropdown.classList.remove('show');
            }
        });


        const notifRoutes = {
            inquiry: "{{ route('staff.inquiries.show', ':id') }}",
            schedule_visit: "{{ route('staff.appointments') }}",
        };


        const notifTypeLabels = {
            inquiry: 'Chat inquiry',
            schedule_visit: 'Appointment',
            patient: 'Patient',
        };

        let rawNotifications = [];  
        let allNotifications = []; 
        let notifFilter = 'all';


        function notifCategory(type) {
            return ['inquiry', 'schedule_visit', 'patient'].includes(type) ? type : 'system';
        }

        function setNotifFilter(filter) {
            notifFilter = filter;
            document.querySelectorAll('.notif-tab').forEach(function (btn) {
                btn.classList.toggle('active', btn.dataset.filter === filter);
            });
            renderNotifList();
        }

        function timeAgo(dateStr) {
            if (!dateStr) return '';
            const then = new Date(dateStr);
            const diffMins = Math.floor((Date.now() - then.getTime()) / 60000);
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return diffMins + (diffMins === 1 ? ' minute ago' : ' minutes ago');
            const hours = Math.floor(diffMins / 60);
            if (hours < 24) return hours + (hours === 1 ? ' hour ago' : ' hours ago');
            const days = Math.floor(hours / 24);
            if (days < 7) return days + (days === 1 ? ' day ago' : ' days ago');
            const weeks = Math.floor(days / 7);
            if (weeks < 5) return weeks + (weeks === 1 ? ' week ago' : ' weeks ago');
            const months = Math.floor(days / 30);
            return months + (months === 1 ? ' month ago' : ' months ago');
        }

        function isToday(dateStr) {
            if (!dateStr) return false;
            return new Date(dateStr).toDateString() === new Date().toDateString();
        }

        function renderNotifEntry(n) {
            const label = notifTypeLabels[n.type] || 'System';
            return `
                <div class="notif-entry ${n.is_read ? 'is-read' : 'is-unread'}" onclick="handleNotifClick(${n.notification_id}, '${n.type}', ${n.reference_id})">
                    <div class="notif-entry-icon"><i class="bi bi-bell"></i></div>
                    <div class="notif-entry-body">
                        <div class="notif-entry-title">${n.title}</div>
                        <div class="notif-entry-meta">
                            <span class="notif-entry-time">${timeAgo(n.created_at)}</span>
                            <span class="notif-entry-badge">${label}</span>
                        </div>
                        <div class="notif-entry-message">${n.message}</div>
                    </div>
                </div>
            `;
        }

        function renderNotifList() {
            const list = document.getElementById('notifList');

            const filtered = notifFilter === 'all'
                ? allNotifications
                : allNotifications.filter(n => notifCategory(n.type) === notifFilter);

            if (filtered.length === 0) {
                list.innerHTML = '<div class="notif-empty">No notification.</div>';
                return;
            }

            const today = filtered.filter(n => isToday(n.created_at));
            const earlier = filtered.filter(n => !isToday(n.created_at));

            let html = '';
            if (today.length > 0) {
                html += '<div class="notif-group-label">Today</div>' + today.map(renderNotifEntry).join('');
            }
            if (earlier.length > 0) {
                html += '<div class="notif-group-label">Earlier</div>' + earlier.map(renderNotifEntry).join('');
            }

            list.innerHTML = html;
        }


        function refreshNotifDisplay() {
            allNotifications = rawNotifications.filter(n => isNotifTypeEnabled(n.type));

            const unreadCount = allNotifications.filter(n => !n.is_read).length;

            const dot = document.getElementById('notifDot');
            if (dot) {
                dot.style.display = unreadCount > 0 ? 'block' : 'none';
            }

            const allCountEl = document.getElementById('notifCountAll');
            if (allCountEl) {
                if (unreadCount > 0) {
                    allCountEl.textContent = unreadCount;
                    allCountEl.style.display = 'inline-flex';
                } else {
                    allCountEl.style.display = 'none';
                }
            }

            renderNotifList();
        }

        function loadNotifications() {
            fetch("{{ route('staff.notifications.index') }}")
                .then(res => res.json())
                .then(data => {
                    rawNotifications = data.notifications || [];
                    refreshNotifDisplay();
                });
        }

        function handleNotifClick(notificationId, type, referenceId) {
            fetch(`/staff/notifications/${notificationId}/read`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                }
            }).finally(() => {
                const template = notifRoutes[type];
                if (!template) return;
                window.location.href = template.replace(':id', referenceId);
            });
        }

        function markAllNotifsRead() {
            fetch("{{ route('staff.notifications.readAll') }}", {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                }
            }).then(() => loadNotifications());
        }


        loadNotifications();
        setInterval(loadNotifications, 30000);
    </script>

    @vite(['resources/js/bootstrap.js'])
    @stack('scripts')
</body>
</html>