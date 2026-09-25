@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div class="page-title-group" style="margin-bottom: 1rem;">
        <h3 style="margin: 0;">
            <i class="bi bi-folder2-open"></i> Patient Records
        </h3>
        <p class="page-subtitle">View and manage patient records</p>
    </div>

    <div class="action-bar" style="background: white; border-radius: 20px; padding: 1rem 1.5rem; box-shadow: var(--shadow); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <div style="position: relative; min-width: 220px;">
            <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
            <input
                type="text"
                id="patientSearch"
                placeholder="Search ID, name, or doctor..."
                style="width: 100%; padding: 0.55rem 0.75rem 0.55rem 2.25rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; outline: none;"
                onkeyup="filterPatientsTable()"
            >
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <select
                id="departmentFilter"
                style="padding: 0.55rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; outline: none; background: white; min-width: 130px;"
                onchange="filterPatientsTable()"
            >
                <option value="">All Departments</option>
                @php
                    $departments = $patients
                        ->map(fn($p) => $p->latestVisit->doctor->specialty ?? null)
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values();
                @endphp
                @foreach($departments as $dept)
                    <option value="{{ strtolower($dept) }}">{{ $dept }}</option>
                @endforeach
            </select>

            <form method="GET" action="{{ route('staff.patients') }}" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <label for="date_from" style="font-size: 0.85rem; color: #64748b; white-space: nowrap;">Visit from</label>
                <input
                    type="date"
                    id="date_from"
                    name="date_from"
                    value="{{ request('date_from') }}"
                    style="padding: 0.5rem 0.6rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; outline: none;"
                >
                <label for="date_to" style="font-size: 0.85rem; color: #64748b; white-space: nowrap;">to</label>
                <input
                    type="date"
                    id="date_to"
                    name="date_to"
                    value="{{ request('date_to') }}"
                    style="padding: 0.5rem 0.6rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; outline: none;"
                >
                <button type="submit" class="btn-sm btn-primary" style="padding: 0.5rem 1.2rem; font-size: 0.85rem; line-height: 1.2; border: none; box-sizing: border-box; cursor: pointer;">Filter</button>
                @if(request('date_from') || request('date_to'))
                    <a href="{{ route('staff.patients') }}" class="btn-sm btn-secondary" style="padding: 0.5rem 1.2rem; font-size: 0.85rem; line-height: 1.2; border: none; box-sizing: border-box; display: inline-flex; align-items: center; text-decoration: none;">Clear</a>
                @endif
            </form>
        </div>
    </div>

    @if($patients->count() > 0)
        <table class="data-table table-cards" id="patientsTable">
            <thead>
                <tr>
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Age</th>
                    <th>Specialty</th>
                    <th>Assigned Doctor</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patients as $p)
                @php
                    $dept = $p->latestVisit->doctor->specialty ?? 'N/A';
                    $age = $p->patient_birthdate ? \Carbon\Carbon::parse($p->patient_birthdate)->age : 'N/A';
                    $latestNotes = $p->latestVisit->notes ?? '—';
                @endphp
                <tr data-department="{{ strtolower($dept) }}">
                    <td data-label="Patient ID">{{ $p->patient_id }}</td>
                    <td data-label="Patient Name">{{ $p->full_name }}</td>
                    <td data-label="Age">{{ $age }}</td>
                    <td data-label="Specialty">{{ $dept }}</td>
                    <td data-label="Assigned Doctor">{{ $p->latestVisit->doctor->doctor_name ?? 'N/A' }}</td>
                    <td data-label="Notes" class="patient-notes-cell">{{ $latestNotes }}</td>
                    <td data-label="" class="table-cards-actions" style="display: flex; gap: 0.5rem;">
                        <button type="button" class="btn-sm btn-view-patient" onclick="openViewPatientModal('{{ $p->patient_id }}')">View</button>
                        <button type="button" class="btn-sm btn-edit-patient" onclick="openEditPatientModal('{{ $p->patient_id }}')">Edit</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p id="noResultsMsg" style="display: none; padding: 2rem; text-align: center; background: white; border-radius: 20px;">No matching patients found.</p>
    @else
        <p style="padding: 2rem; text-align: center; background: white; border-radius: 20px;">No patients found.</p>
    @endif
