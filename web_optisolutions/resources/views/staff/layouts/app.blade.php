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
                        <div class="sidebar-brand-icon">
                            <i class="fa-solid fa-hospital"></i>
                        </div>
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
                        <i class="bi bi-bar-chart-fill"></i> <span>Dashboard</span>
                    </div>
                </a>
                <a href="{{ route('staff.appointments') }}" style="text-decoration: none; color: inherit; display: block;">
                    <div class="nav-item {{ request()->routeIs('staff.appointments*') ? 'active' : '' }}">
                        <i class="bi bi-journal-medical"></i> <span>Scheduled Visits</span>
                    </div>
                </a>
                <a href="{{ route('staff.inquiries') }}" style="text-decoration: none; color: inherit; display: block;">
                    <div class="nav-item {{ request()->routeIs('staff.inquiries*') ? 'active' : '' }}">
                        <i class="bi bi-chat-dots"></i> <span>Chatbot Inquiries</span>
                    </div>
                </a>
                <a href="{{ route('staff.doctors') }}" style="text-decoration: none; color: inherit; display: block;">
                    <div class="nav-item {{ request()->routeIs('staff.doctors*') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-doctor"></i> <span>Manage Doctors</span>
                    </div>
                </a>
                <a href="{{ route('staff.patients') }}" style="text-decoration: none; color: inherit; display: block;">
                    <div class="nav-item {{ request()->routeIs('staff.patients*') ? 'active' : '' }}">
                        <i class="bi bi-hospital"></i> <span>Patient Records</span>
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

<!-- ===== PROFILE MODAL ===== -->
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

