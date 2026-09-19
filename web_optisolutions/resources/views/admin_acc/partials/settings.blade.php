{{--
    Settings overlay/modal. Included once from admin_acc.header so it's
    available on every admin page (opened via the avatar dropdown's
    "Settings" link, same pattern as admin_acc.partials.profile).

    Starts hidden (inline style) and is toggled by openSettingsModal() /
    closeSettingsModal() below, instead of being navigated to as its own
    page like it used to be.
--}}
@vite(['resources/css/admin_css/settings.css'])

{{--
    Applied immediately (not wrapped in DOMContentLoaded) so dark mode is
    on the body before the rest of the page paints, avoiding a light-mode
    flash. Since this partial is included from the header, it runs early.
--}}
<script>
    (function () {
        if (localStorage.getItem('admin_dark_mode') === '1') {
            document.body.classList.add('dark-mode');
        }
    })();
</script>

<div class="settings-overlay" id="settingsOverlay" style="display:none;">
    <div class="settings-modal">

        {{-- NOTE: close button markup restored from the JS reference (#settingsCloseBtn).
             If your original had different markup/classes, keep yours. --}}
        <button type="button" class="settings-close" id="settingsCloseBtn" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>

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

                @if($errors->has('current_password') || $errors->has('password'))
                    <div style="background:#fdecea; color:#b3261e; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem;">
                        {{ $errors->first('current_password') ?: $errors->first('password') }}
                    </div>
                @endif

                <div class="settings-section">
                    <div class="settings-section-title">Change Password</div>
                    <form id="passwordForm" method="POST" action="{{ route('admin.profile.password') }}">
                        @csrf
                        @method('PUT')
                        <div class="settings-field">
                            <label class="settings-field-label">Current password</label>
                            <input type="password" class="settings-input" name="current_password" id="currentPassword" placeholder="Enter current password" required autocomplete="current-password">
                        </div>
                        <div class="settings-field">
                            <label class="settings-field-label">New password</label>
                            <input type="password" class="settings-input" name="password" id="newPassword" placeholder="Enter new password" required autocomplete="new-password" minlength="8">
                        </div>
                        <div class="settings-field">
                            <label class="settings-field-label">Confirm new password</label>
                            <input type="password" class="settings-input" name="password_confirmation" id="confirmPassword" placeholder="Re-enter new password" required autocomplete="new-password" minlength="8">
                        </div>
                        <div class="save-bar">
                            <button type="submit" class="btn-settings btn-settings-primary">
                                <i class="bi bi-check2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <div class="settings-section">
                    <div class="settings-section-title">Session</div>
                    <form method="POST" action="{{ route('admin.profile.session') }}" id="sessionTimeoutForm">
                        @csrf
                        @method('PUT')
                        <div class="settings-row">
                            <div>
                                <div class="settings-row-label">Session timeout</div>
                                <div class="settings-row-desc">Automatically log out after inactivity</div>
                            </div>
                            <select class="settings-input" style="width:auto; margin-top:0;" name="session_timeout" id="sessionTimeout" onchange="this.form.submit()">
                                <option value="15" {{ config('session.lifetime') == 15 ? 'selected' : '' }}>15 minutes</option>
                                <option value="30" {{ config('session.lifetime') == 30 ? 'selected' : '' }}>30 minutes</option>
                                <option value="60" {{ config('session.lifetime') == 60 ? 'selected' : '' }}>1 hour</option>
                                <option value="0" {{ config('session.lifetime') > 60 ? 'selected' : '' }}>Never</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

                    <div id="workingHoursList">
                        <!-- Rows are rendered by JS below from the DAYS array -->
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
                    <a href="{{ route('admin.patients.export') }}" class="btn-settings btn-settings-outline">
                        <i class="bi bi-download"></i> Export
                    </a>
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
                        <button type="button" class="btn-settings btn-settings-primary" id="saveHoursBtn">
                            <i class="bi bi-check2"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms & Conditions -->
<div class="modal-overlay" id="adminTermsModalOverlay"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:2100; align-items:center; justify-content:center;"
     onclick="if(event.target === this) closeAdminTermsModal();">
    <div class="modal-box"
         style="background:#fff; width:90%; max-width:700px; max-height:85vh; overflow-y:auto; border-radius:12px; padding:1.75rem 2rem; position:relative;">
        <div class="modal-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
            <h3 style="margin:0;"><i class="bi bi-file-earmark-text" style="color: var(--primary-main); margin-right: 0.4rem;"></i> Terms &amp; Conditions</h3>
            <button class="modal-close" id="adminTermsCloseBtn" aria-label="Close"
                    style="background:none; border:none; font-size:1.4rem; line-height:1; cursor:pointer;">&times;</button>
        </div>

        <div class="terms-tab-panel" id="terms-tab-admin">
            <p style="color: #7f8c8d; font-size: 0.8rem; margin-bottom: 1.25rem;">Last updated: September 2026</p>

            <p style="font-size: 0.92rem; line-height: 1.7; color: var(--text-dark); margin-bottom: 1rem;">
                These Terms &amp; Conditions govern the use of the OptiSolutions
                System ("the System") by PolyClinic Lipa staff, doctors, and
                administrators. By logging in to and using the System, you
                acknowledge that you have read, understood these Terms.
            </p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">1. Authorized Users</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">The System is intended solely for authorized PolyClinic personnel.</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>User accounts are created and managed exclusively by the PolyClinic Administrator.</li>
                <li>Staff members are not permitted to create their own accounts.</li>
                <li>Access to the System is granted only for official work-related responsibilities.</li>
                <li>PolyClinic reserves the right to suspend or terminate access at any time when necessary.</li>
            </ul>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">2. Confidentiality and Data Privacy</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">
                The System contains Sensitive Personal Information, including
                Personally Identifiable Information (PII) and health-related
                information entrusted to PolyClinic. All users are required to
                maintain the confidentiality of all patient, employee, and
                clinic information, and to access information only when
                required for legitimate business purposes.
            </p>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">Users are strictly prohibited from:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Copying, downloading, exporting, or printing confidential information without authorization.</li>
                <li>Taking screenshots or screen recordings of patient information.</li>
                <li>Sharing patient information through personal communication platforms including but not limited to Facebook Messenger, Viber, WhatsApp, Telegram, Gmail, or similar services.</li>
                <li>Disclosing confidential information to unauthorized individuals.</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Unauthorized disclosure of confidential information may result in disciplinary action, immediate termination of employment, civil liability, and criminal liability under applicable Philippine laws.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">3. Compliance with the Data Privacy Act of 2012</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">The System complies with Republic Act No. 10173 (Data Privacy Act of 2012). Users acknowledge that they process sensitive personal information as part of their duties and agree to:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Process personal data only for authorized clinical and administrative purposes.</li>
                <li>Observe confidentiality at all times.</li>
                <li>Prevent unauthorized access, disclosure, alteration, or destruction of personal information.</li>
                <li>Report any suspected data breach immediately.</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Any intentional misuse of personal data may constitute a violation of the Data Privacy Act and other applicable laws.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">4. Account Security</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">Each staff member is responsible for safeguarding their assigned account. Users must:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Keep usernames and passwords confidential.</li>
                <li>Not share login credentials with others.</li>
                <li>Log out after each use, especially on shared or public computers.</li>
                <li>Immediately report suspected unauthorized access.</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Staff are fully responsible for all activities performed using their assigned account until such access is reported as compromised. Credential sharing is strictly prohibited.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">5. Separation of Clinic Data and Personal Data</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">The System serves solely as a secure portal for authorized access. Users shall not:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Export chat conversations, patient records, or reports to personal storage.</li>
                <li>Upload clinic information to personal cloud storage services such as Google Drive, iCloud, Dropbox, OneDrive, or similar platforms.</li>
                <li>Store patient or contact information outside the System unless specifically authorized by management.</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Clinic information must remain within authorized systems.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">6. Monitoring and Audit</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">To maintain operational integrity, quality assurance, and security, PolyClinic continuously monitors activities performed within the System. The Company may record and review:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Login history</li>
                <li>Chat conversations</li>
                <li>Patient inquiries</li>
                <li>Feedback responses</li>
                <li>Appointment-related actions</li>
                <li>Administrative actions</li>
                <li>Activity timestamps</li>
                <li>System usage logs</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">This monitoring applies only to activities performed within the OptiSolutions System and does not extend to staff's personal accounts, files, or other private content outside the System. By using the System, staff expressly acknowledge and consent to this monitoring.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">7. Acceptable Use</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">The System must be used professionally and solely for official clinic operations. Users shall not:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Use the chat or messaging features for personal conversations.</li>
                <li>Harass, threaten, discriminate against, or abuse patients or co-workers.</li>
                <li>Provide unauthorized or unscripted medical advice beyond their assigned responsibilities.</li>
                <li>Use offensive, defamatory, or inappropriate language.</li>
                <li>Attempt to bypass system security measures.</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Professional communication is expected at all times.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">8. Intellectual Property</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">All information generated, stored, or processed within the System remains the exclusive property of PolyClinic. This includes but is not limited to:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Chat logs</li>
                <li>Inquiry records</li>
                <li>Appointment records</li>
                <li>Feedback</li>
                <li>Reports</li>
                <li>Patient lists</li>
                <li>Templates</li>
                <li>Analytics</li>
                <li>Documentation</li>
                <li>System-generated data</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Staff acquire no ownership rights over any information created or processed using the System.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">9. Compromised or Unauthorized Access</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">If a staff member suspects that their account, or the computer used to access the System, has been compromised or accessed without authorization, they must notify PolyClinic within two (2) hours of becoming aware of the incident. PolyClinic reserves the right to:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Immediately deactivate the user's account.</li>
                <li>Revoke access to the System.</li>
                <li>Require additional security verification before restoring access.</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Failure to promptly report a compromised account may result in disciplinary action.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">10. Employment Separation</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">Access to the System is directly tied to active employment. Upon resignation, retirement, suspension, or termination:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>User accounts will be immediately deactivated.</li>
                <li>Staff shall cease all access to the System.</li>
                <li>Former employees shall not attempt to regain access using previous credentials.</li>
            </ul>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">11. Prohibited Activities</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">Users shall not:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Attempt to hack, reverse engineer, or modify the System.</li>
                <li>Introduce malware or malicious software.</li>
                <li>Circumvent authentication mechanisms.</li>
                <li>Access information beyond their authorized permissions.</li>
                <li>Use another staff member's account.</li>
                <li>Allow unauthorized individuals to access the System.</li>
                <li>Use the System for any unlawful purpose.</li>
            </ul>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">12. Violations</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">Violation of these Terms and Conditions may result in one or more of the following:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Suspension of System access</li>
                <li>Immediate account deactivation</li>
                <li>Administrative or disciplinary sanctions</li>
                <li>Termination of employment, where warranted</li>
                <li>Civil liability</li>
                <li>Criminal prosecution under applicable Philippine laws, including the Data Privacy Act of 2012 and the Cybercrime Prevention Act of 2012</li>
            </ul>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">13. Amendments</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">PolyClinic reserves the right to modify these Terms and Conditions at any time to comply with operational requirements, legal obligations, or security standards. Continued use of the System after such changes constitutes acceptance of the revised Terms.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">14. Acceptance</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">By logging into and using the OptiSolutions System, you acknowledge that:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>You have read and understood these Terms and Conditions.</li>
                <li>You agree to comply with all applicable clinic policies and Philippine laws.</li>
                <li>You understand that all activities performed within the System may be monitored and audited.</li>
                <li>You accept responsibility for maintaining the confidentiality and security of your assigned account and any information accessed through the System.</li>
                <li>You understand that violations of these Terms may result in disciplinary action, termination of employment, and legal consequences where applicable.</li>
            </ul>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">15. Contact Us</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0;">
                TM Kalaw St., Lipa City, Batangas 4217 &middot; 0985 475 5511
            </p>
        </div>
    </div>
</div>

<!-- Terms & Conditions -->
<div class="modal-overlay" id="adminTermsModalOverlay"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:2100; align-items:center; justify-content:center;"
     onclick="if(event.target === this) closeAdminTermsModal();">
    <div class="modal-box"
         style="background:#fff; width:90%; max-width:700px; max-height:85vh; overflow-y:auto; border-radius:12px; padding:1.75rem 2rem; position:relative;">
        <div class="modal-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
            <h3 style="margin:0;"><i class="bi bi-file-earmark-text" style="color: var(--primary-main); margin-right: 0.4rem;"></i> Terms &amp; Conditions</h3>
            <button class="modal-close" id="adminTermsCloseBtn" aria-label="Close" style="background:none; border:none; font-size:1.4rem; line-height:1; cursor:pointer;">&times;</button>
        </div>

        <div class="terms-tab-panel" id="terms-tab-admin">
            <p style="color: #7f8c8d; font-size: 0.8rem; margin-bottom: 1.25rem;">Last updated: September 2026</p>

            <p style="font-size: 0.92rem; line-height: 1.7; color: var(--text-dark); margin-bottom: 1rem;">
                These Terms &amp; Conditions govern the use of the OptiSolutions
                System ("the System") by PolyClinic Lipa staff, doctors, and
                administrators. By logging in to and using the System, you
                acknowledge that you have read, understood these Terms.
            </p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">1. Authorized Users</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">The System is intended solely for authorized PolyClinic personnel.</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>User accounts are created and managed exclusively by the PolyClinic Administrator.</li>
                <li>Staff members are not permitted to create their own accounts.</li>
                <li>Access to the System is granted only for official work-related responsibilities.</li>
                <li>PolyClinic reserves the right to suspend or terminate access at any time when necessary.</li>
            </ul>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">2. Confidentiality and Data Privacy</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">
                The System contains Sensitive Personal Information, including
                Personally Identifiable Information (PII) and health-related
                information entrusted to PolyClinic. All users are required to
                maintain the confidentiality of all patient, employee, and
                clinic information, and to access information only when
                required for legitimate business purposes.
            </p>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">Users are strictly prohibited from:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Copying, downloading, exporting, or printing confidential information without authorization.</li>
                <li>Taking screenshots or screen recordings of patient information.</li>
                <li>Sharing patient information through personal communication platforms including but not limited to Facebook Messenger, Viber, WhatsApp, Telegram, Gmail, or similar services.</li>
                <li>Disclosing confidential information to unauthorized individuals.</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Unauthorized disclosure of confidential information may result in disciplinary action, immediate termination of employment, civil liability, and criminal liability under applicable Philippine laws.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">3. Compliance with the Data Privacy Act of 2012</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">The System complies with Republic Act No. 10173 (Data Privacy Act of 2012). Users acknowledge that they process sensitive personal information as part of their duties and agree to:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Process personal data only for authorized clinical and administrative purposes.</li>
                <li>Observe confidentiality at all times.</li>
                <li>Prevent unauthorized access, disclosure, alteration, or destruction of personal information.</li>
                <li>Report any suspected data breach immediately.</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Any intentional misuse of personal data may constitute a violation of the Data Privacy Act and other applicable laws.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">4. Account Security</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">Each staff member is responsible for safeguarding their assigned account. Users must:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Keep usernames and passwords confidential.</li>
                <li>Never share login credentials with another staff member.</li>
                <li>Log out after each use, especially on shared or public computers.</li>
                <li>Immediately report suspected unauthorized access.</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Staff are fully responsible for all activities performed using their assigned account until such access is reported as compromised. Credential sharing is strictly prohibited.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">5. Separation of Clinic Data and Personal Data</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">The System serves solely as a secure portal for authorized access. Users shall not:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Export chat conversations, patient records, or reports to personal storage.</li>
                <li>Upload clinic information to personal cloud storage services such as Google Drive, iCloud, Dropbox, OneDrive, or similar platforms.</li>
                <li>Store patient or contact information outside the System unless specifically authorized by management.</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Clinic information must remain within authorized systems.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">6. Monitoring and Audit</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">To maintain operational integrity, quality assurance, and security, PolyClinic continuously monitors activities performed within the System. The Company may record and review:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Login history</li>
                <li>Chat conversations</li>
                <li>Patient inquiries</li>
                <li>Feedback responses</li>
                <li>Appointment-related actions</li>
                <li>Administrative actions</li>
                <li>Activity timestamps</li>
                <li>System usage logs</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">This monitoring applies only to activities performed within the OptiSolutions System and does not extend to staff's personal accounts, files, or other private content outside the System. By using the System, staff expressly acknowledge and consent to this monitoring.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">7. Acceptable Use</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">The System must be used professionally and solely for official clinic operations. Users shall not:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Use the chat or messaging features for personal conversations.</li>
                <li>Harass, threaten, discriminate against, or abuse patients or co-workers.</li>
                <li>Provide unauthorized or unscripted medical advice beyond their assigned responsibilities.</li>
                <li>Use offensive, defamatory, or inappropriate language.</li>
                <li>Attempt to bypass system security measures.</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Professional communication is expected at all times.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">8. Intellectual Property</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">All information generated, stored, or processed within the System remains the exclusive property of PolyClinic. This includes but is not limited to:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Chat logs</li>
                <li>Inquiry records</li>
                <li>Appointment records</li>
                <li>Feedback</li>
                <li>Reports</li>
                <li>Patient lists</li>
                <li>Templates</li>
                <li>Analytics</li>
                <li>Documentation</li>
                <li>System-generated data</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Staff acquire no ownership rights over any information created or processed using the System.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">9. Compromised or Unauthorized Access</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">If a staff member suspects that their account, or the computer used to access the System, has been compromised or accessed without authorization, they must notify PolyClinic within two (2) hours of becoming aware of the incident. PolyClinic reserves the right to:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Immediately deactivate the user's account.</li>
                <li>Revoke access to the System.</li>
                <li>Require additional security verification before restoring access.</li>
            </ul>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">Failure to promptly report a compromised account may result in disciplinary action.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">10. Employment Separation</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">Access to the System is directly tied to active employment. Upon resignation, retirement, suspension, or termination:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>User accounts will be immediately deactivated.</li>
                <li>Staff shall cease all access to the System.</li>
                <li>Former employees shall not attempt to regain access using previous credentials.</li>
            </ul>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">11. Prohibited Activities</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">Users shall not:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Attempt to hack, reverse engineer, or modify the System.</li>
                <li>Introduce malware or malicious software.</li>
                <li>Circumvent authentication mechanisms.</li>
                <li>Access information beyond their authorized permissions.</li>
                <li>Use another staff member's account.</li>
                <li>Allow unauthorized individuals to access the System.</li>
                <li>Use the System for any unlawful purpose.</li>
            </ul>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">12. Violations</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">Violation of these Terms and Conditions may result in one or more of the following:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>Suspension of System access</li>
                <li>Immediate account deactivation</li>
                <li>Administrative or disciplinary sanctions</li>
                <li>Termination of employment, where warranted</li>
                <li>Civil liability</li>
                <li>Criminal prosecution under applicable Philippine laws, including the Data Privacy Act of 2012 and the Cybercrime Prevention Act of 2012</li>
            </ul>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">13. Amendments</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.75rem;">PolyClinic reserves the right to modify these Terms and Conditions at any time to comply with operational requirements, legal obligations, or security standards. Continued use of the System after such changes constitutes acceptance of the revised Terms.</p>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">14. Acceptance</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0.5rem;">By logging into and using the OptiSolutions System, you acknowledge that:</p>
            <ul style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin: 0 0 0.75rem 1.1rem; padding: 0;">
                <li>You have read and understood these Terms and Conditions.</li>
                <li>You agree to comply with all applicable clinic policies and Philippine laws.</li>
                <li>You understand that all activities performed within the System may be monitored and audited.</li>
                <li>You accept responsibility for maintaining the confidentiality and security of your assigned account and any information accessed through the System.</li>
                <li>You understand that violations of these Terms may result in disciplinary action, termination of employment, and legal consequences where applicable.</li>
            </ul>

            <h4 style="color: var(--primary-main); font-size: 1rem; margin: 1.25rem 0 0.5rem;">15. Contact Us</h4>
            <p style="font-size: 0.9rem; line-height: 1.65; color: var(--text-dark); margin-bottom: 0;">
                TM Kalaw St., Lipa City, Batangas 4217 &middot; 0985 475 5511
            </p>
        </div>
    </div>
</div>

<script>
    // ---- Placeholder wiring for the panels that aren't wired up yet ----
    // Security (password + session) and Data & Privacy > Export are now
    // real forms/links posting to admin routes — see markup above.
    // Notifications toggles, Clinic Info, and Working Hours are still
    // pending backend wiring.

    // ----- Modal open/close behavior -----
    // Now an overlay included on every page (like admin_acc.partials.profile)
    // instead of its own route, so open/close just toggles display instead
    // of navigating.
    function openSettingsModal() {
        document.getElementById('settingsOverlay').style.display = 'flex';
    }

    function closeSettingsModal() {
        document.getElementById('settingsOverlay').style.display = 'none';
    }

    document.getElementById('settingsCloseBtn').addEventListener('click', closeSettingsModal);

    // Click on the dimmed backdrop (outside the modal card) also closes it
    document.getElementById('settingsOverlay').addEventListener('click', (e) => {
        if (e.target.id === 'settingsOverlay') closeSettingsModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeSettingsModal();
            closeAdminTermsModal();
        }
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
        if (input.id === 'darkModeToggle') return; // handled separately below
        input.addEventListener('change', () => {
            // TODO: POST { key: input.dataset.setting, value: input.checked }
            console.log('Setting changed:', input.dataset.setting, input.checked);
        });
    });

    // ----- Terms & Conditions modal -----
    function openAdminTermsModal() {
        document.getElementById('adminTermsModalOverlay').style.display = 'flex';
    }

    function closeAdminTermsModal() {
        document.getElementById('adminTermsModalOverlay').style.display = 'none';
    }

    document.getElementById('viewTermsBtn').addEventListener('click', openAdminTermsModal);
    document.getElementById('adminTermsCloseBtn').addEventListener('click', closeAdminTermsModal);

    document.getElementById('clearCacheBtn').addEventListener('click', () => {
        if (confirm('Clear cached data? This will not delete any patient records.')) {
            // TODO: call cache-clear endpoint.
            alert('Cache cleared.');
        }
    });

    // ----- Dark mode -----
    // Persisted client-side via localStorage (per-browser, like the rest
    // of the "General" settings on this page, which have no backend yet).
    // A tiny inline <script> right after the @@vite call at the top of this partial
    // already applied the stored preference before this point, so all we
    // do here is (a) reflect that state in the toggle switch, and
    // (b) update + persist it on change.
    const darkModeToggle = document.getElementById('darkModeToggle');
    darkModeToggle.checked = document.body.classList.contains('dark-mode');

    darkModeToggle.addEventListener('change', (e) => {
        document.body.classList.toggle('dark-mode', e.target.checked);
        localStorage.setItem('admin_dark_mode', e.target.checked ? '1' : '0');
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

    @if($errors->has('current_password') || $errors->has('password'))
        // Reopen the modal on the Security pane if password validation failed,
        // same pattern as the staff side.
        document.addEventListener('DOMContentLoaded', () => {
            openSettingsModal();
            document.querySelector('.settings-nav-item[data-target="pane-security"]').click();
        });
    @endif
</script>