</div>

{{-- ===== View Patient Modal ===== --}}
<div id="viewPatientModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="viewPatientModalTitle">Patient Details</h3>
            <button type="button" class="modal-close" onclick="closeViewPatientModal()" aria-label="Close">&times;</button>
        </div>

        <div id="patientCaptureArea" style="background: white; padding: 0.25rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="font-weight: 600; color: var(--text-dark);">Patient ID</label>
                    <p id="viewPatientId" style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;"></p>
                </div>
                <div>
                    <label style="font-weight: 600; color: var(--text-dark);">Patient Name</label>
                    <p id="viewPatientName" style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;"></p>
                </div>
                <div>
                    <label style="font-weight: 600; color: var(--text-dark);">Department (latest visit)</label>
                    <p id="viewPatientDept" style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;"></p>
                </div>
                <div>
                    <label style="font-weight: 600; color: var(--text-dark);">Assigned Doctor (latest visit)</label>
                    <p id="viewPatientDoctor" style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;"></p>
                </div>
                <div>
                    <label style="font-weight: 600; color: var(--text-dark);">Birthday</label>
                    <p id="viewPatientBirthdate" style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;"></p>
                </div>
                <div>
                    <label style="font-weight: 600; color: var(--text-dark);">Contact</label>
                    <p id="viewPatientContact" style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;"></p>
                </div>
                <div style="grid-column: span 2;">
                    <label style="font-weight: 600; color: var(--text-dark);">Email</label>
                    <p id="viewPatientEmail" style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px;"></p>
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <label style="font-weight: 600; color: var(--text-dark);">Notes / Diagnosis</label>
                <div id="viewPatientVisits"></div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; align-items:center; gap:0.75rem; margin-top: 1.5rem;">
            <div style="position: relative;">
                <button type="button" class="btn-sm btn-primary" id="downloadPatientBtn" style="padding:0.8rem 1.5rem; display:inline-flex; align-items:center; gap:0.5rem;" onclick="toggleDownloadMenu(event)">
                    <i class="bi bi-download"></i> Download <i class="bi bi-chevron-down" style="font-size:0.7rem;"></i>
                </button>
                <div id="downloadMenu" class="download-menu">
                    <button type="button" class="download-menu-item" onclick="downloadPatientAsImage()">
                        <i class="bi bi-file-earmark-image"></i> Image (PNG)
                    </button>
                    <button type="button" class="download-menu-item" onclick="downloadPatientAsPDF()">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </button>
                </div>
            </div>
            <button type="button" class="btn-sm btn-secondary" style="padding:0.8rem 2rem;" onclick="closeViewPatientModal()">Close</button>
        </div>
    </div>
</div>

{{-- ===== Hidden Patient Record Template (used only for PNG/PDF download) ===== --}}
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
            Generated on <span id="pdfGeneratedDate"></span> &middot; PolyClinic Staff Records
        </div>
    </div>
</div>

