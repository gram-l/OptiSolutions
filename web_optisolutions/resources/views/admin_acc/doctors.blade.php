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
    <title>OptiSolutions - Manage Doctors</title>

    <!-- Vite CSS -->
    @vite(['resources/css/admin_css/doctors.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css'])

    <style>
        /* ── Schedule session builder in the modal ── */
        .session-day-group { margin-bottom: 0.75rem; }
        .session-day-group h5 {
            font-size: 0.82rem;
            font-weight: 700;
            margin: 0 0 0.35rem;
            color: #062744;
        }
        .session-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f4f6fb;
            border-radius: 8px;
            padding: 0.4rem 0.6rem;
            margin-bottom: 0.35rem;
            font-size: 0.82rem;
        }
        .session-row button {
            background: none;
            border: none;
            color: #c0392b;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .add-session-row {
            display: grid;
            grid-template-columns: 1.3fr 1fr 1fr auto;
            gap: 0.5rem;
            align-items: end;
            margin-top: 0.4rem;
        }
        .add-session-row label {
            display: block;
            font-size: 0.72rem;
            color: #6b7280;
            margin-bottom: 0.2rem;
        }
        .add-session-row select,
        .add-session-row input[type="time"] {
            width: 100%;
            padding: 0.4rem;
            border: 1px solid #d7dce3;
            border-radius: 6px;
            font-size: 0.82rem;
        }
        .add-session-btn {
            background: #0E62AA;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.45rem 0.7rem;
            cursor: pointer;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        .field-error {
            color: #c0392b;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        .schedule-error-banner {
            color: #c0392b;
            font-size: 0.8rem;
            margin-top: 0.35rem;
        }
        .photo-upload-row {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 0.75rem;
        }
        .photo-preview {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            overflow: hidden;
            background: #eef2f7;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.5rem;
            color: #9aa5b1;
        }
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .btn-save[disabled], .btn-save:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <!-- Header -->
    @include('admin_acc.header')

    <!-- Main Container -->
    <div class="container">
        <!-- Sidebar Navigation -->
         @include('admin_acc.sidebar')
          <div style="flex: 1; min-width: 0;">
        <div class="page-header">
            <h2>
                <span><i class="fa-solid fa-user-doctor"></i></span> 
                Manage Doctors
            </h2>
            <p>Add, edit, or remove doctor profiles and manage their schedules</p>
            
        </div>

         <div class="action-bar">
           
            <div style="display: flex; gap: 1rem;">
                <input type="text" class="search-box" id="searchInput" placeholder="Search by name or specialty...">
                <select class="filter-select" id="departmentFilter">
                    <option value="all">All Departments</option>
                </select>
                <select class="filter-select" id="statusFilter">
                    <option value="all">All Doctors</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>
            </div>
             <button class="add-doctor-btn" id="openAddModalBtn">
                <span><i class="fa-solid fa-plus"></i></span> Add New Doctor
            </button>
        </div>

        <!-- Doctors Grid -->
        <div class="doctors-grid" id="doctorsGrid">
            <!-- Dynamic content -->
        </div>
        </div>
    </div>

    <!-- Add/Edit Doctor Modal -->
    <div id="doctorModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add New Doctor</h3>
            <form id="doctorForm" novalidate>
                <input type="hidden" id="doctorId" value="">

                <div class="photo-upload-row">
                    <div class="photo-preview" id="photoPreview"><i class="fa-solid fa-user-doctor"></i></div>
                    <div>
                        <input type="file" id="docPhoto" accept="image/png,image/jpeg,image/webp">
                        <div class="field-error" id="err-profile_image"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" id="docName" required placeholder="e.g., Dr. Maria Reyes" maxlength="100">
                    <div class="field-error" id="err-doctor_name"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Specialty *</label>
                        <input type="text" id="docSpecialty" required placeholder="e.g., Ophthalmology" maxlength="60">
                        <div class="field-error" id="err-specialty"></div>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" id="docPhone" placeholder="e.g., 09123456789">
                        <div class="field-error" id="err-contact_number"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Schedule *</label>
                    <div id="sessionGroups"></div>
                    <div class="schedule-error-banner" id="err-schedule_sessions"></div>

                    <div class="add-session-row">
                        <div>
                            <label>Day</label>
                            <select id="sessionDay">
                                <option>Monday</option>
                                <option>Tuesday</option>
                                <option>Wednesday</option>
                                <option>Thursday</option>
                                <option>Friday</option>
                                <option>Saturday</option>
                                <option>Sunday</option>
                            </select>
                        </div>
                        <div>
                            <label>Start Time</label>
                            <input type="time" id="sessionStart" value="08:00">
                        </div>
                        <div>
                            <label>End Time</label>
                            <input type="time" id="sessionEnd" value="17:00">
                        </div>
                        <button type="button" class="add-session-btn" id="addSessionBtn">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </div>
                    <div class="field-error" id="err-session-inline"></div>
                </div>

                <div class="form-group">
                    <label>Description / Bio</label>
                    <textarea id="docDescription" rows="3" maxlength="500" placeholder="Doctor's background, experience, and qualifications..."></textarea>
                    <div class="field-error" id="err-description"></div>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save" id="saveBtn">Save Doctor</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const LIST_URL = '/admin_acc/doctors/list';
    const API_BASE = '/admin_acc/doctors';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    let doctorsData = [];
    let currentEditId = null;
    let sessions = []; // [{day, start_time, end_time}] for the open modal
    let selectedPhotoFile = null;
    let existingPhotoUrl = null;

    // ── Data loading ──────────────────────────────────────────
    async function loadDoctors() {
        try {
            const res = await fetch(LIST_URL, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.success) {
                doctorsData = data.doctors;
                populateDepartmentFilter();
                renderDoctors();
            } else {
                showGridMessage(data.message || 'Failed to load doctors.');
            }
        } catch (e) {
            showGridMessage('Could not reach the server. Please try again.');
        }
    }

    function populateDepartmentFilter() {
        const select = document.getElementById('departmentFilter');
        const current = select.value;
        const departments = [...new Set(doctorsData.map(d => d.specialty).filter(Boolean))].sort();
        select.innerHTML = '<option value="all">All Departments</option>' +
            departments.map(d => `<option value="${escapeHtml(d)}">${escapeHtml(d)}</option>`).join('');
        if (departments.includes(current)) select.value = current;
    }

    function showGridMessage(msg) {
        document.getElementById('doctorsGrid').innerHTML =
            `<div class="empty-state">${escapeHtml(msg)}</div>`;
    }

    // ── Rendering ──────────────────────────────────────────────
    function renderDoctors() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        const departmentFilter = document.getElementById('departmentFilter').value;

        let filtered = doctorsData.filter(doc => {
            const matchesSearch = doc.name.toLowerCase().includes(searchTerm) ||
                                 doc.specialty.toLowerCase().includes(searchTerm);
            const matchesStatus = statusFilter === 'all' ||
                                 (statusFilter === 'active' && doc.active) ||
                                 (statusFilter === 'inactive' && !doc.active);
            const matchesDepartment = departmentFilter === 'all' || doc.specialty === departmentFilter;
            return matchesSearch && matchesStatus && matchesDepartment;
        });

        const grid = document.getElementById('doctorsGrid');

        if (filtered.length === 0) {
            grid.innerHTML = '<div class="empty-state">No doctors found. Click "Add New Doctor" to create a profile.</div>';
            return;
        }

        grid.innerHTML = filtered.map(doc => `
            <div class="doctor-card ${!doc.active ? 'inactive' : ''}">
                <div class="card-header">
                    <div class="doctor-avatar">
                        ${doc.profile_image_url
                            ? `<img src="${escapeHtml(doc.profile_image_url)}" alt="${escapeHtml(doc.name)}">`
                            : `<i class="fa-solid fa-user-doctor"></i>`}
                    </div>
                    <div class="doctor-name">${escapeHtml(doc.name)}</div>
                    <div class="doctor-specialty">${escapeHtml(doc.specialty)}</div>
                    <span class="status-badge ${doc.active ? 'status-active' : 'status-inactive'}">
                        ${doc.active ? '● ACTIVE' : '○ INACTIVE'}
                    </span>
                </div>
                <div class="card-body">
                    <div class="doctor-description">
                        ${escapeHtml((doc.description || '').length > 100 ? doc.description.substring(0, 100) + '...' : (doc.description || 'No description provided.'))}
                    </div>
                    <div class="schedule-info">
                        <div class="schedule-title">
                            <span><i class="fa-solid fa-clock"></i></span> Schedule
                        </div>
                        <div class="schedule-text">${escapeHtml(doc.schedule)}</div>
                        ${doc.phone ? `<div class="schedule-text" style="margin-top: 0.5rem;">📞 ${escapeHtml(doc.phone)}</div>` : ''}
                    </div>
                    <div class="card-actions">
                        <button class="btn-icon btn-edit" onclick="openEditModal(${doc.id})">
                            <i class="fa-solid fa-edit"></i> Edit
                        </button>
                        ${doc.active ?
                            `<button class="btn-icon btn-deactivate" onclick="toggleActiveStatus(${doc.id})">
                                <i class="fa-solid fa-lock"></i> Deactivate
                            </button>` :
                            `<button class="btn-icon btn-activate" onclick="toggleActiveStatus(${doc.id})">
                                <i class="fa-solid fa-unlock"></i> Activate
                            </button>`
                        }
                        <button class="btn-icon btn-remove" onclick="removeDoctor(${doc.id})">
                            <i class="fa-solid fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ── Schedule session builder ──────────────────────────────
    function minutesOf(hhmm) {
        const [h, m] = hhmm.split(':').map(Number);
        return h * 60 + m;
    }

    function fmtTime12(hhmm) {
        const [h, m] = hhmm.split(':').map(Number);
        const period = h >= 12 ? 'pm' : 'am';
        const hour = h % 12 === 0 ? 12 : h % 12;
        return `${hour}:${String(m).padStart(2, '0')} ${period}`;
    }

    function sessionsOverlap(a, b) {
        if (a.day !== b.day) return false;
        const aStart = minutesOf(a.start_time), aEnd = minutesOf(a.end_time);
        const bStart = minutesOf(b.start_time), bEnd = minutesOf(b.end_time);
        return aStart < bEnd && bStart < aEnd;
    }

    function renderSessions() {
        const container = document.getElementById('sessionGroups');
        const daysUsed = WEEKDAYS.filter(day => sessions.some(s => s.day === day));

        if (daysUsed.length === 0) {
            container.innerHTML = '<div style="font-size:0.8rem;color:#8a94a3;">No sessions added yet.</div>';
            return;
        }

        container.innerHTML = daysUsed.map(day => {
            const rows = sessions
                .map((s, i) => ({ ...s, i }))
                .filter(s => s.day === day)
                .map(s => `
                    <div class="session-row">
                        <span>${fmtTime12(s.start_time)} - ${fmtTime12(s.end_time)}</span>
                        <button type="button" onclick="removeSession(${s.i})" title="Remove">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                `).join('');
            return `<div class="session-day-group"><h5>${day}</h5>${rows}</div>`;
        }).join('');
    }

    function removeSession(index) {
        sessions.splice(index, 1);
        renderSessions();
    }

    document.getElementById('addSessionBtn').addEventListener('click', () => {
        const day = document.getElementById('sessionDay').value;
        const start = document.getElementById('sessionStart').value;
        const end = document.getElementById('sessionEnd').value;
        const errEl = document.getElementById('err-session-inline');
        errEl.textContent = '';

        if (!start || !end) {
            errEl.textContent = 'Pick both a start and end time.';
            return;
        }
        if (minutesOf(end) <= minutesOf(start)) {
            errEl.textContent = 'End time must be after start time.';
            return;
        }

        const candidate = { day, start_time: start, end_time: end };
        if (sessions.some(s => sessionsOverlap(s, candidate))) {
            errEl.textContent = `This session overlaps with an existing session on ${day}.`;
            return;
        }

        sessions.push(candidate);
        renderSessions();
    });

    // ── Photo picker ──────────────────────────────────────────
    document.getElementById('docPhoto').addEventListener('change', (e) => {
        const file = e.target.files[0];
        clearFieldError('profile_image');
        if (!file) {
            selectedPhotoFile = null;
            updatePhotoPreview();
            return;
        }
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type)) {
            setFieldError('profile_image', 'Please choose a JPG, PNG, or WEBP image.');
            e.target.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            setFieldError('profile_image', 'The photo must not be larger than 2MB.');
            e.target.value = '';
            return;
        }
        selectedPhotoFile = file;
        updatePhotoPreview();
    });

    function updatePhotoPreview() {
        const preview = document.getElementById('photoPreview');
        if (selectedPhotoFile) {
            const reader = new FileReader();
            reader.onload = (ev) => {
                preview.innerHTML = `<img src="${ev.target.result}" alt="Preview">`;
            };
            reader.readAsDataURL(selectedPhotoFile);
        } else if (existingPhotoUrl) {
            preview.innerHTML = `<img src="${escapeHtml(existingPhotoUrl)}" alt="Preview">`;
        } else {
            preview.innerHTML = '<i class="fa-solid fa-user-doctor"></i>';
        }
    }

    // ── Field error helpers ───────────────────────────────────
    function setFieldError(field, message) {
        const el = document.getElementById(`err-${field}`);
        if (el) el.textContent = message;
    }
    function clearFieldError(field) {
        setFieldError(field, '');
    }
    function clearAllFieldErrors() {
        ['profile_image', 'doctor_name', 'specialty', 'contact_number', 'schedule_sessions', 'description']
            .forEach(clearFieldError);
        document.getElementById('err-session-inline').textContent = '';
    }

    // ── Modal open/close ──────────────────────────────────────
    function openAddModal() {
        currentEditId = null;
        sessions = [];
        selectedPhotoFile = null;
        existingPhotoUrl = null;
        clearAllFieldErrors();
        document.getElementById('modalTitle').innerText = 'Add New Doctor';
        document.getElementById('doctorForm').reset();
        document.getElementById('doctorId').value = '';
        renderSessions();
        updatePhotoPreview();
        document.getElementById('doctorModal').style.display = 'flex';
    }

    function openEditModal(id) {
        const doctor = doctorsData.find(d => d.id === id);
        if (!doctor) return;

        currentEditId = id;
        sessions = (doctor.schedule_sessions || []).map(s => ({ ...s }));
        selectedPhotoFile = null;
        existingPhotoUrl = doctor.profile_image_url || null;
        clearAllFieldErrors();

        document.getElementById('modalTitle').innerText = 'Edit Doctor Profile';
        document.getElementById('doctorId').value = doctor.id;
        document.getElementById('docName').value = doctor.name;
        document.getElementById('docSpecialty').value = doctor.specialty;
        document.getElementById('docDescription').value = doctor.description || '';
        document.getElementById('docPhone').value = doctor.phone || '';
        document.getElementById('docPhoto').value = '';

        renderSessions();
        updatePhotoPreview();
        document.getElementById('doctorModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('doctorModal').style.display = 'none';
        currentEditId = null;
    }

    // ── Client-side validation (mirrors the backend rules) ────
    function validateForm(name, specialty, phone, description) {
        let ok = true;

        if (!name) { setFieldError('doctor_name', 'Required'); ok = false; }
        else if (name.length < 2) { setFieldError('doctor_name', 'Name is too short.'); ok = false; }
        else if (name.length > 100) { setFieldError('doctor_name', 'Name is too long (max 100 characters).'); ok = false; }

        if (!specialty) { setFieldError('specialty', 'Required'); ok = false; }
        else if (specialty.length > 60) { setFieldError('specialty', 'Specialty is too long (max 60 characters).'); ok = false; }

        if (phone) {
            const phoneRe = /^\+?[0-9]{7,15}$/;
            if (!phoneRe.test(phone)) { setFieldError('contact_number', 'Enter a valid phone number (digits only, 7–15 digits).'); ok = false; }
        }

        if (description.length > 500) { setFieldError('description', 'Description is too long (max 500 characters).'); ok = false; }

        if (sessions.length === 0) { setFieldError('schedule_sessions', 'Add at least one schedule session.'); ok = false; }

        const isDuplicate = doctorsData.some(d =>
            d.name.trim().toLowerCase() === name.toLowerCase() &&
            (currentEditId === null || d.id !== currentEditId)
        );
        if (isDuplicate) { setFieldError('doctor_name', `A doctor named "${name}" already exists.`); ok = false; }

        return ok;
    }

    // ── Save (create/update) ──────────────────────────────────
    async function saveDoctor(event) {
        event.preventDefault();
        clearAllFieldErrors();

        const name = document.getElementById('docName').value.trim();
        const specialty = document.getElementById('docSpecialty').value.trim();
        const description = document.getElementById('docDescription').value.trim();
        const phone = document.getElementById('docPhone').value.trim();

        if (!validateForm(name, specialty, phone, description)) return;

        const formData = new FormData();
        formData.append('doctor_name', name);
        formData.append('specialty', specialty);
        formData.append('description', description);
        formData.append('contact_number', phone);
        formData.append('schedule_sessions', JSON.stringify(sessions));
        if (selectedPhotoFile) formData.append('profile_image', selectedPhotoFile);

        const isEdit = !!currentEditId;
        if (isEdit) formData.append('_method', 'PUT'); // Laravel method-spoofing so the file still uploads

        const url = isEdit ? `${API_BASE}/${currentEditId}` : API_BASE;

        const saveBtn = document.getElementById('saveBtn');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        try {
            const res = await fetch(url, {
                method: 'POST', // POST for both; PUT is spoofed above so multipart works
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: formData,
            });
            const data = await res.json();

            if (res.status === 422 && data.errors) {
                applyServerErrors(data.errors);
                return;
            }

            if (!data.success) {
                alert(data.message || 'Something went wrong.');
                return;
            }

            closeModal();
            await loadDoctors();
        } catch (e) {
            alert('Could not reach the server. Please try again.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Doctor';
        }
    }

    function applyServerErrors(errors) {
        Object.keys(errors).forEach(key => {
            const message = errors[key][0];
            if (key.startsWith('schedule_sessions')) {
                setFieldError('schedule_sessions', message);
            } else if (key === 'profile_image') {
                setFieldError('profile_image', message);
            } else {
                setFieldError(key, message);
            }
        });
    }

    // ── Toggle / remove ────────────────────────────────────────
    async function toggleActiveStatus(id) {
        try {
            const res = await fetch(`${API_BASE}/${id}/toggle`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                await loadDoctors();
            } else {
                alert(data.message || 'Could not update status.');
            }
        } catch (e) {
            alert('Could not reach the server. Please try again.');
        }
    }

    async function removeDoctor(id) {
        const doctor = doctorsData.find(d => d.id === id);
        if (!doctor) return;

        if (!confirm(`Are you sure you want to permanently remove ${doctor.name} from the system? This action cannot be undone.`)) {
            return;
        }

        try {
            const res = await fetch(`${API_BASE}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                await loadDoctors();
            } else {
                alert(data.message || 'Could not remove doctor.');
            }
        } catch (e) {
            alert('Could not reach the server. Please try again.');
        }
    }

    // Navigation functions
    function goBackToDashboard() {
        window.location.href = "dashboard.html";
    }

    function handleLogout() {
        if (confirm('Are you sure you want to logout?')) {
            alert('Logging out... Redirecting to login page.');
        }
    }

    // Event listeners
    document.getElementById('openAddModalBtn').addEventListener('click', openAddModal);
    document.getElementById('doctorForm').addEventListener('submit', saveDoctor);
    document.getElementById('searchInput').addEventListener('input', () => renderDoctors());
    document.getElementById('statusFilter').addEventListener('change', () => renderDoctors());
    document.getElementById('departmentFilter').addEventListener('change', () => renderDoctors());

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('doctorModal');
        if (event.target === modal) closeModal();
    }

    // Initial load
    loadDoctors();
    </script>
</body>
</html>