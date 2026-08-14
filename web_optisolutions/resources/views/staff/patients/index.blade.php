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
                        <a href="{{ route('staff.patients.show', $p->patient_id) }}" class="btn-sm btn-primary">View</a>
                        <a href="{{ route('staff.patients.edit', $p->patient_id) }}" class="btn-sm btn-warning">Edit</a>
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

<script>
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
</script>
@endsection