{{-- ===== Edit Patient Modal ===== --}}
<div id="editPatientModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="editPatientModalTitle">Edit Patient</h3>
            <button type="button" class="modal-close" onclick="closeEditPatientModal()" aria-label="Close">&times;</button>
        </div>

        <form method="POST" id="editPatientForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="visit_id" id="editPatientVisitId">

            <div class="form-group">
                <label>Patient ID</label>
                <input type="text" id="editPatientIdDisplay" disabled style="background: #f5f5f5; color: #999;">
            </div>

            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="patient_fname" id="editPatientFname" required>
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="patient_lname" id="editPatientLname" required>
            </div>

            <div class="form-group">
                <label>Birthday</label>
                <input type="date" name="patient_birthdate" id="editPatientBirthdate" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="patient_email" id="editPatientEmail" required>
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="patient_contact" id="editPatientContact" required>
            </div>

            <div class="form-group">
                <label>Notes / Diagnosis</label>
                <textarea name="notes" id="editPatientNotes" rows="4" placeholder="Enter the diagnosis or notes for this patient's latest visit..." style="width: 100%; padding: 0.7rem; border: 1px solid var(--light-gray); border-radius: 8px; font-family: 'Poppins', sans-serif; resize: vertical;"></textarea>
                <small id="editPatientNotesHint" style="color: #999;"></small>
            </div>

            <div style="display:flex; gap:0.5rem; justify-content:flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn-sm btn-secondary" style="padding:0.8rem 2rem;" onclick="closeEditPatientModal()">Cancel</button>
                <button type="submit" class="btn-sm btn-success" style="padding:0.8rem 2rem;">Update Patient</button>
            </div>
        </form>
    </div>
</div>

@php
    $patientEditMap = [];
    $patientViewMap = [];

    foreach ($patients as $p) {
        $latestVisit = $p->latestVisit;

        $patientEditMap[$p->patient_id] = [
            'patientId'  => $p->patient_id,
            'fname'      => $p->patient_fname,
            'lname'      => $p->patient_lname,
            'birthdate'  => \Carbon\Carbon::parse($p->patient_birthdate)->format('Y-m-d'),
            'email'      => $p->patient_email,
            'contact'    => $p->patient_contact,
            'hasVisit'   => (bool) $latestVisit,
            'visitId'    => $latestVisit->visit_id ?? null,
            'notes'      => $latestVisit->notes ?? '',
            'visitDate'  => $latestVisit ? \Carbon\Carbon::parse($latestVisit->visit_date)->format('M d, Y') : null,
            'updateUrl'  => route('staff.patients.update', $p->patient_id),
        ];

        $patientViewMap[$p->patient_id] = [
            'patientId'  => $p->patient_id,
            'fullName'   => $p->full_name,
            'dept'       => $latestVisit->doctor->specialty ?? 'N/A',
            'doctorName' => $latestVisit->doctor->doctor_name ?? 'N/A',
            'birthdate'  => \Carbon\Carbon::parse($p->patient_birthdate)->format('M d, Y'),
            'contact'    => $p->patient_contact,
            'email'      => $p->patient_email,
            'visits'     => $p->visits->map(function ($visit) {
                return [
                    'date'       => \Carbon\Carbon::parse($visit->visit_date)->format('M d, Y'),
                    'serviceType'=> $visit->service_type,
                    'doctorName' => $visit->doctor->doctor_name ?? 'N/A',
                    'notes'      => $visit->notes ?: 'No notes recorded for this visit.',
                ];
            })->values(),
        ];
    }
@endphp

