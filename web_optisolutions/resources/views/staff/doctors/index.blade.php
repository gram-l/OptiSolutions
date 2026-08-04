@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <h3 style="margin: 0;">Doctors</h3>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
            <div style="position: relative; min-width: 220px;">
                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input
                    type="text"
                    id="doctorSearch"
                    placeholder="Search name or specialty..."
                    style="width: 100%; padding: 0.55rem 0.75rem 0.55rem 2.25rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; outline: none;"
                    onkeyup="filterDoctorsTable()"
                >
            </div>

            <select
                id="departmentFilter"
                style="padding: 0.55rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; outline: none; background: white; min-width: 180px;"
                onchange="filterDoctorsTable()"
            >
                <option value="">All Departments</option>
                @php
                    $specialties = $doctors->pluck('specialty')->unique()->sort()->values();
                @endphp
                @foreach($specialties as $specialty)
                    <option value="{{ strtolower($specialty) }}">{{ $specialty }}</option>
                @endforeach
            </select>

            <div style="position: relative;">
                <button
                    type="button"
                    id="downloadBtn"
                    onclick="toggleExportMenu()"
                    class="btn-primary"
                    style="display: flex; align-items: center; gap: 6px; padding: 0.55rem 1rem; border: none; border-radius: 8px; font-size: 0.9rem; cursor: pointer;"
                >
                    <i class="bi bi-download"></i> Download
                </button>

                <div
                    id="exportMenu"
                    class="dropdown-menu"
                    style="right: 0; left: auto; top: calc(100% + 6px); width: 200px; padding: 0.5rem 0;"
                >
                    <a href="{{ route('staff.doctors.export', 'csv') }}" class="dropdown-item" style="text-decoration: none;">
                        <i class="bi bi-filetype-csv"></i> Export as CSV
                    </a>
                    <a href="{{ route('staff.doctors.export', 'xlsx') }}" class="dropdown-item" style="text-decoration: none;">
                        <i class="bi bi-filetype-xlsx"></i> Export as Excel
                    </a>
                    <a href="{{ route('staff.doctors.export', 'pdf') }}" target="_blank" class="dropdown-item" style="text-decoration: none;">
                        <i class="bi bi-filetype-pdf"></i> Export as PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($doctors->count() > 0)
        <table class="data-table" id="doctorsTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Specialty</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($doctors as $doc)
                <tr data-specialty="{{ strtolower($doc->specialty) }}">
                    <td>{{ $doc->doctor_name }}</td>
                    <td>{{ $doc->specialty }}</td>
                    <td>
                        @forelse($doc->schedules as $sched)
                            {{ $sched->day }} {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}<br>
                        @empty
                            N/A
                        @endforelse
                    </td>
                    <td>
                        <span class="status-badge {{ $doc->available ? 'status-confirmed' : 'status-pending' }}">
                            {{ $doc->available ? 'Available' : 'Unavailable' }}
                        </span>
                    </td>
                    <td>
                        @if($doc->available)
                            {{-- Available pa yung doctor, pwede i-edit --}}
                            <a href="{{ route('staff.doctors.edit', $doc->doctor_id) }}" class="btn-sm btn-secondary">Edit</a>
                        @else
                            {{-- Naka-set na 'Unavailable' ng admin (inactive), hindi na dapat ma-edit ni staff --}}
                            <button type="button" class="btn-sm btn-secondary" disabled
                                style="opacity: 0.5; cursor: not-allowed;"
                                title="Hindi maaaring i-edit — naka-set as Unavailable ng admin.">
                                Edit
                            </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p id="noResultsMsg" style="display: none; padding: 2rem; text-align: center; background: white; border-radius: 20px;">No matching doctors found.</p>
    @else
        <p style="padding: 2rem; text-align: center; background: white; border-radius: 20px;">No doctors found.</p>
    @endif
</div>

<script>
    function toggleExportMenu() {
        document.getElementById('exportMenu').classList.toggle('show');
    }

    document.addEventListener('click', function (e) {
        const menu = document.getElementById('exportMenu');
        const btn = document.getElementById('downloadBtn');
        if (menu && !menu.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            menu.classList.remove('show');
        }
    });

    function filterDoctorsTable() {
        const searchInput = document.getElementById('doctorSearch');
        const departmentSelect = document.getElementById('departmentFilter');
        const searchFilter = searchInput.value.trim().toLowerCase();
        const departmentFilter = departmentSelect.value;

        const table = document.getElementById('doctorsTable');
        if (!table) return;

        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        let visibleCount = 0;

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const rowText = row.textContent.toLowerCase();
            const rowSpecialty = row.getAttribute('data-specialty') || '';

            const matchesSearch = rowText.includes(searchFilter);
            const matchesDepartment = departmentFilter === '' || rowSpecialty === departmentFilter;

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