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
        <table class="data-table" id="patientsTable">
            <thead>
                <tr>
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Department</th>
                    <th>Assigned Doctor</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patients as $p)
                @php
                    $dept = $p->latestVisit->doctor->specialty ?? 'N/A';
                @endphp
                <tr data-department="{{ strtolower($dept) }}">
                    <td>{{ $p->patient_id }}</td>
                    <td>{{ $p->full_name }}</td>
                    <td>{{ $dept }}</td>
                    <td>{{ $p->latestVisit->doctor->doctor_name ?? 'N/A' }}</td>
                    <td style="display: flex; gap: 0.5rem;">
                        <button type="button" class="btn-sm btn-primary" onclick="openViewPatientModal('{{ $p->patient_id }}')">View</button>
                        <button type="button" class="btn-sm btn-warning" onclick="openEditPatientModal('{{ $p->patient_id }}')">Edit</button>
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

        <div style="display:flex; justify-content:flex-end; margin-top: 1.5rem;">
            <button type="button" class="btn-sm btn-secondary" style="padding:0.8rem 2rem;" onclick="closeViewPatientModal()">Close</button>
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

    function openViewPatientModal(patientId) {
        const data = patientViewMap[patientId];
        if (!data) return;

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
    }

    document.getElementById('viewPatientModal').addEventListener('click', function (e) {
        if (e.target === this) closeViewPatientModal();
    });

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
@endsection