<script>
    const patientEditMap = @json($patientEditMap);
    const patientViewMap = @json($patientViewMap);

    function filterPatientsTable() {
        const searchInput = document.getElementById('patientSearch');
        const departmentSelect = document.getElementById('departmentFilter');
        const searchFilter = searchInput.value.trim().toLowerCase();
        const departmentFilter = departmentSelect.value;

        const table = document.getElementById('patientsTable');
        if (!table) return;

        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        let visibleCount = 0;

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const rowText = row.textContent.toLowerCase();
            const rowDepartment = row.getAttribute('data-department') || '';

            const matchesSearch = rowText.includes(searchFilter);
            const matchesDepartment = departmentFilter === '' || rowDepartment === departmentFilter;

            const isMatch = matchesSearch && matchesDepartment;
            row.style.display = isMatch ? '' : 'none';
            if (isMatch) visibleCount++;
        }

        const noResultsMsg = document.getElementById('noResultsMsg');
        if (noResultsMsg) {
            noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    // ===== View Patient Modal =====

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    let currentViewPatientId = null;

    function openViewPatientModal(patientId) {
        const data = patientViewMap[patientId];
        if (!data) return;

        currentViewPatientId = patientId;

        document.getElementById('viewPatientId').textContent = data.patientId;
        document.getElementById('viewPatientName').textContent = data.fullName;
        document.getElementById('viewPatientDept').textContent = data.dept;
        document.getElementById('viewPatientDoctor').textContent = data.doctorName;
        document.getElementById('viewPatientBirthdate').textContent = data.birthdate;
        document.getElementById('viewPatientContact').textContent = data.contact;
        document.getElementById('viewPatientEmail').textContent = data.email;

        const visitsEl = document.getElementById('viewPatientVisits');
        if (data.visits.length === 0) {
            visitsEl.innerHTML = '<p style="padding: 0.5rem; background: var(--light-gray); border-radius: 8px; margin-top: 0.5rem;">No notes recorded.</p>';
        } else {
            visitsEl.innerHTML = data.visits.map(function (v) {
                return '<div style="padding: 0.75rem; background: var(--light-gray); border-radius: 8px; margin-top: 0.5rem;">' +
                    '<div style="font-size: 0.8rem; color: #7f8c8d; margin-bottom: 0.3rem;">' +
                        escapeHtml(v.date) + ' — ' + escapeHtml(v.serviceType) + ' with ' + escapeHtml(v.doctorName) +
                    '</div>' +
                    '<div style="color: var(--text-dark);">' + escapeHtml(v.notes) + '</div>' +
                '</div>';
            }).join('');
        }

        document.getElementById('viewPatientModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeViewPatientModal() {
        document.getElementById('viewPatientModal').classList.remove('show');
        document.body.style.overflow = '';
        const menu = document.getElementById('downloadMenu');
        if (menu) menu.classList.remove('show');
    }

    document.getElementById('viewPatientModal').addEventListener('click', function (e) {
        if (e.target === this) closeViewPatientModal();
    });

    // ===== Download Patient Details (PNG / PDF) =====

    function toggleDownloadMenu(e) {
        e.stopPropagation();
        document.getElementById('downloadMenu').classList.toggle('show');
    }

    document.addEventListener('click', function (e) {
        const menu = document.getElementById('downloadMenu');
        const btn = document.getElementById('downloadPatientBtn');
        if (!menu || !btn) return;
        if (!menu.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            menu.classList.remove('show');
        }
    });

    function buildPatientPdfTemplate(patientId) {
        const data = patientViewMap[patientId];
        if (!data) return null;

        document.getElementById('pdfPatientId').textContent = data.patientId;
        document.getElementById('pdfPatientName').textContent = data.fullName;
        document.getElementById('pdfPatientBirthdate').textContent = data.birthdate;
        document.getElementById('pdfPatientContact').textContent = data.contact;
        document.getElementById('pdfPatientEmail').textContent = data.email;
        document.getElementById('pdfPatientDept').textContent = data.dept;
        document.getElementById('pdfPatientDoctor').textContent = data.doctorName;
        document.getElementById('pdfGeneratedDate').textContent = new Date().toLocaleString('en-US', {
            year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit'
        });

        const tbody = document.getElementById('pdfVisitTableBody');
        if (data.visits.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="padding:0.75rem 0.6rem; text-align:center; color:#999;">No visit records.</td></tr>';
        } else {
            tbody.innerHTML = data.visits.map(function (v) {
                return '<tr style="border-bottom:1px solid #eee;">' +
                    '<td style="padding:0.55rem 0.6rem; vertical-align:top; white-space:nowrap;">' + escapeHtml(v.date) + '</td>' +
                    '<td style="padding:0.55rem 0.6rem; vertical-align:top; white-space:nowrap;">' + escapeHtml(v.serviceType) + '</td>' +
                    '<td style="padding:0.55rem 0.6rem; vertical-align:top; white-space:nowrap;">' + escapeHtml(v.doctorName) + '</td>' +
                    '<td style="padding:0.55rem 0.6rem; vertical-align:top;">' + escapeHtml(v.notes) + '</td>' +
                    '</tr>';
            }).join('');
        }

        return document.getElementById('patientPdfTemplate');
    }

    function downloadPatientAsImage() {
        document.getElementById('downloadMenu').classList.remove('show');

        if (typeof html2canvas === 'undefined') {
            alert('Download tool is still loading. Please try again in a moment.');
            return;
        }

        const el = buildPatientPdfTemplate(currentViewPatientId);
        if (!el) return;

        html2canvas(el, { backgroundColor: '#ffffff', scale: 2 }).then(function (canvas) {
            const link = document.createElement('a');
            link.download = 'patient-' + (currentViewPatientId ?? 'record') + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        }).catch(function (err) {
            console.error('PNG download failed:', err);
            alert('Failed to generate image. Please try again.');
        });
    }

    function downloadPatientAsPDF() {
        document.getElementById('downloadMenu').classList.remove('show');

        if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
            alert('Download tool is still loading. Please try again in a moment.');
            return;
        }

        const el = buildPatientPdfTemplate(currentViewPatientId);
        if (!el) return;

        html2canvas(el, { backgroundColor: '#ffffff', scale: 2 }).then(function (canvas) {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'pt', 'a4');

            const pageWidth = pdf.internal.pageSize.getWidth();
            const margin = 30;
            const imgWidth = pageWidth - margin * 2;
            const imgHeight = canvas.height * (imgWidth / canvas.width);

            const imgData = canvas.toDataURL('image/png');
            pdf.addImage(imgData, 'PNG', margin, margin, imgWidth, imgHeight);

            pdf.save('patient-' + (currentViewPatientId ?? 'record') + '.pdf');
        }).catch(function (err) {
            console.error('PDF download failed:', err);
            alert('Failed to generate PDF. Please try again.');
        });
    }

    // ===== Edit Patient Modal =====

    function openEditPatientModal(patientId) {
        const data = patientEditMap[patientId];
        if (!data) return;

        document.getElementById('editPatientModalTitle').textContent = 'Edit Patient - ' + data.fname + ' ' + data.lname;
        document.getElementById('editPatientForm').action = data.updateUrl;

        document.getElementById('editPatientIdDisplay').value = data.patientId;
        document.getElementById('editPatientFname').value = data.fname;
        document.getElementById('editPatientLname').value = data.lname;
        document.getElementById('editPatientBirthdate').value = data.birthdate;
        document.getElementById('editPatientEmail').value = data.email;
        document.getElementById('editPatientContact').value = data.contact;

        const notesField = document.getElementById('editPatientNotes');
        const notesHint = document.getElementById('editPatientNotesHint');
        const visitIdInput = document.getElementById('editPatientVisitId');

        if (data.hasVisit) {
            notesField.disabled = false;
            notesField.value = data.notes;
            visitIdInput.value = data.visitId;
            visitIdInput.disabled = false;
            notesHint.textContent = 'This updates the notes for the latest visit (' + data.visitDate + ').';
        } else {
            notesField.disabled = true;
            notesField.value = '';
            visitIdInput.value = '';
            visitIdInput.disabled = true;
            notesHint.textContent = 'No visit on record yet — notes can be added once this patient has a visit.';
        }

        document.getElementById('editPatientModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeEditPatientModal() {
        document.getElementById('editPatientModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    
    document.getElementById('editPatientModal').addEventListener('click', function (e) {
        if (e.target === this) closeEditPatientModal();
    });

    
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeViewPatientModal();
            closeEditPatientModal();
        }
    });
</script>

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
@endpush
@endsection