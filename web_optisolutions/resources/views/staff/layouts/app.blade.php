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
        // Apply saved dark mode agad bago mag-render, para walang flash
        if (localStorage.getItem('staffDarkMode') === 'true') {
            document.body.classList.add('dark-mode');
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
                <div class="notification-bell" onclick="toggleNotifDropdown()">
                    <i class="bi bi-bell"></i>
                    <span class="notif-dot" id="notifDot" style="display:none;"></span>
                </div>
                <div class="dropdown-menu" id="notifDropdown">
                    <div class="dropdown-header">
                        <strong>Notifications</strong>
                        <button onclick="markAllNotifsRead()" style="margin-left:auto; background:none; border:none; color:var(--primary-main); font-size:0.8rem; cursor:pointer;">Mark all as read</button>
                    </div>
                    <div id="notifList" style="max-height: 320px; overflow-y:auto;">
                        <div class="dropdown-item">Loading...</div>
                    </div>
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

<!-- ===== PROFILE MODAL (EDITABLE - mula sa navbar avatar dropdown) ===== -->
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

<!-- ===== MY PROFILE MODAL (VIEW-ONLY - mula sa loob ng Settings) ===== -->
<div class="profile-modal-overlay" id="profileViewModalOverlay" onclick="if(event.target === this) closeProfileViewModal();">
    <div class="profile-modal">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div class="info-modal-title" style="margin-bottom:0;">
                <i class="bi bi-person-badge"></i> My Profile
            </div>
            <button onclick="closeProfileViewModal();" style="background:none; border:none; font-size:1.3rem; cursor:pointer; color:#95a5a6;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="profile-modal-photo-wrap">
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

    <!-- ===== SETTINGS MODAL ===== -->
    <div class="profile-modal-overlay" id="settingsModalOverlay" onclick="if(event.target === this) closeSettingsModal();">
        <div class="profile-modal settings-modal">
            <button class="profile-modal-close" onclick="closeSettingsModal();">
                <i class="bi bi-arrow-left"></i>
            </button>

            <div style="margin-top: 6px;">
                <div style="font-size:1.2rem; font-weight:700; color:var(--text-dark);">Settings</div>
                <div class="settings-subtitle">Manage your account preferences</div>
            </div>

            <div class="settings-section-label">
                <i class="bi bi-person"></i> Personal Information
            </div>
            <button type="button" class="settings-item" onclick="openProfileViewModal();">
                <div class="settings-item-icon blue"><i class="bi bi-person-badge"></i></div>
                <div class="settings-item-text">
                    <div class="settings-item-title">My Profile</div>
                    <div class="settings-item-desc">View your personal details</div>
                </div>
                <i class="bi bi-chevron-right settings-item-chevron"></i>
            </button>

            <div class="settings-section-label">
                <i class="bi bi-plus-square"></i> Clinic Information
            </div>
            <button type="button" class="settings-item" onclick="openClinicInfoModal();">
                <div class="settings-item-icon purple"><i class="bi bi-plus-square"></i></div>
                <div class="settings-item-text">
                    <div class="settings-item-title">Clinic Information</div>
                    <div class="settings-item-desc">View clinic details</div>
                </div>
                <i class="bi bi-chevron-right settings-item-chevron"></i>
            </button>

            <div class="settings-section-label">
                <i class="bi bi-question-circle"></i> Support
            </div>
            <button type="button" class="settings-item" onclick="openDataPrivacyModal();">
                <div class="settings-item-icon gray"><i class="bi bi-shield-check"></i></div>
                <div class="settings-item-text">
                    <div class="settings-item-title">Data &amp; Privacy</div>
                    <div class="settings-item-desc">How patient data is handled</div>
                </div>
                <i class="bi bi-chevron-right settings-item-chevron"></i>
            </button>

            <div class="settings-toggle-row">
                <div class="settings-item-icon gray"><i class="bi bi-moon-stars"></i></div>
                <div class="settings-item-text">
                    <div class="settings-item-title">Dark Mode</div>
                    <div class="settings-item-desc">Switch the dashboard theme</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="darkModeToggle" onchange="toggleDarkMode(this.checked)">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div style="text-align:center; color:#b0b8c1; font-size:0.75rem; margin-top:18px;">
                PolyClinic Staff v2.0
            </div>
        </div>
    </div>

    <!-- ===== CLINIC INFORMATION MODAL ===== -->
    <div class="profile-modal-overlay" id="clinicInfoModalOverlay" onclick="if(event.target === this) closeClinicInfoModal();">
        <div class="profile-modal" style="width:380px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div class="info-modal-title" style="margin-bottom:0;">
                    <i class="bi bi-plus-square"></i> Clinic Information
                </div>
                <button onclick="closeClinicInfoModal();" style="background:none; border:none; font-size:1.3rem; cursor:pointer; color:#95a5a6;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div style="margin-top:18px;">
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
                <div class="info-modal-field">
                    <div class="info-modal-label">Operating Hours</div>
                    <div class="info-modal-value">Monday - Friday: 8:00 AM - 6:00 PM | Saturday: 9:00 AM - 1:00 PM</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== DATA & PRIVACY MODAL ===== -->
    <div class="profile-modal-overlay" id="dataPrivacyModalOverlay" onclick="if(event.target === this) closeDataPrivacyModal();">
        <div class="profile-modal" style="width:380px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div class="info-modal-title" style="margin-bottom:0;">
                    <i class="bi bi-shield-check"></i> Data &amp; Privacy
                </div>
                <button onclick="closeDataPrivacyModal();" style="background:none; border:none; font-size:1.3rem; cursor:pointer; color:#95a5a6;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div style="margin-top:18px;">
                <div style="font-weight:600; color:var(--text-dark); margin-bottom:10px;">How patient data is handled:</div>
                <ul class="privacy-list">
                    <li>Patient records are encrypted and stored securely.</li>
                    <li>Only authorized staff can access patient information.</li>
                    <li>Data is used strictly for clinical and administrative purposes.</li>
                    <li>All access to patient data is logged for accountability.</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // ===== USER AVATAR DROPDOWN =====
        function toggleUserDropdown() {
            document.getElementById('staffDropdown').classList.toggle('show');
        }

        // ===== NOTIFICATION DROPDOWN =====
        function toggleNotifDropdown() {
            document.getElementById('notifDropdown').classList.toggle('show');
        }

        // ===== PROFILE MODAL (editable) =====
        function openProfileModal() {
            document.getElementById('staffDropdown').classList.remove('show');
            document.getElementById('profileModalOverlay').classList.add('show');
        }
        function closeProfileModal() {
            document.getElementById('profileModalOverlay').classList.remove('show');
        }

        // ===== MY PROFILE MODAL (view-only, mula sa Settings) =====
        function openProfileViewModal() {
            document.getElementById('settingsModalOverlay').classList.remove('show');
            document.getElementById('profileViewModalOverlay').classList.add('show');
        }
        function closeProfileViewModal() {
            document.getElementById('profileViewModalOverlay').classList.remove('show');
            document.getElementById('settingsModalOverlay').classList.add('show');
        }

        // ===== SETTINGS MODAL =====
        function openSettingsModal() {
            document.getElementById('staffDropdown').classList.remove('show');
            document.getElementById('settingsModalOverlay').classList.add('show');
        }
        function closeSettingsModal() {
            document.getElementById('settingsModalOverlay').classList.remove('show');
        }

        // ===== CLINIC INFO MODAL =====
        function openClinicInfoModal() {
            document.getElementById('clinicInfoModalOverlay').classList.add('show');
        }
        function closeClinicInfoModal() {
            document.getElementById('clinicInfoModalOverlay').classList.remove('show');
        }

        // ===== DATA & PRIVACY MODAL =====
        function openDataPrivacyModal() {
            document.getElementById('dataPrivacyModalOverlay').classList.add('show');
        }
        function closeDataPrivacyModal() {
            document.getElementById('dataPrivacyModalOverlay').classList.remove('show');
        }

        // ===== DARK MODE =====
        function toggleDarkMode(isOn) {
            document.body.classList.toggle('dark-mode', isOn);
            localStorage.setItem('staffDarkMode', isOn);
            window.dispatchEvent(new CustomEvent('staffThemeChange', { detail: { dark: isOn } }));
        }
        // i-sync yung toggle switch state sa saved preference
        document.getElementById('darkModeToggle').checked = document.body.classList.contains('dark-mode');

        // close dropdowns pag nag-click sa labas
        window.addEventListener('click', function (e) {
            const userDropdown = document.getElementById('staffDropdown');
            const userWrapper = document.querySelector('.user-avatar')?.closest('.avatar-dropdown');
            if (userDropdown && userWrapper && !userWrapper.contains(e.target)) {
                userDropdown.classList.remove('show');
            }

            const notifDropdown = document.getElementById('notifDropdown');
            const notifWrapper = document.querySelector('.notification-bell')?.closest('.avatar-dropdown');
            if (notifDropdown && notifWrapper && !notifWrapper.contains(e.target)) {
                notifDropdown.classList.remove('show');
            }
        });

        // Mapping ng notification type papunta sa tamang page
        const notifRoutes = {
            inquiry: "{{ route('staff.inquiries.show', ':id') }}",
            schedule_visit: "{{ route('staff.appointments') }}",
        };

        function loadNotifications() {
            fetch("{{ route('staff.notifications.index') }}")
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById('notifList');
                    const dot = document.getElementById('notifDot');

                    dot.style.display = data.unread_count > 0 ? 'block' : 'none';

                    if (data.notifications.length === 0) {
                        list.innerHTML = '<div class="dropdown-item">Walang notification.</div>';
                        return;
                    }

                    list.innerHTML = data.notifications.map(n => `
                        <div class="dropdown-item" style="flex-direction:column; align-items:flex-start; cursor:pointer; ${n.is_read ? 'opacity:0.6;' : ''}" onclick="handleNotifClick(${n.notification_id}, '${n.type}', ${n.reference_id})">
                            <strong style="font-size:0.85rem;">${n.title}</strong>
                            <span style="font-size:0.8rem; color:#7f8c8d;">${n.message}</span>
                        </div>
                    `).join('');
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

        // Load agad pagbukas ng page, tapos every 30s
        loadNotifications();
        setInterval(loadNotifications, 30000);
    </script>

    @vite(['resources/js/bootstrap.js'])
    @stack('scripts')
</body>
</html>