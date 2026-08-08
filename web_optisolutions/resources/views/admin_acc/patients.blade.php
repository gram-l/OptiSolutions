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
    <title>OptiSolutions - Patient Records</title>
@vite(['resources/css/admin_css/patients.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css'])
</head>
<body>
    <!-- Header -->
     @include('admin_acc.header')
    

    <!-- Main Container -->
    <div class="container">
        @include('admin_acc.sidebar')
        <div style="flex: 1; min-width: 0;">
        <div class="page-header">
            <h2>
                <span><i class="fa-regular fa-hospital"></i></span> 
                Patient Records
            </h2>
            <p>View and manage all registered patients, their medical history, and assigned doctors</p>
            
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-number" id="totalPatients">0</div>
                <div class="stat-label">Total Patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="departmentsCount">0</div>
                <div class="stat-label">Departments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="doctorsCount">0</div>
                <div class="stat-label">Doctors Involved</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <input type="text" class="search-box" id="searchInput" placeholder="Search by name, ID, or doctor...">
                <select class="filter-select" id="departmentFilter">
                    <option value="all">All Departments</option>
                </select>
            </div>
            <button class="add-patient-btn" id="openAddModalBtn">
                <span></span>
            </button>
        </div>

        <!-- Patients Table -->
        <div class="patients-table-container">
            <table class="patients-table" id="patientsTable">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th onclick="sortTable('name')">Patient Name <span class="sort-indicator" id="sort-name"><i class="fa-solid fa-sort"></i></span></th>
                        <th onclick="sortTable('age')">Age <span class="sort-indicator" id="sort-age"><i class="fa-solid fa-sort"></i></span></th>
                        <th onclick="sortTable('department')">Department <span class="sort-indicator" id="sort-dept"><i class="fa-solid fa-sort"></i></span></th>
                        <th onclick="sortTable('doctor')">Assigned Doctor <span class="sort-indicator" id="sort-doctor"><i class="fa-solid fa-sort"></i></span></th>
                        <th> </th>
                    </tr>
                </thead>
                <tbody id="patientsTableBody">
                    <!-- Dynamic content -->
                </tbody>
            </table>
        </div>
    </div>
