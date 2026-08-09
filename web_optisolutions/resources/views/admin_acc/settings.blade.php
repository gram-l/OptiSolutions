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
        

        <div style="flex: 1; min-width: 0;">
            <div class="settings-header">
                <h2><i class="bi bi-gear"></i>Settings</h2>
                <p>Manage clinic-wide preferences, security, and account options</p>
            </div>

            <div class="settings-grid">

                <!-- Notification Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="icon-badge"><i class="bi bi-bell"></i></div>
                        <div>
                            <h3>Notification Settings</h3>
                            <p>Choose what you get notified about</p>
                        </div>
                    </div>
                    <div class="settings-card-body">
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
                </div>

                <!-- Appearance -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="icon-badge"><i class="bi bi-palette"></i></div>
                        <div>
                            <h3>Appearance</h3>
                            <p>Customize how the dashboard looks</p>
                        </div>
                    </div>
                    <div class="settings-card-body">
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
                </div>

                <!-- Data & Privacy -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="icon-badge"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <h3>Data &amp; Privacy</h3>
                            <p>Manage clinic data and stored records</p>
                        </div>
                    </div>
                    <div class="settings-card-body">
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
                </div>

                <!-- Security & Access -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="icon-badge"><i class="bi bi-lock"></i></div>
                        <div>
                            <h3>Security &amp; Access</h3>
                            <p>Update your password and session settings</p>
                        </div>
                    </div>
                    <div class="settings-card-body">
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

                        <div class="settings-row" style="margin-top:0.5rem;">
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

                <!-- System Info -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="icon-badge"><i class="bi bi-info-circle"></i></div>
                        <div>
                            <h3>System Info</h3>
                            <p>Details about this installation</p>
                        </div>
                    </div>
                    <div class="settings-card-body">
                        <ul class="info-list">
                            <li><span class="label">App version</span><span class="value">v1.0.0</span></li>
                            <li><span class="label">Last login</span><span class="value" id="lastLoginValue">—</span></li>
                            <li><span class="label">Account role</span><span class="value" id="roleValue">Admin</span></li>
                            <li><span class="label">Last backup</span><span class="value">Not configured</span></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Logout -->
            <div class="logout-card">
                <div>
                    <h4>Log out of this account</h4>
                    <p>You'll need to sign in again to access the dashboard</p>
                </div>
                <button class="btn-logout" id="logoutBtn">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
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