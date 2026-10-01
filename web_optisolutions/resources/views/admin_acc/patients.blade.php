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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
            <div style="display:flex; gap:0.75rem; align-items:center;">
                <div class="download-dropdown" id="downloadDropdown">
                    <button type="button" class="download-btn" id="downloadBtn">
                        <i class="fa-solid fa-download"></i> Download <i class="fa-solid fa-chevron-down chevron"></i>
                    </button>
                    <div class="download-menu">
                        <button type="button" onclick="exportPDF()"><i class="fa-regular fa-file-pdf"></i> PDF</button>
                        <button type="button" onclick="exportCSV()"><i class="fa-regular fa-file-lines"></i> CSV</button>
                        <button type="button" onclick="exportExcel()"><i class="fa-regular fa-file-excel"></i> Excel</button>
                    </div>
                </div>
                <button class="add-patient-btn" id="openAddModalBtn">
                    <span><i class="fa-solid fa-plus"></i></span> Add New Patient
                </button>
            </div>
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
                        <th>Notes</th>
                        <th> </th>
                    </tr>
                </thead>
                <tbody id="patientsTableBody">
                    <!-- Dynamic content -->
                </tbody>
            </table>
            <div class="pagination-wrap" id="patientsPaginationWrap" style="display:none;">
                <button type="button" class="page-btn" id="patientsPrevBtn" onclick="changePatientsPage(-1)" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></button>
                <span class="page-info" id="patientsPageInfo"></span>
                <button type="button" class="page-btn" id="patientsNextBtn" onclick="changePatientsPage(1)" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
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
                <div class="form-row">
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" id="patientDepartment" disabled placeholder="No visits yet">
                    </div>
                    <div class="form-group">
                        <label>Assigned Doctor</label>
                        <input type="text" id="patientDoctor" disabled placeholder="No visits yet">
                    </div>
                </div>
                <p style="font-size:0.78rem; color:#7f8c8d; margin-top:-0.5rem;">
                    Department and doctor reflect the patient's most recent scheduled visit and update automatically as new visits are added.
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
            <div id="viewCaptureArea">
                <h3>Patient Details</h3>
                <div id="viewDetails">
                    <!-- Dynamic content -->
                </div>
                <div id="viewVisitHistory">
                    <!-- Dynamic content -->
                </div>
            </div>
            <div class="modal-buttons">
                <div class="download-dropdown" id="viewDownloadDropdown">
                    <button type="button" class="download-btn" id="viewDownloadBtn">
                        <i class="fa-solid fa-download"></i> Download <i class="fa-solid fa-chevron-down chevron"></i>
                    </button>
                    <div class="download-menu">
                        <button type="button" onclick="exportViewAsImage()"><i class="fa-regular fa-file-image"></i> Image (PNG)</button>
                        <button type="button" onclick="exportViewAsPDF()"><i class="fa-regular fa-file-pdf"></i> PDF</button>
                    </div>
                </div>
                <button class="btn-cancel" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    {{-- ===== Hidden Patient Record Template (used only for PNG/PDF download) =====
         Same design as the Staff "View Patient" download — a branded header,
         a PERSONAL DETAILS pill, a two-column detail grid, a VISIT HISTORY
         pill, and a table — instead of screenshotting the plain on-screen
         modal like this page did before. --}}
    <div id="patientPdfTemplate" style="position: absolute; left: -99999px; top: 0; width: 720px; background: #ffffff; font-family: 'Poppins', sans-serif;">
        <div style="background: linear-gradient(135deg, #b06ab3, #b06ab3); padding: 1.5rem 2rem; border-radius: 14px 14px 0 0;">
            <div style="color:#ffffff; font-size: 1.6rem; font-weight: 700; letter-spacing: 3px; text-transform: uppercase;">Patient Record</div>
        </div>

        <div style="border: 1px solid #e6d6f2; border-top: none; border-radius: 0 0 14px 14px; padding: 1.75rem 2rem 2rem; background: #ffffff;">

            <div style="background: linear-gradient(90deg, #b06ab3, #b06ab3); color: #ffffff; text-align: center; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; letter-spacing: 1px; font-size: 0.85rem; margin-bottom: 1.25rem;">
                PERSONAL DETAILS
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.9rem 2rem; font-size: 0.92rem; color: #2d2d2d; margin-bottom: 1.75rem;">
                <div><strong>Patient ID:</strong> <span id="pdfPatientId"></span></div>
                <div><strong>Full Name:</strong> <span id="pdfPatientName"></span></div>
                <div><strong>Birthdate:</strong> <span id="pdfPatientBirthdate"></span></div>
                <div><strong>Contact No.:</strong> <span id="pdfPatientContact"></span></div>
                <div><strong>Email:</strong> <span id="pdfPatientEmail"></span></div>
                <div><strong>Department:</strong> <span id="pdfPatientDept"></span></div>
                <div><strong>Assigned Doctor:</strong> <span id="pdfPatientDoctor"></span></div>
            </div>

            <div style="background: linear-gradient(90deg, #b06ab3, #b06ab3); color: #ffffff; text-align: center; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; letter-spacing: 1px; font-size: 0.85rem; margin-bottom: 1rem;">
                VISIT HISTORY
            </div>

            <table style="width: 100%; border-collapse: collapse; font-size: 0.82rem;">
                <thead>
                    <tr style="border-bottom: 2px solid #b06ab3;">
                        <th style="text-align: left; padding: 0.5rem 0.6rem; color: #b06ab3; white-space: nowrap;">DATE</th>
                        <th style="text-align: left; padding: 0.5rem 0.6rem; color: #b06ab3; white-space: nowrap;">SERVICE</th>
                        <th style="text-align: left; padding: 0.5rem 0.6rem; color: #b06ab3; white-space: nowrap;">DOCTOR</th>
                        <th style="text-align: left; padding: 0.5rem 0.6rem; color: #b06ab3;">NOTES / DIAGNOSIS</th>
                    </tr>
                </thead>
                <tbody id="pdfVisitTableBody"></tbody>
            </table>

            <div style="margin-top: 1.75rem; font-size: 0.72rem; color: #999; text-align: right;">
                Generated on <span id="pdfGeneratedDate"></span> &middot; PolyClinic Admin Records
            </div>
        </div>
    </div>

    <style>
        .field-error { color: #c0392b; font-size: 0.75rem; margin-top: 0.25rem; }
        .visit-history-table { width: 100%; border-collapse: collapse; margin-top: 0.75rem; font-size: 0.82rem; }
        .visit-history-table th { text-align: left; padding: 0.5rem; color: #95a5a6; font-size: 0.72rem; text-transform: uppercase; border-bottom: 1px solid #f0f4f8; }
        .visit-history-table td { padding: 0.5rem; border-bottom: 1px solid #f0f4f8; }

        /* ── Pagination ── */
        .pagination-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.75rem;
            padding: 0.9rem;
            border-top: 1px solid #ECF0F1;
        }
        .pagination-wrap .page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 0.5rem;
            border: 1px solid #d7dce3;
            border-radius: 6px;
            background: #fff;
            color: #2c3e50;
            font-size: 0.82rem;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        }
        .pagination-wrap .page-btn:hover:not(:disabled) {
            background: var(--primary-main, #0E62AA);
            border-color: var(--primary-main, #0E62AA);
            color: #fff;
        }
        .pagination-wrap .page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .pagination-wrap .page-info { font-size: 0.82rem; color: #7f8c8d; }

        /* ── Download button + dropdown ── */
        .download-dropdown {
            position: relative;
            display: inline-block;
        }
        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.1rem;
            background: var(--primary-main, #0E62AA);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            font-family: inherit;
            transition: background 0.2s ease, transform 0.1s ease;
        }
        .download-btn:hover { background: #0b4f8a; }
        .download-btn .chevron { font-size: 0.65rem; transition: transform 0.2s ease; }
        .download-dropdown.open .download-btn .chevron { transform: rotate(180deg); }
        .download-menu {
            display: none;
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 0;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            overflow: hidden;
            min-width: 170px;
            z-index: 50;
        }
        .download-dropdown.open .download-menu { display: block; }
        .download-menu button {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            width: 100%;
            padding: 0.65rem 1rem;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
            font-size: 0.85rem;
            font-family: inherit;
            color: #333;
            transition: background 0.15s ease;
        }
        .download-menu button:hover { background: #ECF0F1; }
        .download-menu button i { width: 16px; color: var(--primary-main, #0E62AA); }
    </style>

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const LIST_URL = '/admin_acc/patients/list';
        const PATIENT_BASE = '/admin_acc/patients';

        let patientsData = [];
        let currentSort = { column: 'name', direction: 'asc' };
        let currentViewPatientId = null;
        const ROWS_PER_PAGE = 10;
        let currentPage = 1;

        async function loadPatients() {
            const tbody = document.getElementById('patientsTableBody');
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Loading...</td></tr>';

            try {
                const res = await fetch(LIST_URL, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (!data.success) {
                    tbody.innerHTML = `<tr><td colspan="7" class="empty-state">${escapeHtml(data.message || 'Failed to load patients.')}</td></tr>`;
                    return;
                }
                patientsData = data.patients;
                populateDepartmentFilter();
                renderPatients();
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Could not reach the server. Please try again.</td></tr>';
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

        // Shared filter + sort, used by both the table render and exports
        function getFilteredPatients() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const deptFilter = document.getElementById('departmentFilter').value;

            let filtered = patientsData.filter(patient => {
                const matchesSearch = patient.name.toLowerCase().includes(searchTerm) ||
                                     String(patient.id).toLowerCase().includes(searchTerm) ||
                                     (patient.doctor || '').toLowerCase().includes(searchTerm);
                const matchesDept = deptFilter === 'all' || patient.department === deptFilter;
                return matchesSearch && matchesDept;
            });

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

            return filtered;
        }

        // Render patients table
        function renderPatients() {
            const filtered = getFilteredPatients();
            const tbody = document.getElementById('patientsTableBody');
            const paginationWrap = document.getElementById('patientsPaginationWrap');

            const totalPages = Math.max(1, Math.ceil(filtered.length / ROWS_PER_PAGE));
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;
            const startIdx = (currentPage - 1) * ROWS_PER_PAGE;
            const pageRows = filtered.slice(startIdx, startIdx + ROWS_PER_PAGE);

            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No patients found. Click "Add New Patient" to create a record.</td></tr>';
            } else {
                tbody.innerHTML = pageRows.map(patient => `
                    <tr>
                        <td data-label="Patient ID"><code>${patient.id}</code></td>
                        <td data-label="Patient Name" style="font-weight: 500;">${escapeHtml(patient.name)}</td>
                        <td data-label="Age">${patient.age != null ? patient.age + ' years' : '—'}</td>
                        <td data-label="Department">${escapeHtml(patient.department || '—')}</td>
                        <td data-label="Assigned Doctor">${escapeHtml(patient.doctor || '—')}</td>
                        <td data-label="Notes"><small style="color:#7f8c8d">${escapeHtml((patient.notes || '').length > 40 ? patient.notes.substring(0, 40) + '...' : (patient.notes || '—'))}</small></td>
                        <td data-label="" class="action-buttons">
                            <button class="btn-icon btn-view" onclick="viewPatient(${patient.id})"> <i class="fa-regular fa-eye"></i> View</button>
                            <button class="btn-icon btn-edit" onclick="editPatient(${patient.id})"> <i class="fa-regular fa-edit"></i> Edit</button>
                        </td>
                    </tr>
                `).join('');
            }

            paginationWrap.style.display = totalPages > 1 ? 'flex' : 'none';
            document.getElementById('patientsPageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
            document.getElementById('patientsPrevBtn').disabled = currentPage <= 1;
            document.getElementById('patientsNextBtn').disabled = currentPage >= totalPages;

            updateStats(filtered);
        }

        function changePatientsPage(delta) {
            currentPage += delta;
            renderPatients();
            document.querySelector('.patients-table-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
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

            currentPage = 1;
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
            document.getElementById('patientDepartment').value = '';
            document.getElementById('patientDoctor').value = '';
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
            document.getElementById('patientDepartment').value = patient.department || '';
            document.getElementById('patientDoctor').value = patient.doctor || '';
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
        let currentViewPatientVisits = [];

        async function viewPatient(id) {
            const patient = patientsData.find(p => p.id === id);
            if (!patient) return;

            currentViewPatientId = id;
            currentViewPatientVisits = [];

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
                currentViewPatientVisits = data.visits;
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

        /* ── Download dropdown (table export) ── */
        function toggleDownloadMenu() {
            document.getElementById('downloadDropdown').classList.toggle('open');
        }

        function getExportRows() {
            return getFilteredPatients().map(p => ({
                'Patient ID': p.id,
                Name: p.name || 'Unknown',
                Age: p.age != null ? p.age : '—',
                Department: p.department || '—',
                'Assigned Doctor': p.doctor || '—',
                Notes: p.notes || '—',
            }));
        }

        function downloadBlob(blob, filename) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function exportCSV() {
            const rows = getExportRows();
            if (rows.length === 0) { alert('No patients to export.'); return; }
            const headers = Object.keys(rows[0]);
            const csvLines = [
                headers.join(','),
                ...rows.map(r => headers.map(h => `"${String(r[h]).replace(/"/g, '""')}"`).join(','))
            ];
            const blob = new Blob(["\ufeff" + csvLines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            downloadBlob(blob, 'patients.csv');
            toggleDownloadMenu();
        }

        function exportExcel() {
            const rows = getExportRows();
            if (rows.length === 0) { alert('No patients to export.'); return; }
            const ws = XLSX.utils.json_to_sheet(rows);
            ws['!cols'] = [{ wch: 12 }, { wch: 24 }, { wch: 8 }, { wch: 20 }, { wch: 22 }, { wch: 35 }];
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Patients');
            XLSX.writeFile(wb, 'patients.xlsx');
            toggleDownloadMenu();
        }

        function exportPDF() {
            const rows = getExportRows();
            if (rows.length === 0) { alert('No patients to export.'); return; }
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.setFontSize(14);
            doc.text('Patient Records', 14, 15);
            doc.autoTable({
                startY: 22,
                head: [['Patient ID', 'Name', 'Age', 'Department', 'Assigned Doctor', 'Notes']],
                body: rows.map(r => [r['Patient ID'], r.Name, r.Age, r.Department, r['Assigned Doctor'], r.Notes]),
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [14, 98, 170] },
                columnStyles: { 5: { cellWidth: 55 } },
            });
            doc.save('patients.pdf');
            toggleDownloadMenu();
        }

        document.getElementById('downloadBtn').addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDownloadMenu();
        });
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('downloadDropdown');
            if (dropdown.classList.contains('open') && !dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });

        /* ── Download dropdown (view modal export) ── */
        function toggleViewDownloadMenu() {
            document.getElementById('viewDownloadDropdown').classList.toggle('open');
        }

        function buildPatientPdfTemplate(id) {
            const patient = patientsData.find(p => p.id === id);
            if (!patient) return null;

            document.getElementById('pdfPatientId').textContent = patient.id;
            document.getElementById('pdfPatientName').textContent = patient.name || '—';
            document.getElementById('pdfPatientBirthdate').textContent = patient.birthdate || '—';
            document.getElementById('pdfPatientContact').textContent = patient.phone || '—';
            document.getElementById('pdfPatientEmail').textContent = patient.email || '—';
            document.getElementById('pdfPatientDept').textContent = patient.department || '—';
            document.getElementById('pdfPatientDoctor').textContent = patient.doctor || '—';
            document.getElementById('pdfGeneratedDate').textContent = new Date().toLocaleString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit'
            });

            const tbody = document.getElementById('pdfVisitTableBody');
            if (currentViewPatientVisits.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="padding:0.75rem 0.6rem; text-align:center; color:#999;">No visit records.</td></tr>';
            } else {
                tbody.innerHTML = currentViewPatientVisits.map(function (v) {
                    return '<tr style="border-bottom:1px solid #eee;">' +
                        '<td style="padding:0.55rem 0.6rem; vertical-align:top; white-space:nowrap;">' + escapeHtml(v.visit_date || '—') + '</td>' +
                        '<td style="padding:0.55rem 0.6rem; vertical-align:top; white-space:nowrap;">' + escapeHtml(v.service_type || '—') + '</td>' +
                        '<td style="padding:0.55rem 0.6rem; vertical-align:top; white-space:nowrap;">' + escapeHtml(v.doctor_name || '—') + '</td>' +
                        '<td style="padding:0.55rem 0.6rem; vertical-align:top;">' + escapeHtml(v.notes || '—') + '</td>' +
                        '</tr>';
                }).join('');
            }

            return document.getElementById('patientPdfTemplate');
        }

        async function exportViewAsImage() {
            toggleViewDownloadMenu();
            const el = buildPatientPdfTemplate(currentViewPatientId);
            if (!el) return;
            const canvas = await html2canvas(el, { backgroundColor: '#ffffff', scale: 2 });
            canvas.toBlob(blob => downloadBlob(blob, `patient-${currentViewPatientId ?? 'details'}.png`));
        }

        async function exportViewAsPDF() {
            toggleViewDownloadMenu();
            const el = buildPatientPdfTemplate(currentViewPatientId);
            if (!el) return;
            const canvas = await html2canvas(el, { backgroundColor: '#ffffff', scale: 2 });
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'pt', 'a4');
            const pageWidth = pdf.internal.pageSize.getWidth();
            const margin = 30;
            const imgWidth = pageWidth - margin * 2;
            const imgHeight = canvas.height * (imgWidth / canvas.width);
            pdf.addImage(imgData, 'PNG', margin, margin, imgWidth, imgHeight);
            pdf.save(`patient-${currentViewPatientId ?? 'details'}.pdf`);
        }

        document.getElementById('viewDownloadBtn').addEventListener('click', (e) => {
            e.stopPropagation();
            toggleViewDownloadMenu();
        });
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('viewDownloadDropdown');
            if (dropdown && dropdown.classList.contains('open') && !dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });

        // Event listeners
        document.getElementById('openAddModalBtn').addEventListener('click', openAddModal);
        document.getElementById('patientForm').addEventListener('submit', savePatient);
        document.getElementById('searchInput').addEventListener('input', () => { currentPage = 1; renderPatients(); });
        document.getElementById('departmentFilter').addEventListener('change', () => { currentPage = 1; renderPatients(); });

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