<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Font Awesome -->
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>OptiSolutions - Settings</title>
    <!-- Vite CSS -->
    @vite(['resources/css/admin_css/settings.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css'])

</head>
<body>
    <!-- Header -->
@include('admin_acc.header')

    <!-- Main Container -->
    <div class="container">
        <div class="settings-overlay" id="settingsOverlay">
            <div class="settings-modal">

                <!-- Modal topbar -->
                <div class="settings-modal-topbar">
                    <div class="settings-modal-topbar-text">
                        <h2><i class="bi bi-gear"></i> Settings</h2>
                        <p>Manage clinic-wide preferences, security, and account options</p>
                    </div>
                    <button class="settings-modal-close" id="settingsCloseBtn" aria-label="Close settings">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Settings shell (nav + content pane) -->
                <div class="settings-shell">

                <!-- Left nav -->
                <div class="settings-nav">
                    <div class="settings-nav-scroll">
                        <div class="settings-nav-group">
                            <div class="settings-nav-group-label">General</div>
                            <button class="settings-nav-item active" data-target="pane-notifications">
                                <i class="bi bi-bell"></i> Notifications
                            </button>
                            <button class="settings-nav-item" data-target="pane-appearance">
                                <i class="bi bi-palette"></i> Appearance
                            </button>
                        </div>

                        <div class="settings-nav-group">
                            <div class="settings-nav-group-label">Account</div>
                            <button class="settings-nav-item" data-target="pane-profile">
                                <i class="bi bi-person-circle"></i> Profile
                            </button>
                            <button class="settings-nav-item" data-target="pane-security">
                                <i class="bi bi-lock"></i> Security
                            </button>
                            <button class="settings-nav-item" data-target="pane-privacy">
                                <i class="bi bi-shield-check"></i> Data &amp; Privacy
                            </button>
                        </div>

                        <div class="settings-nav-group">
                            <div class="settings-nav-group-label">Clinic</div>
                            <button class="settings-nav-item" data-target="pane-clinic-info">
                                <i class="bi bi-hospital"></i> Clinic Info
                            </button>
                            <button class="settings-nav-item" data-target="pane-working-hours">
                                <i class="bi bi-clock-history"></i> Working Hours
                            </button>
                        </div>
                    </div>

                    <div class="settings-nav-divider"></div>
                    <button class="settings-nav-item-danger" id="logoutBtn">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </div>

                <!-- Right panel -->
                <div class="settings-panel">

                    <!-- Notifications -->
                    <div class="settings-pane active" id="pane-notifications">
                        <div class="settings-pane-header">
                            <h3><i class="bi bi-bell"></i> Notification Settings</h3>
                            <p>Choose what you get notified about</p>
                        </div>

                        <div class="settings-row">
                            <div>
                                <div class="settings-row-label">New appointment bookings</div>
                                <div class="settings-row-desc">Alert when a patient books an appointment</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked data-setting="notify_appointments">
                                <span class="switch-track"></span>
                            </label>
                        </div>
                        <div class="settings-row">
                            <div>
                                <div class="settings-row-label">New chatbot inquiries</div>
                                <div class="settings-row-desc">Alert when a patient message needs a reply</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked data-setting="notify_chatbot">
                                <span class="switch-track"></span>
                            </label>
                        </div>
                        <div class="settings-row">
                            <div>
                                <div class="settings-row-label">New patient complaints</div>
                                <div class="settings-row-desc">Alert when a complaint is submitted</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked data-setting="notify_complaints">
                                <span class="switch-track"></span>
                            </label>
                        </div>
                        <div class="settings-row">
                            <div>
                                <div class="settings-row-label">Email notifications</div>
                                <div class="settings-row-desc">Also send the above to your email</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" data-setting="notify_email">
                                <span class="switch-track"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Appearance -->
                    <div class="settings-pane" id="pane-appearance">
                        <div class="settings-pane-header">
                            <h3><i class="bi bi-palette"></i> Appearance</h3>
                            <p>Customize how the dashboard looks</p>
                        </div>

                        <div class="settings-row">
                            <div>
                                <div class="settings-row-label">Dark mode</div>
                                <div class="settings-row-desc">Switch to a darker color theme</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="darkModeToggle" data-setting="dark_mode">
                                <span class="switch-track"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Profile -->
                    <div class="settings-pane" id="pane-profile">
                        <div class="settings-pane-header">
                            <h3><i class="bi bi-person-circle"></i> Profile</h3>
                            <p>Your account details for this installation</p>
                        </div>

                        <ul class="info-list">
                            <li><span class="label">App version</span><span class="value">v1.0.0</span></li>
                            <li><span class="label">Last login</span><span class="value" id="lastLoginValue">—</span></li>
                            <li><span class="label">Account role</span><span class="value" id="roleValue">Admin</span></li>
                            <li><span class="label">Last backup</span><span class="value">Not configured</span></li>
                        </ul>
                    </div>

                    <!-- Security -->
                    <div class="settings-pane" id="pane-security">
                        <div class="settings-pane-header">
                            <h3><i class="bi bi-lock"></i> Security &amp; Access</h3>
                            <p>Update your password and session settings</p>
                        </div>

                        <div class="settings-section">
                            <div class="settings-section-title">Change Password</div>
                            <form id="passwordForm">
                                <div class="settings-field">
                                    <label class="settings-field-label">Current password</label>
                                    <input type="password" class="settings-input" id="currentPassword" placeholder="Enter current password">
                                </div>
                                <div class="settings-field">
                                    <label class="settings-field-label">New password</label>
                                    <input type="password" class="settings-input" id="newPassword" placeholder="Enter new password">
                                </div>
                                <div class="settings-field">
                                    <label class="settings-field-label">Confirm new password</label>
                                    <input type="password" class="settings-input" id="confirmPassword" placeholder="Re-enter new password">
                                </div>
                                <div class="save-bar">
                                    <button type="submit" class="btn-settings btn-settings-primary">
                                        <i class="bi bi-check2"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="settings-section">
                            <div class="settings-section-title">Session</div>
                            <div class="settings-row">
                                <div>
                                    <div class="settings-row-label">Session timeout</div>
                                    <div class="settings-row-desc">Automatically log out after inactivity</div>
                                </div>
                                <select class="settings-input" style="width:auto; margin-top:0;" id="sessionTimeout">
                                    <option value="15">15 minutes</option>
                                    <option value="30" selected>30 minutes</option>
                                    <option value="60">1 hour</option>
                                    <option value="0">Never</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Data & Privacy -->
                    <div class="settings-pane" id="pane-privacy">
                        <div class="settings-pane-header">
                            <h3><i class="bi bi-shield-check"></i> Data &amp; Privacy</h3>
                            <p>Manage clinic data and stored records</p>
                        </div>

                        <div class="settings-row">
                            <div>
                                <div class="settings-row-label">Terms &amp; Conditions</div>
                                <div class="settings-row-desc">Review the terms shown to patients on login</div>
                            </div>
                            <button class="btn-settings btn-settings-outline" id="viewTermsBtn">
                                <i class="bi bi-eye"></i> View
                            </button>
                        </div>
                        <div class="settings-row">
                            <div>
                                <div class="settings-row-label">Export patient data</div>
                                <div class="settings-row-desc">Download records as a CSV file</div>
                            </div>
                            <button class="btn-settings btn-settings-outline" id="exportDataBtn">
                                <i class="bi bi-download"></i> Export
                            </button>
                        </div>
                        <div class="settings-row">
                            <div>
                                <div class="settings-row-label">Clear cached data</div>
                                <div class="settings-row-desc">Free up space used by cached images and files</div>
                            </div>
                            <button class="btn-settings btn-settings-outline" id="clearCacheBtn">
                                <i class="bi bi-trash3"></i> Clear
                            </button>
                        </div>
                    </div>

                    <!-- Clinic Info -->
                    <div class="settings-pane" id="pane-clinic-info">
                        <div class="settings-pane-header">
                            <h3><i class="bi bi-hospital"></i> Clinic Info</h3>
                            <p>Details shown to patients and used across the system</p>
                        </div>

                        <form id="clinicInfoForm">
                            <div class="settings-field-grid">
                                <div class="settings-field">
                                    <label class="settings-field-label">Clinic name</label>
                                    <input type="text" class="settings-input" id="clinicName" placeholder="e.g. PolyClinic Balayan">
                                </div>
                                <div class="settings-field">
                                    <label class="settings-field-label">Contact number</label>
                                    <input type="text" class="settings-input" id="clinicPhone" placeholder="e.g. (043) 000 0000">
                                </div>
                            </div>
                            <div class="settings-field">
                                <label class="settings-field-label">Address</label>
                                <input type="text" class="settings-input" id="clinicAddress" placeholder="Street, city, province">
                            </div>
                            <div class="settings-field">
                                <label class="settings-field-label">Email</label>
                                <input type="email" class="settings-input" id="clinicEmail" placeholder="clinic@example.com">
                            </div>
                            <div class="save-bar">
                                <button type="submit" class="btn-settings btn-settings-primary">
                                    <i class="bi bi-check2"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Working Hours -->
                    <div class="settings-pane" id="pane-working-hours">
                        <div class="settings-pane-header">
                            <h3><i class="bi bi-clock-history"></i> Working Hours</h3>
                            <p>Set when the clinic is open for appointments</p>
                        </div>

                        <div id="workingHoursList">
                            <!-- Rows are rendered by JS below from the DAYS array -->
                        </div>

                        <div class="save-bar">
                            <button type="button" class="btn-settings btn-settings-primary" id="saveHoursBtn">
                                <i class="bi bi-check2"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
        // ---- Placeholder wiring only (UI-first pass, no backend yet) ----
        // Each handler below just gives visible feedback for now. Once the
        // settings storage approach is decided (single table vs per-user
        // columns vs local), swap the TODOs for real fetch() calls, the
        // same pattern used in chatbot_logs.blade.php's sendReply()/
        // resolveInquiry().

        // ----- Modal close behavior -----
        // Settings is reached as its own page (via the avatar dropdown link),
        // so "closing" it just takes you back to wherever you came from.
        function closeSettingsModal() {
            if (document.referrer && document.referrer.includes(window.location.host)) {
                window.location.href = document.referrer;
            } else {
                window.location.href = '/admin_acc/dashboard';
            }
        }

        document.getElementById('settingsCloseBtn').addEventListener('click', closeSettingsModal);

        // Click on the dimmed backdrop (outside the modal card) also closes it
        document.getElementById('settingsOverlay').addEventListener('click', (e) => {
            if (e.target.id === 'settingsOverlay') closeSettingsModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSettingsModal();
        });

        // ----- Nav / pane switching -----
        document.querySelectorAll('.settings-nav-item[data-target]').forEach(navItem => {
            navItem.addEventListener('click', () => {
                document.querySelectorAll('.settings-nav-item[data-target]').forEach(el => el.classList.remove('active'));
                document.querySelectorAll('.settings-pane').forEach(el => el.classList.remove('active'));

                navItem.classList.add('active');
                document.getElementById(navItem.dataset.target).classList.add('active');
            });
        });

        document.querySelectorAll('[data-setting]').forEach(input => {
            input.addEventListener('change', () => {
                // TODO: POST { key: input.dataset.setting, value: input.checked }
                console.log('Setting changed:', input.dataset.setting, input.checked);
            });
        });

        document.getElementById('viewTermsBtn').addEventListener('click', () => {
            // TODO: open the same Terms & Conditions content used in
            // showTermsDialogIfNeeded (terms_screen.dart) here, read-only.
            alert('Terms & Conditions viewer coming soon.');
        });

        document.getElementById('exportDataBtn').addEventListener('click', () => {
            // TODO: trigger a real CSV export endpoint.
            alert('Export will be available once the export endpoint is wired up.');
        });

        document.getElementById('clearCacheBtn').addEventListener('click', () => {
            if (confirm('Clear cached data? This will not delete any patient records.')) {
                // TODO: call cache-clear endpoint.
                alert('Cache cleared.');
            }
        });

        document.getElementById('passwordForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const current = document.getElementById('currentPassword').value;
            const next = document.getElementById('newPassword').value;
            const confirmVal = document.getElementById('confirmPassword').value;

            if (!current || !next || !confirmVal) {
                alert('Please fill in all password fields.');
                return;
            }
            if (next !== confirmVal) {
                alert('New password and confirmation do not match.');
                return;
            }
            // TODO: POST to a change-password endpoint.
            alert('Password updated.');
            e.target.reset();
        });

        document.getElementById('darkModeToggle').addEventListener('change', (e) => {
            // TODO: hook into the same dark-mode flag used elsewhere on
            // the web admin side, if one exists; otherwise this can stay
            // a local, per-browser preference.
            document.body.classList.toggle('dark-mode-preview', e.target.checked);
        });

        document.getElementById('clinicInfoForm').addEventListener('submit', (e) => {
            e.preventDefault();
            // TODO: POST clinic name/phone/address/email to a clinic-info endpoint.
            alert('Clinic info saved.');
        });

        // ----- Working hours -----
        const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        function renderWorkingHours() {
            const list = document.getElementById('workingHoursList');
            list.innerHTML = DAYS.map((day, i) => `
                <div class="hours-row" data-day="${day}">
                    <div class="hours-day">${day}</div>
                    <label class="switch">
                        <input type="checkbox" class="hours-open-toggle" ${i === 6 ? '' : 'checked'}>
                        <span class="switch-track"></span>
                    </label>
                    <div class="hours-times">
                        <input type="time" class="settings-input hours-open" value="08:00" style="width:auto;">
                        <span>to</span>
                        <input type="time" class="settings-input hours-close" value="17:00" style="width:auto;">
                    </div>
                </div>
            `).join('');

            list.querySelectorAll('.hours-open-toggle').forEach(toggle => {
                const row = toggle.closest('.hours-row');
                const updateRowState = () => {
                    const times = row.querySelector('.hours-times');
                    times.style.display = toggle.checked ? 'flex' : 'none';
                };
                toggle.addEventListener('change', updateRowState);
                updateRowState();
            });
        }
        renderWorkingHours();

        document.getElementById('saveHoursBtn').addEventListener('click', () => {
            // TODO: collect per-day open/close values and POST to a
            // working-hours endpoint.
            alert('Working hours saved.');
        });

        document.getElementById('logoutBtn').addEventListener('click', () => {
            if (confirm('Are you sure you want to logout?')) {
                // TODO: replace with real logout — POST to /logout then
                // redirect, same as the rest of the admin panel should do.
                window.location.href = '/logout';
            }
        });

        // Populate System Info with whatever's easily available client-side.
        document.getElementById('lastLoginValue').innerText = new Date().toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    </script>
</body>
</html>