</div>
    <!-- Add/Edit Patient Modal -->
    <div id="patientModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add New Patient</h3>
            <form id="patientForm">
                <input type="hidden" id="patientId" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" id="patientFirstName" required placeholder="e.g., Maria" maxlength="100">
                        <div class="field-error" id="err-patient_fname"></div>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" id="patientLastName" required placeholder="e.g., Santos" maxlength="100">
                        <div class="field-error" id="err-patient_lname"></div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Birthdate</label>
                        <input type="date" id="patientBirthdate">
                        <div class="field-error" id="err-patient_birthdate"></div>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" id="patientPhone" placeholder="e.g., 09123456789">
                        <div class="field-error" id="err-patient_contact"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="patientEmail" placeholder="e.g., maria.santos@email.com">
                    <div class="field-error" id="err-patient_email"></div>
                </div>
                <p style="font-size:0.8rem; color:#7f8c8d;">
                    Department and assigned doctor are set automatically from the patient's visit history — they aren't entered here.
                </p>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save" id="saveBtn">Save Patient</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Patient Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <h3>Patient Details</h3>
            <div id="viewDetails">
                <!-- Dynamic content -->
            </div>
            <div id="viewVisitHistory">
                <!-- Dynamic content -->
            </div>
            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <style>
        .field-error { color: #c0392b; font-size: 0.75rem; margin-top: 0.25rem; }
        .visit-history-table { width: 100%; border-collapse: collapse; margin-top: 0.75rem; font-size: 0.82rem; }
        .visit-history-table th { text-align: left; padding: 0.5rem; color: #95a5a6; font-size: 0.72rem; text-transform: uppercase; border-bottom: 1px solid #f0f4f8; }
        .visit-history-table td { padding: 0.5rem; border-bottom: 1px solid #f0f4f8; }
    </style>

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const LIST_URL = '/admin_acc/patients/list';
        const PATIENT_BASE = '/admin_acc/patients';

        let patientsData = [];
        let currentSort = { column: 'name', direction: 'asc' };

        async function loadPatients() {
            const tbody = document.getElementById('patientsTableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Loading...</td></tr>';

            try {
                const res = await fetch(LIST_URL, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (!data.success) {
                    tbody.innerHTML = `<tr><td colspan="6" class="empty-state">${escapeHtml(data.message || 'Failed to load patients.')}</td></tr>`;
                    return;
                }
                patientsData = data.patients;
                populateDepartmentFilter();
                renderPatients();
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Could not reach the server. Please try again.</td></tr>';
            }
        }

        function populateDepartmentFilter() {
            const select = document.getElementById('departmentFilter');
            const current = select.value;
            const depts = [...new Set(patientsData.map(p => p.department).filter(Boolean))].sort();
            select.innerHTML = '<option value="all">All Departments</option>' +
                depts.map(d => `<option value="${escapeHtml(d)}">${escapeHtml(d)}</option>`).join('');
            if (depts.includes(current)) select.value = current;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Render patients table
        function renderPatients() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const deptFilter = document.getElementById('departmentFilter').value;

            let filtered = patientsData.filter(patient => {
                const matchesSearch = patient.name.toLowerCase().includes(searchTerm) ||
                                     String(patient.id).toLowerCase().includes(searchTerm) ||
                                     (patient.doctor || '').toLowerCase().includes(searchTerm);
                const matchesDept = deptFilter === 'all' || patient.department === deptFilter;
                return matchesSearch && matchesDept;
            });

            // Sort
            filtered.sort((a, b) => {
                let valA = a[currentSort.column];
                let valB = b[currentSort.column];
                if (currentSort.column === 'age') {
                    valA = Number(valA) || 0;
                    valB = Number(valB) || 0;
                } else {
                    valA = String(valA || '').toLowerCase();
                    valB = String(valB || '').toLowerCase();
                }
                if (valA < valB) return currentSort.direction === 'asc' ? -1 : 1;
                if (valA > valB) return currentSort.direction === 'asc' ? 1 : -1;
                return 0;
            });

            const tbody = document.getElementById('patientsTableBody');

            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No patients found. Click "Add New Patient" to create a record.</td></tr>';
            } else {
                tbody.innerHTML = filtered.map(patient => `
                    <tr>
                        <td><code>${patient.id}</code></td>
                        <td style="font-weight: 500;">${escapeHtml(patient.name)}</td>
                        <td>${patient.age != null ? patient.age + ' years' : '—'}</td>
                        <td>${escapeHtml(patient.department || '—')}</td>
                        <td>${escapeHtml(patient.doctor || '—')}</td>
                        <td class="action-buttons">
                            <button class="btn-icon btn-view" onclick="viewPatient(${patient.id})"> <i class="fa-regular fa-eye"></i> View</button>
                            <button class="btn-icon btn-edit" onclick="editPatient(${patient.id})"> <i class="fa-regular fa-edit"></i> Edit</button>
                        </td>
                    </tr>
                `).join('');
            }

            updateStats(filtered);
        }

        function updateStats(filtered) {
            document.getElementById('totalPatients').innerText = patientsData.length;
            document.getElementById('departmentsCount').innerText = new Set(patientsData.map(p => p.department).filter(Boolean)).size;
            document.getElementById('doctorsCount').innerText = new Set(patientsData.map(p => p.doctor).filter(Boolean)).size;
        }

        // Sorting
        function sortTable(column) {
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }

            ['name', 'age', 'department', 'doctor'].forEach(col => {
                const indicator = document.getElementById(`sort-${col === 'department' ? 'dept' : col}`);
                if (col === column) {
                    indicator.textContent = currentSort.direction === 'asc' ? '↑' : '↓';
                } else {
                    indicator.textContent = '↕';
                }
            });

            renderPatients();
        }

        // ── Field error helpers ──
        function setFieldError(field, message) {
            const el = document.getElementById(`err-${field}`);
            if (el) el.textContent = message;
        }
        function clearAllFieldErrors() {
            ['patient_fname', 'patient_lname', 'patient_birthdate', 'patient_contact', 'patient_email']
                .forEach(f => setFieldError(f, ''));
        }

        // Open Add Modal
        function openAddModal() {
            clearAllFieldErrors();
            document.getElementById('modalTitle').innerText = 'Add New Patient';
            document.getElementById('patientForm').reset();
            document.getElementById('patientId').value = '';
            document.getElementById('patientModal').style.display = 'flex';
        }

        // Edit Patient
        function editPatient(id) {
            const patient = patientsData.find(p => p.id === id);
            if (!patient) return;

            clearAllFieldErrors();
            document.getElementById('modalTitle').innerText = 'Edit Patient';
            document.getElementById('patientId').value = patient.id;
            document.getElementById('patientFirstName').value = patient.first_name || '';
            document.getElementById('patientLastName').value = patient.last_name || '';
            document.getElementById('patientBirthdate').value = patient.birthdate || '';
            document.getElementById('patientPhone').value = patient.phone || '';
            document.getElementById('patientEmail').value = patient.email || '';
            document.getElementById('patientModal').style.display = 'flex';
        }

        // Save patient (add or edit)
        async function savePatient(event) {
            event.preventDefault();
            clearAllFieldErrors();

            const id = document.getElementById('patientId').value;
            const payload = {
                patient_fname: document.getElementById('patientFirstName').value.trim(),
                patient_lname: document.getElementById('patientLastName').value.trim(),
                patient_birthdate: document.getElementById('patientBirthdate').value || null,
                patient_contact: document.getElementById('patientPhone').value.trim() || null,
                patient_email: document.getElementById('patientEmail').value.trim() || null,
            };

            const isEdit = !!id;
            const url = isEdit ? `${PATIENT_BASE}/${id}` : PATIENT_BASE;

            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            try {
                const res = await fetch(url, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (res.status === 422 && data.errors) {
                    Object.keys(data.errors).forEach(key => setFieldError(key, data.errors[key][0]));
                    return;
                }

                if (!data.success) {
                    alert(data.message || 'Something went wrong.');
                    return;
                }

                closeModal();
                await loadPatients();
            } catch (e) {
                alert('Could not reach the server. Please try again.');
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Patient';
            }
        }

        // View patient details (+ visit history)
        async function viewPatient(id) {
            const patient = patientsData.find(p => p.id === id);
            if (!patient) return;

            const detailsHtml = `
                <div class="form-group"><strong>Patient ID:</strong> ${patient.id}</div>
                <div class="form-group"><strong>Full Name:</strong> ${escapeHtml(patient.name)}</div>
                <div class="form-group"><strong>Age:</strong> ${patient.age != null ? patient.age + ' years' : 'Unknown'}</div>
                <div class="form-group"><strong>Birthdate:</strong> ${patient.birthdate || 'Not provided'}</div>
                <div class="form-group"><strong>Contact:</strong> ${patient.phone || 'Not provided'}</div>
                <div class="form-group"><strong>Email:</strong> ${patient.email || 'Not provided'}</div>
            `;
            document.getElementById('viewDetails').innerHTML = detailsHtml;
            document.getElementById('viewVisitHistory').innerHTML = '<p style="font-size:0.85rem;color:#7f8c8d;">Loading visit history...</p>';
            document.getElementById('viewModal').style.display = 'flex';

            try {
                const res = await fetch(`${PATIENT_BASE}/${id}/visits`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (!data.success || data.visits.length === 0) {
                    document.getElementById('viewVisitHistory').innerHTML = '<p style="font-size:0.85rem;color:#7f8c8d;">No visit history yet.</p>';
                    return;
                }
                document.getElementById('viewVisitHistory').innerHTML = `
                    <strong style="font-size:0.85rem;">Visit History</strong>
                    <table class="visit-history-table">
                        <thead><tr><th>Date</th><th>Service</th><th>Doctor</th><th>Notes</th></tr></thead>
                        <tbody>
                            ${data.visits.map(v => `
                                <tr>
                                    <td>${v.visit_date || '—'}</td>
                                    <td>${escapeHtml(v.service_type || '—')}</td>
                                    <td>${escapeHtml(v.doctor_name || '—')}</td>
                                    <td>${escapeHtml(v.notes || '—')}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } catch (e) {
                document.getElementById('viewVisitHistory').innerHTML = '<p style="font-size:0.85rem;color:#c0392b;">Could not load visit history.</p>';
            }
        }

        function closeModal() {
            document.getElementById('patientModal').style.display = 'none';
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                alert('Logging out... Redirecting to login page.');
            }
        }

        // Event listeners
        document.getElementById('openAddModalBtn').addEventListener('click', openAddModal);
        document.getElementById('patientForm').addEventListener('submit', savePatient);
        document.getElementById('searchInput').addEventListener('input', () => renderPatients());
        document.getElementById('departmentFilter').addEventListener('change', () => renderPatients());

        // Close modals on outside click
        window.onclick = function(event) {
            const modal1 = document.getElementById('patientModal');
            const modal2 = document.getElementById('viewModal');
            if (event.target === modal1) closeModal();
            if (event.target === modal2) closeViewModal();
        }

        // Initial load
        loadPatients();
    </script>
</body>
</html>