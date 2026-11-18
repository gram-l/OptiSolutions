@extends('staff.layouts.app')

@section('content')
@php
    $dayOrderForCompact = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

    // Same grouping the admin Manage Doctors cards use:
    // consecutive days with identical hours collapse to "Mon-Sat 10:00 AM - 12:00 PM".
    if (!function_exists('formatScheduleAdminStyle')) {
        function formatScheduleAdminStyle($schedules): string {
            if (!$schedules || $schedules->isEmpty()) return 'Schedule not set';
            $order = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            $sorted = $schedules->sortBy(fn ($s) => array_search($s->day, $order))->values();
            $groups = [];
            foreach ($sorted as $s) {
                $key = ($s->start_time ?? '') . '-' . ($s->end_time ?? '');
                $i = count($groups) - 1;
                if ($i >= 0 && $groups[$i]['key'] === $key
                    && array_search($s->day, $order) === array_search(end($groups[$i]['days']), $order) + 1) {
                    $groups[$i]['days'][] = $s->day;
                    continue;
                }
                $groups[] = ['key' => $key, 'days' => [$s->day], 'start' => $s->start_time, 'end' => $s->end_time];
            }
            $parts = [];
            foreach ($groups as $g) {
                $d = $g['days'];
                $label = count($d) > 1 ? substr($d[0], 0, 3) . '-' . substr(end($d), 0, 3) : substr($d[0], 0, 3);
                if ($g['start'] && $g['end']) {
                    $label .= ' ' . \Carbon\Carbon::parse($g['start'])->format('g:i A') . ' - ' . \Carbon\Carbon::parse($g['end'])->format('g:i A');
                }
                $parts[] = $label;
            }
            return implode(', ', $parts);
        }
    }
@endphp

