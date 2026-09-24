@extends('staff.layouts.app')

@section('content')
@php
    $dayOrderForCompact = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

    if (!function_exists('formatScheduleTime')) {
        function formatScheduleTime(\Carbon\Carbon $c): string {
            return $c->format('i') === '00' ? $c->format('h') : $c->format('h:i');
        }
    }

    if (!function_exists('formatScheduleCompact')) {
        function formatScheduleCompact(string $day, $start, $end): string {
            $dayAbbrev = substr($day, 0, 3);
            $start = \Carbon\Carbon::parse($start);
            $end = \Carbon\Carbon::parse($end);
            $startPeriod = $start->format('A');
            $endPeriod = $end->format('A');

            if ($startPeriod === $endPeriod) {
                return "{$dayAbbrev} " . formatScheduleTime($start) . '–' . formatScheduleTime($end) . " {$endPeriod}";
            }

            return "{$dayAbbrev} " . formatScheduleTime($start) . " {$startPeriod}–" . formatScheduleTime($end) . " {$endPeriod}";
        }
    }
@endphp
<div class="container">
    <div class="page-title-group" style="margin-bottom: 1rem;">
        <h3 style="margin: 0;">
            <i class="bi bi-person-badge"></i> Manage Doctors
        </h3>
        <p class="page-subtitle">View and edit doctor schedules</p>
    </div>

    <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
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

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
            <select
                id="departmentFilter"
                style="padding: 0.55rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; outline: none; background: white; min-width: 130px;"
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
        <table class="data-table table-cards" id="doctorsTable">
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
                @php
                    $sortedSchedules = $doc->schedules->sortBy(function ($s) use ($dayOrderForCompact) {
                        return array_search($s->day, $dayOrderForCompact);
                    })->values();
                    $scheduleCount = $sortedSchedules->count();
                    $visibleSchedules = $sortedSchedules->take(2);
                    $remainingCount = $scheduleCount - $visibleSchedules->count();
                @endphp
                <tr data-specialty="{{ strtolower($doc->specialty) }}">
                    <td data-label="Name">
                        <div class="doctor-name-cell">
                            <div class="doctor-avatar">
                                @if($doc->profile_image)
                                    <img src="{{ asset('storage/' . $doc->profile_image) }}" alt="{{ $doc->doctor_name }}">
                                @else
                                    <i class="bi bi-person-fill"></i>
                                @endif
                            </div>
                            <span>{{ $doc->doctor_name }}</span>
                        </div>
                    </td>
                    <td data-label="Specialty">{{ $doc->specialty }}</td>
                    <td data-label="Schedule">
                        @if($scheduleCount > 0)
                            <div class="schedule-compact">
                                @foreach($visibleSchedules as $sched)
                                    <span>{{ formatScheduleCompact($sched->day, $sched->start_time, $sched->end_time) }}</span>
                                @endforeach
                                @if($remainingCount > 0)
                                    <div class="schedule-hidden-rows" id="scheduleMore-{{ $doc->doctor_id }}">
                                        @foreach($sortedSchedules->slice(2) as $sched)
                                            <span>{{ formatScheduleCompact($sched->day, $sched->start_time, $sched->end_time) }}</span>
                                        @endforeach
                                    </div>
                                    <button type="button" class="schedule-more-toggle" onclick="toggleScheduleMore({{ $doc->doctor_id }}, this)">+ {{ $remainingCount }} more</button>
                                @endif
                            </div>
                        @else
                            No schedule yet
                        @endif
                    </td>
                    <td data-label="Status">
                        <span class="status-badge {{ $doc->available ? 'status-available' : 'status-unavailable' }}">
                            <span class="status-dot"></span>{{ $doc->available ? 'Available' : 'Unavailable' }}
                        </span>
                    </td>
                    <td data-label="" class="table-cards-actions">
                        @if($doc->available)
                            {{-- Available pa yung doctor, pwede i-edit — pop-up modal na lang, walang page navigation --}}
                            <button type="button" class="btn-sm btn-edit-doctor" onclick="openEditModal({{ $doc->doctor_id }})">Edit</button>
                        @else
                            {{-- Naka-set na 'Unavailable' ng admin (inactive), hindi na dapat ma-edit ni staff --}}
                            <button type="button" class="btn-sm btn-edit-doctor" disabled
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
        <div class="confirm-modal-icon"><i class="bi bi-question-lg"></i></div>
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

    function toggleScheduleMore(doctorId, btn) {
        const hiddenRows = document.getElementById('scheduleMore-' + doctorId);
        if (!hiddenRows) return;
        const isShown = hiddenRows.classList.toggle('show');
        btn.textContent = isShown ? 'Show less' : btn.dataset.moreLabel;
    }

    document.querySelectorAll('.schedule-more-toggle').forEach(function (btn) {
        btn.dataset.moreLabel = btn.textContent;
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