<!-- ===== SETTINGS MODAL ===== -->
<div class="profile-modal-overlay" id="settingsModalOverlay" onclick="if(event.target === this) closeSettingsModal();">
    <div class="settings-panel">
        <button class="settings-panel-close" onclick="closeSettingsModal();" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="settings-panel-header">
            <div class="settings-panel-title"><i class="bi bi-gear"></i> Settings</div>
            <p class="settings-panel-subtitle">Manage clinic-wide preferences, security, and account options</p>
        </div>

        <div class="settings-panel-body">

            {{-- LEFT: sidebar nav --}}
            <div class="settings-sidebar">
                <div class="settings-sidebar-label">GENERAL</div>
                <button type="button" class="settings-nav-item active" data-tab="notifications" onclick="switchSettingsTab('notifications')">
                    <i class="bi bi-bell"></i> Notifications
                </button>
                <button type="button" class="settings-nav-item" data-tab="appearance" onclick="switchSettingsTab('appearance')">
                    <i class="bi bi-palette"></i> Appearance
                </button>

                <div class="settings-sidebar-label">ACCOUNT</div>
                <button type="button" class="settings-nav-item" data-tab="profile" onclick="switchSettingsTab('profile')">
                    <i class="bi bi-person"></i> Profile
                </button>
                <button type="button" class="settings-nav-item" data-tab="security" onclick="switchSettingsTab('security')">
                    <i class="bi bi-shield-lock"></i> Security
                </button>
                <button type="button" class="settings-nav-item" data-tab="privacy" onclick="switchSettingsTab('privacy')">
                    <i class="bi bi-shield-check"></i> Data &amp; Privacy
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
                            <div class="settings-item-title">New appointment</div>
                            <div class="settings-item-desc">Alert when a patient books an appointment</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" class="notif-pref-toggle" data-pref="appointments" checked onchange="saveNotifPref('appointments', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="settings-toggle-row">
                        <div class="settings-item-text">
                            <div class="settings-item-title">New chatbot inquiries</div>
                            <div class="settings-item-desc">Alert when a patient message needs a reply</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" class="notif-pref-toggle" data-pref="inquiries" checked onchange="saveNotifPref('inquiries', this.checked)">
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
                            <div class="settings-item-title">Dark mode</div>
                            <div class="settings-item-desc">Switch to a darker color theme</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="darkModeToggle" onchange="toggleDarkMode(this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

{{-- PROFILE --}}
                <div class="settings-tab-panel" id="settings-tab-profile">
                    <div class="settings-content-title"><i class="bi bi-person-badge"></i> Profile</div>
                    <p class="settings-content-subtitle">Your account details for this installation</p>

                    <div class="info-modal-field" style="margin-top: 1.5rem;">
                        <div class="info-modal-label">App version</div>
                        <div class="info-modal-value">{{ config('app.version', 'v1.0.0') }}</div>
                    </div>

                    <div class="info-modal-field">
                        <div class="info-modal-label">Last login</div>
                        <div class="info-modal-value">
                            {{ Auth::user()->last_login_at ? \Carbon\Carbon::parse(Auth::user()->last_login_at)->format('M d, g:i A') : 'N/A' }}
                        </div>
                    </div>

                    <div class="info-modal-field">
                        <div class="info-modal-label">Account role</div>
                        <div class="info-modal-value">{{ ucfirst(Auth::user()->user_role ?? 'Admin') }}</div>
                    </div>

                    <div class="info-modal-field">
                        <div class="info-modal-label">Last backup</div>
                        <div class="info-modal-value">{{ config('app.last_backup', 'Not configured') }}</div>
                    </div>
                </div>

                {{-- SECURITY --}}
                <div class="settings-tab-panel" id="settings-tab-security">
                    <div class="settings-content-title"><i class="bi bi-shield-lock"></i> Security & Access</div>
                    <p class="settings-content-subtitle">Update your password and session settings</p>

                    @if($errors->has('current_password') || $errors->has('password'))
                        <div class="error-message show">
                            {{ $errors->first('current_password') ?: $errors->first('password') }}
                        </div>
                    @endif

                    <div class="settings-section-heading" style="margin-top: 1.5rem; font-size: 0.8rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">CHANGE PASSWORD</div>
                    
                    {{-- Working Password Form --}}
                    <form method="POST" action="{{ route('staff.profile.password') }}" id="changePasswordForm">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Current password</label>
                            <input type="password" name="current_password" placeholder="Enter current password" required autocomplete="current-password">
                        </div>

                        <div class="form-group">
                            <label>New password</label>
                            <input type="password" name="password" placeholder="Enter new password" required autocomplete="new-password" minlength="8">
                        </div>

                        <div class="form-group">
                            <label>Confirm new password</label>
                            <input type="password" name="password_confirmation" placeholder="Re-enter new password" required autocomplete="new-password" minlength="8">
                        </div>

                        <div style="text-align: right;">
                            <button type="submit" class="btn-sm btn-primary" style="padding: 0.7rem 1.5rem; margin-top: 0.5rem;"><i class="bi bi-check-lg"></i> Update Password</button>
                        </div>
                    </form>

                    <div class="settings-section-heading" style="margin-top: 2.5rem; font-size: 0.8rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">SESSION</div>
                    
                    {{-- Working Session Form --}}
                    <form method="POST" action="{{ route('staff.profile.session') ?? '#' }}" id="updateSessionForm">
                        @csrf
                        <div class="settings-toggle-row">
                            <div class="settings-item-text">
                                <div class="settings-item-title">Session timeout</div>
                                <div class="settings-item-desc">Automatically log out after inactivity</div>
                            </div>
                            <select name="session_timeout" class="form-control" style="width: auto; display: inline-block; padding: 0.4rem 1rem;" onchange="this.form.submit()">
                                <option value="15" {{ config('session.lifetime') == 15 ? 'selected' : '' }}>15 minutes</option>
                                <option value="30" {{ config('session.lifetime') == 30 ? 'selected' : '' }}>30 minutes</option>
                                <option value="60" {{ config('session.lifetime') == 60 ? 'selected' : '' }}>1 hour</option>
                                <option value="never" {{ config('session.lifetime') > 60 ? 'selected' : '' }}>Never</option>
                            </select>
                        </div>
                    </form>
                </div>

                {{-- DATA & PRIVACY --}}
                <div class="settings-tab-panel" id="settings-tab-privacy">
                    <div class="settings-content-title"><i class="bi bi-shield-check"></i> Data &amp; Privacy</div>
                    <p class="settings-content-subtitle">Manage clinic data and stored records</p>

                    <div class="settings-toggle-row" style="align-items: center; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-top: 1.5rem;">
                        <div class="settings-item-text">
                            <div class="settings-item-title">Terms & Conditions</div>
                            <div class="settings-item-desc">Review the terms for patients and staff</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius: 20px; padding: 0.3rem 1rem;" onclick="openStaffTermsModal();"><i class="bi bi-eye"></i> View</button>
                    </div>

                    <div class="settings-toggle-row" style="align-items: center; border-bottom: 1px solid #eee; padding-bottom: 1rem;">
                        <div class="settings-item-text">
                            <div class="settings-item-title">Export patient data</div>
                            <div class="settings-item-desc">Download records as a CSV file</div>
                        </div>
                        <a href="{{ route('staff.patients.export') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 20px; padding: 0.3rem 1rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem;"><i class="bi bi-download"></i> Export</a>
                    </div>

                    <div class="settings-toggle-row" style="align-items: center; border-bottom: none;">
                        <div class="settings-item-text">
                            <div class="settings-item-title">Clear cached data</div>
                            <div class="settings-item-desc">Free up space used by cached images and files</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius: 20px; padding: 0.3rem 1rem;"><i class="bi bi-trash"></i> Clear</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ===== TERMS & CONDITIONS MODAL (Patients + Staff) ===== -->
<div class="modal-overlay" id="staffTermsModalOverlay" style="z-index: 2100;" onclick="if(event.target === this) closeStaffTermsModal();">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="bi bi-file-earmark-text" style="color: var(--primary-main); margin-right: 0.4rem;"></i> Terms &amp; Conditions</h3>
            <button class="modal-close" onclick="closeStaffTermsModal();" aria-label="Close">&times;</button>
        </div>

        {{-- Sub-tabs: Patients / Staff --}}
        <div style="display: flex; gap: 0.5rem; border-bottom: 1px solid var(--light-gray); margin-bottom: 1.25rem;">
            <button type="button" class="terms-subtab active" data-terms-tab="patients" onclick="switchTermsTab('patients')"
                style="background: none; border: none; padding: 0 0 0.75rem; margin-right: 0.5rem; font-size: 0.9rem; font-weight: 600; color: var(--primary-main); border-bottom: 2px solid var(--primary-main); cursor: pointer;">
                For Patients
            </button>
            <button type="button" class="terms-subtab" data-terms-tab="staff" onclick="switchTermsTab('staff')"
                style="background: none; border: none; padding: 0 0 0.75rem; font-size: 0.9rem; font-weight: 600; color: #7f8c8d; border-bottom: 2px solid transparent; cursor: pointer;">
                For Staff
            </button>
        </div>

        {{-- ===== PATIENT TERMS ===== --}}
        <div class="terms-tab-panel" id="terms-tab-patients">
            <p style="color: #7f8c8d; font-size: 0.8rem; margin-bottom: 1.25rem;">Last updated: August 2026</p>

            <p style="font-size: 0.92rem; line-height: 1.7; color: var(--text-dark); margin-bottom: 1rem;">
                These terms explain how PolyClinic's online scheduling system
                collects and uses patient information, in compliance with the
                Data Privacy Act of 2012 (RA 10173) and the issuances of the
                National Privacy Commission (NPC).
            </p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">What We Collect</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">
                Name, contact number, date of birth, email address, and the
                service/doctor selected when you schedule a visit through our
                chatbot. Complaint or feedback details are collected only if
                you choose to submit them.
            </p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">Why We Collect It</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">
                To schedule and confirm your appointment, match you with an
                available specialist, send confirmations or transcripts you
                request, and respond to feedback. We do not use your data for
                advertising or profiling.
            </p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">Your Rights</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">
                Under the Data Privacy Act, you have the right to be informed,
                access, correct, object to processing, request erasure or
                blocking, request data portability, and file a complaint with
                PolyClinic or the National Privacy Commission (NPC).
            </p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">Contact</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0;">
                TM Kalaw St., Lipa City, Batangas 4217 &middot; 0985 475 5511
            </p>
        </div>

        {{-- ===== STAFF TERMS ===== --}}
        <div class="terms-tab-panel" id="terms-tab-staff" style="display: none;">
            <p style="color: #7f8c8d; font-size: 0.8rem; margin-bottom: 1.25rem;">Last updated: September 2026</p>

            <p style="font-size: 0.92rem; line-height: 1.7; color: var(--text-dark); margin-bottom: 1rem;">
                These Terms &amp; Conditions govern the use of the OptiSolutions
                system ("the System") by PolyClinic staff, doctors, and
                administrators. By logging in to and using the System, you
                agree to comply with these Terms.
            </p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">1. Purpose of the System</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">
                OptiSolutions is PolyClinic Lipa's internal clinic management
                system, used to manage appointments, chatbot inquiries, doctor
                schedules, patient records, and related clinic operations.
                Access is granted solely to perform your official duties.
            </p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">2. Account Access and Responsibility</h4>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Keep your username and password confidential; do not share credentials with anyone.</li>
                <li>You are responsible for all actions performed under your account.</li>
                <li>Log out after each use, especially on shared or public devices.</li>
                <li>Report suspected unauthorized access to the system administrator immediately.</li>
            </ul>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">3. Acceptable Use</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">You must not access records outside your assigned tasks, bypass security features, misuse the System toward patients or coworkers, or introduce unauthorized software.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">4. Confidentiality of Patient Information</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">
                As a staff member with access to patient data, you are
                personally bound by the obligations described in the Patient
                terms above under the Data Privacy Act of 2012. Patient
                information must only be used for scheduling, treatment, and
                clinic operations, and must never be disclosed, copied, or
                shared outside the System except as required by your official
                duties.
            </p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">5. Data Accuracy</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Information entered into the System must be accurate, complete, and in good faith. Deliberately false or misleading entries are a violation of these Terms.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">6. Monitoring and Activity Logs</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">The System may log account activity (logins, record changes, data access) for security and accountability. Logs may be reviewed when investigating suspected misuse.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">7. System Availability</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Uninterrupted access is not guaranteed. Maintenance, updates, or technical issues may cause temporary downtime; keep a manual fallback for critical tasks.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">8. Consequences of Violation</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Violations &mdash; including unauthorized data access, credential sharing, or misuse &mdash; may result in disciplinary action, suspension of access, and liability under applicable law.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">9. Changes to These Terms</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">These Terms may be updated to reflect changes in clinic policy, system features, or legal requirements. Continued use after an update means you accept the revised Terms.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">10. Contact Us</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0;">
                TM Kalaw St., Lipa City, Batangas 4217 &middot; 0985 475 5511
            </p>
        </div>

        <div style="text-align: right; margin-top: 1.5rem;">
            <button type="button" class="btn-sm btn-primary" onclick="closeStaffTermsModal();">Close</button>
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

        //  STAFF TERMS & CONDITIONS MODAL
        function openStaffTermsModal() {
            document.getElementById('staffTermsModalOverlay').classList.add('show');
        }
        function closeStaffTermsModal() {
            document.getElementById('staffTermsModalOverlay').classList.remove('show');
        }
        function switchTermsTab(tab) {
            document.querySelectorAll('.terms-subtab').forEach(function (btn) {
                const active = btn.dataset.termsTab === tab;
                btn.classList.toggle('active', active);
                btn.style.color = active ? 'var(--primary-main)' : '#7f8c8d';
                btn.style.borderBottomColor = active ? 'var(--primary-main)' : 'transparent';
            });
            document.querySelectorAll('.terms-tab-panel').forEach(function (panel) {
                panel.style.display = panel.id === 'terms-tab-' + tab ? 'block' : 'none';
            });
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
            return ['inquiry', 'schedule_visit'].includes(type) ? type : 'system';
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