<style>
    /* Admin "Manage Doctors" card look, scoped so it doesn't affect other staff pages. */
    .docs-admin { --light-gray: #ECF0F1; --primary-dark: #062744; --shadow: rgba(0,0,0,0.1); }
    .docs-admin .dc-page-header { margin-bottom: 1.25rem; padding-left: 0.35rem; }
    .docs-admin .dc-page-header h2 { font-size: 1.75rem; color: var(--primary-dark); display: flex; align-items: center; gap: 0.65rem; margin: 0; }
    .docs-admin .dc-page-header h2 span { display: inline-flex; align-items: center; justify-content: center; width: 2.15rem; height: 2.15rem; flex-shrink: 0; border-radius: 10px; background: rgba(14,98,170,0.1); color: var(--primary-main); font-size: 1.05rem; }
    .docs-admin .dc-page-header p { color: #7f8c8d; margin: 0.4rem 0 0; padding-left: 0.35rem; }
    .docs-admin .action-bar { background: #fff; padding: 1rem 1.5rem; border-radius: 15px; margin-bottom: 1.5rem !important; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; box-shadow: 0 2px 10px var(--shadow); }
    .docs-admin .action-bar .filters { display: flex; gap: 1rem; flex-wrap: wrap; }
    .docs-admin .search-box { padding: 0.6rem 1rem; border: 1px solid var(--light-gray); border-radius: 8px; width: 250px; outline: none; background: #fff; font-size: 0.9rem; }
    .docs-admin .search-box:focus { border-color: var(--primary-main); box-shadow: 0 0 0 2px rgba(14,98,170,0.2); }
    .docs-admin .filter-select { padding: 0.6rem 1rem; border: 1px solid var(--light-gray); border-radius: 8px; background: #fff; cursor: pointer; font-size: 0.9rem; }
    .docs-admin .dl-wrap { position: relative; }
    .docs-admin .dl-btn { padding: 0.7rem 1.5rem; background: var(--primary-main); color: #fff; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; white-space: nowrap; }
    .docs-admin .dl-btn:hover { background: #0b4f8a; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(14,98,170,0.3); }
    .docs-admin #exportMenu { right: 0; left: auto; top: calc(100% + 6px); width: 200px; padding: 0.5rem 0; }

    .docs-admin .doctors-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
    .docs-admin .doctor-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 15px var(--shadow); border: 1px solid var(--light-gray); overflow: hidden; display: flex; flex-direction: column; transition: transform .25s ease, box-shadow .25s ease; }
    .docs-admin .doctor-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
    .docs-admin .doctor-card.inactive { opacity: 0.7; }
    .docs-admin .card-header { padding: 1.5rem 1.5rem 1.1rem; text-align: center; border-bottom: 1px solid var(--light-gray); }
    .docs-admin .doctor-avatar { width: 76px; height: 76px; margin: 0 auto 0.75rem; border-radius: 50%; background: var(--primary-main); color: #fff; font-size: 1.9rem; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .docs-admin .doctor-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .docs-admin .doctor-name { font-size: 1.05rem; font-weight: 700; color: var(--primary-dark); margin-bottom: 0.15rem; }
    .docs-admin .doctor-specialty { font-size: 0.82rem; color: #7f8c8d; margin-bottom: 0.7rem; }
    .docs-admin .status-badge { display: inline-block; padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.03em; }
    .docs-admin .status-active { background: #e8f8f0; color: #0E62AA; }
    .docs-admin .status-inactive { background: #fdeaea; color: #e74c3c; }
    .docs-admin .card-body { padding: 1.25rem 1.5rem 1.5rem; display: flex; flex-direction: column; gap: 1rem; flex: 1; }
    .docs-admin .doctor-description { font-size: 0.85rem; color: #5a6474; line-height: 1.5; }
    .docs-admin .schedule-info { background: #f4f6fb; border-radius: 10px; padding: 0.8rem 1rem; }
    .docs-admin .schedule-title { display: flex; align-items: center; gap: 0.4rem; font-weight: 600; font-size: 0.8rem; color: var(--primary-dark); margin-bottom: 0.35rem; }
    .docs-admin .schedule-text { font-size: 0.8rem; color: #5a6474; line-height: 1.45; }
    .docs-admin .card-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: auto; padding-top: 0.25rem; }
    .docs-admin .btn-icon { flex: 1 1 auto; display: inline-flex; align-items: center; justify-content: center; gap: 0.3rem; padding: 0.35rem 0.85rem; border: none; border-radius: 20px; cursor: pointer; font-size: 0.75rem; font-weight: 600; transition: all .2s ease; background: #e8f0fe; color: var(--primary-main); }
    .docs-admin .btn-icon:hover { background: #d5e5fc; }
    .docs-admin .btn-icon:disabled { background: var(--light-gray); color: #a3adb8; cursor: not-allowed; }
    .docs-admin .empty-state { text-align: center; padding: 3rem; color: #95a5a6; grid-column: 1 / -1; }
    body.dark-mode .docs-admin .dc-page-header h2, body.dark-mode .docs-admin .doctor-name, body.dark-mode .docs-admin .schedule-title { color: #e7edf5; }
    body.dark-mode .docs-admin :is(.action-bar, .doctor-card, .search-box, .filter-select) { background: #1a2232; border-color: #344155; color: #e7edf5; }
    body.dark-mode .docs-admin .card-header { border-color: #344155; }
    body.dark-mode .docs-admin .schedule-info { background: #293548; }
    body.dark-mode .docs-admin :is(.doctor-description, .schedule-text, .doctor-specialty) { color: #afbdce; }
    @media (max-width: 768px) {
        .docs-admin .dc-page-header { padding-left: 0.15rem; }
        .docs-admin .dc-page-header h2 { font-size: 1.4rem; gap: 0.55rem; }
        .docs-admin .dc-page-header h2 span { width: 1.9rem; height: 1.9rem; font-size: 0.9rem; border-radius: 8px; }
        .docs-admin .action-bar, .docs-admin .action-bar .filters { flex-direction: column; align-items: stretch; }
        .docs-admin .search-box { width: 100%; }
        .docs-admin .dl-btn { justify-content: center; width: 100%; }
        .docs-admin .doctors-grid { grid-template-columns: 1fr; gap: 1rem; }
    }
</style>

<div class="container">
<div class="docs-admin">
    <div class="dc-page-header">
        <h2><span><i class="fa-solid fa-user-doctor"></i></span> Manage Doctors</h2>
        <p>View doctor profiles and edit their schedules</p>
    </div>

    <div class="action-bar">
        <div class="filters">
            <input type="text" class="search-box" id="doctorSearch" placeholder="Search by name or specialty..." oninput="filterDoctorsTable()">
            <select class="filter-select" id="departmentFilter" onchange="filterDoctorsTable()">
                <option value="">All Departments</option>
                @foreach(\App\Support\DoctorSpecialties::catalog() as $specialty)
                    <option value="{{ strtolower($specialty) }}">{{ $specialty }}</option>
                @endforeach
            </select>
            <select class="filter-select" id="statusFilter" onchange="filterDoctorsTable()">
                <option value="">All Doctors</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
            </select>
        </div>

        <div class="dl-wrap">
            <button type="button" id="downloadBtn" onclick="toggleExportMenu()" class="dl-btn">
                <i class="bi bi-download"></i> Download
            </button>
            <div id="exportMenu" class="dropdown-menu">
                <a href="{{ route('staff.doctors.export', 'csv') }}" class="dropdown-item" style="text-decoration: none;"><i class="bi bi-filetype-csv"></i> Export as CSV</a>
                <a href="{{ route('staff.doctors.export', 'xlsx') }}" class="dropdown-item" style="text-decoration: none;"><i class="bi bi-filetype-xlsx"></i> Export as Excel</a>
                <a href="{{ route('staff.doctors.export', 'pdf') }}" target="_blank" class="dropdown-item" style="text-decoration: none;"><i class="bi bi-filetype-pdf"></i> Export as PDF</a>
            </div>
        </div>
    </div>

    <div class="doctors-grid" id="doctorsGrid">
        @forelse($doctors as $doc)
            @php
                $isActive = (bool) $doc->available;
                $desc = trim((string) $doc->description);
                $desc = $desc === '' ? 'No description provided.' : \Illuminate\Support\Str::limit($desc, 100);
            @endphp
            <div class="doctor-card {{ $isActive ? '' : 'inactive' }}"
                 data-name="{{ strtolower($doc->doctor_name) }}"
                 data-specialty="{{ strtolower($doc->specialty) }}"
                 data-status="{{ $isActive ? 'active' : 'inactive' }}">
                <div class="card-header">
                    <div class="doctor-avatar">
                        @if($doc->profile_image)
                            <img src="{{ asset('storage/' . $doc->profile_image) }}" alt="{{ $doc->doctor_name }}">
                        @else
                            <i class="fa-solid fa-user-doctor"></i>
                        @endif
                    </div>
                    <div class="doctor-name">{{ $doc->doctor_name }}</div>
                    <div class="doctor-specialty">{{ $doc->specialty }}</div>
                    <span class="status-badge {{ $isActive ? 'status-active' : 'status-inactive' }}">
                        {{ $isActive ? '● ACTIVE' : '○ INACTIVE' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="doctor-description">{{ $desc }}</div>
                    <div class="schedule-info">
                        <div class="schedule-title"><span><i class="fa-solid fa-clock"></i></span> Schedule</div>
                        <div class="schedule-text">{{ formatScheduleAdminStyle($doc->schedules) }}</div>
                    </div>
                    <div class="card-actions">
                        @if($isActive)
                            <button type="button" class="btn-icon" onclick="openEditModal({{ $doc->doctor_id }})">
                                <i class="fa-solid fa-edit"></i> Edit
                            </button>
                        @else
                            <button type="button" class="btn-icon" disabled title="Set as inactive by the admin, so its schedule can't be edited here.">
                                <i class="fa-solid fa-edit"></i> Edit
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state">No doctors found.</div>
        @endforelse
        <div class="empty-state" id="noResultsMsg" style="display: none;">No matching doctors found.</div>
    </div>
</div>
</div>

{{-- ===== Edit Schedule Modal (isa lang, ginagamit paulit-ulit per doctor) ===== --}}
<div id="editScheduleModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="editModalTitle">Edit Schedule</h3>
            <button type="button" class="modal-close" onclick="closeEditModal()" aria-label="Close">&times;</button>
        </div>

        <form method="POST" id="editScheduleForm">
            @csrf
            @method('PUT')

            <div class="day-list" id="editDayList">
                {{-- dynamic, pinopopulate ng JS pag binuksan --}}
            </div>

            <div style="display:flex; gap:0.5rem; justify-content:flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn-sm btn-secondary" style="padding:0.8rem 2rem;" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-sm btn-primary" style="padding:0.8rem 2rem;">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Toast — shows the "Doctor schedule updated successfully!" flash from
     DoctorController@update, styled to match the admin's toast --}}
@if(session('success'))
    <div class="toast toast-success" id="successToast">
        <span>{{ session('success') }}</span>
    </div>
@endif

{{-- "Save changes?" confirmation, shown before the edit form actually submits --}}
<div class="confirm-modal-overlay" id="confirmSaveOverlay">
    <div class="confirm-modal-box">
        <h3>Save changes?</h3>
        <p id="confirmSaveMessage">This will update the doctor's schedule.</p>
        <div class="confirm-modal-buttons">
            <button type="button" class="confirm-modal-btn-cancel" onclick="cancelSaveSchedule()">Cancel</button>
            <button type="button" class="confirm-modal-btn-confirm" onclick="confirmSaveSchedule()">Confirm</button>
        </div>
    </div>
</div>

@php
    $dayOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    $doctorScheduleMap = [];

    foreach ($doctors as $doc) {
        $byDay = $doc->schedules->groupBy('day');
        $daysData = [];

        foreach ($dayOrder as $day) {
            $daysData[$day] = $byDay->get($day, collect())->map(function ($s) {
                return [
                    'start_time' => \Carbon\Carbon::parse($s->start_time)->format('H:i'),
                    'end_time' => \Carbon\Carbon::parse($s->end_time)->format('H:i'),
                ];
            })->values();
        }

        $doctorScheduleMap[$doc->doctor_id] = [
            'name' => $doc->doctor_name,
            'days' => $daysData,
            'updateUrl' => route('staff.doctors.update', $doc->doctor_id),
        ];
    }
@endphp

<script>
    const doctorScheduleMap = @json($doctorScheduleMap);
    const dayOrder = @json($dayOrder);

    // --- Toast (auto-show + auto-hide) ---
    document.addEventListener('DOMContentLoaded', function () {
        const toast = document.getElementById('successToast');
        if (toast) {
            requestAnimationFrame(function () {
                toast.classList.add('show');
            });
            setTimeout(function () {
                toast.classList.remove('show');
            }, 3000);
        }
    });

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
        const search = document.getElementById('doctorSearch').value.trim().toLowerCase();
        const department = document.getElementById('departmentFilter').value;
        const status = document.getElementById('statusFilter').value;
        let visible = 0;

        document.querySelectorAll('#doctorsGrid .doctor-card').forEach(function (card) {
            const specialty = card.dataset.specialty || '';
            const matchesSearch = (card.dataset.name + ' ' + specialty).includes(search);
            const matchesDepartment = department === '' || specialty.split('/').map(function (v) { return v.trim(); }).includes(department);
            const matchesStatus = status === '' || card.dataset.status === status;
            const show = matchesSearch && matchesDepartment && matchesStatus;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        const msg = document.getElementById('noResultsMsg');
        if (msg) msg.style.display = visible === 0 && document.querySelector('#doctorsGrid .doctor-card') ? 'block' : 'none';
    }

    // ===== Edit Schedule Modal =====

    function sessionRowHtml(day, index, start, end) {
        return '<div class="session-row">' +
            '<input type="time" name="schedules[' + day + '][' + index + '][start_time]" value="' + (start || '') + '" required>' +
            '<span class="session-sep">-</span>' +
            '<input type="time" name="schedules[' + day + '][' + index + '][end_time]" value="' + (end || '') + '" required>' +
            '<button type="button" class="btn-remove-session" onclick="removeSession(this)" aria-label="Remove session"><i class="bi bi-trash"></i></button>' +
            '</div>';
    }

    function buildDayBlock(day, sessions) {
        const hasSessions = sessions.length > 0;
        const sessionsHtml = sessions.map(function (s, i) {
            return sessionRowHtml(day, i, s.start_time, s.end_time);
        }).join('');

        return '<div class="day-block">' +
            '<label class="day-toggle">' +
                '<input type="checkbox" class="day-check" data-day="' + day + '" ' + (hasSessions ? 'checked' : '') + ' onchange="toggleDay(this)">' +
                '<span>' + day + '</span>' +
            '</label>' +
            '<div class="day-sessions" id="sessions-' + day + '" style="' + (hasSessions ? '' : 'display:none;') + '">' +
                sessionsHtml +
                '<button type="button" class="btn-add-session" onclick="addSession(\'' + day + '\')">+ Add session</button>' +
            '</div>' +
        '</div>';
    }

    function openEditModal(doctorId) {
        const data = doctorScheduleMap[doctorId];
        if (!data) return;

        document.getElementById('editModalTitle').textContent = 'Edit Schedule - ' + data.name;
        document.getElementById('editScheduleForm').action = data.updateUrl;

        const dayListEl = document.getElementById('editDayList');
        dayListEl.innerHTML = dayOrder.map(function (day) {
            return buildDayBlock(day, data.days[day] || []);
        }).join('');

        document.getElementById('editScheduleModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        document.getElementById('editScheduleModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    // Click sa labas ng modal box = close
    document.getElementById('editScheduleModal').addEventListener('click', function (e) {
        if (e.target === this) closeEditModal();
    });

    // Esc key = close
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeEditModal();
    });

    function toggleDay(checkbox) {
        const day = checkbox.dataset.day;
        const panel = document.getElementById('sessions-' + day);
        if (checkbox.checked) {
            panel.style.display = 'flex';
            if (panel.querySelectorAll('.session-row').length === 0) {
                addSession(day);
            }
        } else {
            panel.style.display = 'none';
        }
    }

    function addSession(day) {
        const panel = document.getElementById('sessions-' + day);
        const addBtn = panel.querySelector('.btn-add-session');
        const index = panel.querySelectorAll('.session-row').length;

        const row = document.createElement('div');
        row.className = 'session-row';
        row.innerHTML =
            '<input type="time" name="schedules[' + day + '][' + index + '][start_time]" required>' +
            '<span class="session-sep">-</span>' +
            '<input type="time" name="schedules[' + day + '][' + index + '][end_time]" required>' +
            '<button type="button" class="btn-remove-session" onclick="removeSession(this)" aria-label="Remove session"><i class="bi bi-trash"></i></button>';

        panel.insertBefore(row, addBtn);
    }

    function removeSession(btn) {
        const row = btn.closest('.session-row');
        const panel = row.closest('.day-sessions');
        const day = panel.id.replace('sessions-', '');
        row.remove();

        const rows = panel.querySelectorAll('.session-row');
        rows.forEach(function (r, i) {
            r.querySelectorAll('input').forEach(function (input) {
                const isStart = input.name.indexOf('start_time') !== -1;
                input.name = 'schedules[' + day + '][' + i + '][' + (isStart ? 'start_time' : 'end_time') + ']';
            });
        });

        if (rows.length === 0) {
            const checkbox = document.querySelector('.day-check[data-day="' + day + '"]');
            checkbox.checked = false;
            panel.style.display = 'none';
        }
    }

    // --- "Save changes?" confirmation before the schedule form submits ---
    document.getElementById('editScheduleForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const titleText = document.getElementById('editModalTitle').textContent || '';
        const doctorName = titleText.replace('Edit Schedule - ', '');
        document.getElementById('confirmSaveMessage').textContent =
            doctorName ? `This will update ${doctorName}'s schedule.` : `This will update the doctor's schedule.`;
        document.getElementById('confirmSaveOverlay').classList.add('show');
    });

    function cancelSaveSchedule() {
        document.getElementById('confirmSaveOverlay').classList.remove('show');
    }

    function confirmSaveSchedule() {
        document.querySelectorAll('.day-check').forEach(function (cb) {
            if (!cb.checked) {
                const panel = document.getElementById('sessions-' + cb.dataset.day);
                panel.querySelectorAll('input').forEach(function (input) {
                    input.disabled = true;
                });
            }
        });
        document.getElementById('confirmSaveOverlay').classList.remove('show');
        document.getElementById('editScheduleForm').submit();
    }
</script>
@endsection