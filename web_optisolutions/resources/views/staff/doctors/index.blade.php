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

    // One line per scheduled day, e.g. "Mon 10 AM–03 PM" / "Tue 01–03 PM" (as in the table design).
    if (!function_exists('doctorScheduleLines')) {
        function doctorScheduleLines($schedules): array {
            if (!$schedules || $schedules->isEmpty()) return [];
            $order = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            $fmt = function ($t, $withMeridiem) {
                $c = \Carbon\Carbon::parse($t);
                return $c->format($c->minute === 0 ? 'h' : 'h:i') . ($withMeridiem ? ' ' . $c->format('A') : '');
            };
            return $schedules->sortBy(fn ($s) => [array_search($s->day, $order), $s->start_time])->map(function ($s) use ($fmt) {
                $day = substr($s->day, 0, 3);
                if (!$s->start_time || !$s->end_time) return $day;
                $start = \Carbon\Carbon::parse($s->start_time);
                $end = \Carbon\Carbon::parse($s->end_time);
                $sameMeridiem = $start->format('A') === $end->format('A');
                return $day . ' ' . $fmt($s->start_time, !$sameMeridiem) . '–' . $fmt($s->end_time, true);
            })->values()->all();
        }
    }
@endphp

<style>
    /* Admin "Manage Doctors" card look, scoped so it doesn't affect other staff pages. */
    .docs-admin { --light-gray: #ECF0F1; --primary-dark: #062744; --shadow: rgba(0,0,0,0.1); }
    .docs-admin .dc-page-header { margin-bottom: 1.25rem; padding-left: 0.35rem; }
    .docs-admin .dc-page-header h2 { font-size: 1.75rem; color: var(--primary-dark); display: flex; align-items: center; gap: 0.65rem; margin: 0; }
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

    .docs-admin .doctors-table-wrap { background: #fff; border-radius: 15px; box-shadow: 0 2px 10px var(--shadow); overflow-x: auto; }
    .docs-admin .doctors-table { width: 100%; border-collapse: collapse; min-width: 760px; }
    .docs-admin .doctors-table thead th { background: #ECF0F1; color: #34495e; font-size: 0.85rem; font-weight: 600; text-align: left; padding: 1rem 1.25rem; white-space: nowrap; }
    .docs-admin .doctors-table tbody td { padding: 0.9rem 1.25rem; border-bottom: 1px solid var(--light-gray); vertical-align: middle; font-size: 0.92rem; color: var(--primary-dark); }
    .docs-admin .doctors-table tbody tr:last-child td { border-bottom: none; }
    .docs-admin .doctors-table tbody tr.doctor-row:hover { background: #f8fafc; }
    .docs-admin .doctors-table tr.inactive td { opacity: 0.7; }
    .docs-admin .doc-cell { display: flex; align-items: center; gap: 0.75rem; font-weight: 500; }
    .docs-admin .doctor-avatar { width: 42px; height: 42px; border-radius: 50%; background: var(--primary-main); color: #fff; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
    .docs-admin .doctor-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .docs-admin .sched-line { font-size: 0.85rem; color: #5a6474; line-height: 1.55; white-space: nowrap; }
    .docs-admin .sched-extra { display: none; }
    .docs-admin .sched-extra.open { display: block; }
    .docs-admin .sched-more { background: none; border: none; padding: 0; margin-top: 0.1rem; color: var(--primary-main); font-size: 0.8rem; font-weight: 600; cursor: pointer; }
    .docs-admin .sched-more:hover { text-decoration: underline; }
    .docs-admin .sched-none { font-size: 0.85rem; color: #95a5a6; }
    .docs-admin .status-badge { display: inline-block; padding: 0.3rem 0.85rem; border-radius: 20px; font-size: 0.78rem; font-weight: 600; white-space: nowrap; }
    .docs-admin .status-active { background: #e8f8f0; color: #1e9e5a; }
    .docs-admin .status-inactive { background: #fdeaea; color: #e74c3c; }
    .docs-admin .btn-icon { display: inline-flex; align-items: center; justify-content: center; padding: 0.4rem 1.1rem; border: none; border-radius: 20px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all .2s ease; background: #e8f0fe; color: var(--primary-main); }
    .docs-admin .btn-icon:hover { background: #d5e5fc; }
    .docs-admin .btn-icon:disabled { background: var(--light-gray); color: #a3adb8; cursor: not-allowed; }
    .docs-admin .empty-state { text-align: center; padding: 3rem 1rem !important; color: #95a5a6 !important; }
    body.dark-mode .docs-admin .dc-page-header h2, body.dark-mode .docs-admin .doctors-table tbody td { color: #e7edf5; }
    body.dark-mode .docs-admin :is(.action-bar, .doctors-table-wrap, .search-box, .filter-select) { background: #1a2232; border-color: #344155; color: #e7edf5; }
    body.dark-mode .docs-admin .doctors-table thead th { background: #293548; color: #afbdce; }
    body.dark-mode .docs-admin .doctors-table tbody td { border-color: #344155; }
    body.dark-mode .docs-admin .doctors-table tbody tr.doctor-row:hover { background: #222d40; }
    body.dark-mode .docs-admin :is(.sched-line, .sched-none) { color: #afbdce; }
    @media (max-width: 768px) {
        .docs-admin .dc-page-header { padding-left: 0.15rem; }
        .docs-admin .dc-page-header h2 { font-size: 1.4rem; }
        .docs-admin .action-bar, .docs-admin .action-bar .filters { flex-direction: column; align-items: stretch; }
        .docs-admin .search-box { width: 100%; }
        .docs-admin .dl-btn { justify-content: center; width: 100%; }
    }
</style>

<div class="container">
<div class="docs-admin">
    <div class="dc-page-header">
        <h2>Manage Doctors</h2>
        <p>View and edit doctor schedules</p>
    </div>

    <div class="action-bar">
        <div class="filters">
            <input type="text" class="search-box" id="doctorSearch" placeholder="Search name or specialty..." oninput="filterDoctorsTable()">
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

    <div class="doctors-table-wrap">
        <table class="doctors-table" id="doctorsTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Specialty</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($doctors as $doc)
                    @php
                        $isActive = (bool) $doc->available;
                        $lines = doctorScheduleLines($doc->schedules);
                        $initials = collect(preg_split('/\s+/', preg_replace('/^Dr\.?\s*/i', '', trim((string) $doc->doctor_name))))
                            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                    @endphp
                    <tr class="doctor-row {{ $isActive ? '' : 'inactive' }}"
                        data-name="{{ strtolower($doc->doctor_name) }}"
                        data-specialty="{{ strtolower($doc->specialty) }}"
                        data-status="{{ $isActive ? 'active' : 'inactive' }}">
                        <td>
                            <div class="doc-cell">
                                <div class="doctor-avatar">
                                    @if($doc->profile_image)
                                        <img src="{{ asset('storage/' . $doc->profile_image) }}" alt="{{ $doc->doctor_name }}">
                                    @else
                                        {{ $initials }}
                                    @endif
                                </div>
                                <span>{{ $doc->doctor_name }}</span>
                            </div>
                        </td>
                        <td>{{ $doc->specialty }}</td>
                        <td>
                            @if(count($lines) === 0)
                                <span class="sched-none">Schedule not set</span>
                            @else
                                @foreach(array_slice($lines, 0, 2) as $line)
                                    <div class="sched-line">{{ $line }}</div>
                                @endforeach
                                @if(count($lines) > 2)
                                    <div class="sched-extra">
                                        @foreach(array_slice($lines, 2) as $line)
                                            <div class="sched-line">{{ $line }}</div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="sched-more" data-more="+ {{ count($lines) - 2 }} more" onclick="toggleScheduleMore(this)">+ {{ count($lines) - 2 }} more</button>
                                @endif
                            @endif
                        </td>
                        <td>
                            <span class="status-badge {{ $isActive ? 'status-active' : 'status-inactive' }}">
                                {{ $isActive ? '● Available' : '○ Inactive' }}
                            </span>
                        </td>
                        <td style="text-align:right;">
                            @if($isActive)
                                <button type="button" class="btn-icon" onclick="openEditModal({{ $doc->doctor_id }})">Edit</button>
                            @else
                                <button type="button" class="btn-icon" disabled title="Set as inactive by the admin, so its schedule can't be edited here.">Edit</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">No doctors found.</td></tr>
                @endforelse
                <tr id="noResultsMsg" style="display: none;"><td colspan="5" class="empty-state">No matching doctors found.</td></tr>
            </tbody>
        </table>
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

    function toggleScheduleMore(btn) {
        const extra = btn.previousElementSibling;
        const open = extra.classList.toggle('open');
        btn.textContent = open ? 'Show less' : btn.dataset.more;
    }

    function filterDoctorsTable() {
        const search = document.getElementById('doctorSearch').value.trim().toLowerCase();
        const department = document.getElementById('departmentFilter').value;
        const status = document.getElementById('statusFilter').value;
        let visible = 0;

        document.querySelectorAll('#doctorsTable tr.doctor-row').forEach(function (card) {
            const specialty = card.dataset.specialty || '';
            const matchesSearch = (card.dataset.name + ' ' + specialty).includes(search);
            const matchesDepartment = department === '' || specialty.split('/').map(function (v) { return v.trim(); }).includes(department);
            const matchesStatus = status === '' || card.dataset.status === status;
            const show = matchesSearch && matchesDepartment && matchesStatus;
            card.style.display = show ? '' : 'none';
            if (!show) { const ex = card.querySelector('.sched-extra'); if (ex) ex.classList.remove('open'); }
            if (show) visible++;
        });

        const msg = document.getElementById('noResultsMsg');
        if (msg) msg.style.display = visible === 0 && document.querySelector('#doctorsTable tr.doctor-row') ? 'table-row' : 'none';
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