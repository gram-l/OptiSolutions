@extends('staff.layouts.app')

@section('content')
<div class="container" style="max-width: 100%; padding: 0 1rem;">
    <div style="max-width: 620px; margin: 0 auto; background: white; border-radius: 20px; padding: 2rem; box-shadow: var(--shadow);">

        <h3 style="color: var(--text-dark); margin-bottom: 1.5rem;">Edit Schedule - {{ $doctor->doctor_name }}</h3>

        @php
            $dayOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            $schedulesByDay = $doctor->schedules->groupBy('day');
        @endphp

        <form method="POST" action="{{ route('staff.doctors.update', $doctor->doctor_id) }}" id="scheduleForm">
            @csrf
            @method('PUT')

            <div class="day-list">
                @foreach($dayOrder as $day)
                    @php $daySessions = $schedulesByDay->get($day, collect()); @endphp
                    <div class="day-block">
                        <label class="day-toggle">
                            <input
                                type="checkbox"
                                class="day-check"
                                data-day="{{ $day }}"
                                {{ $daySessions->isNotEmpty() ? 'checked' : '' }}
                            >
                            <span>{{ $day }}</span>
                        </label>

                        <div class="day-sessions" id="sessions-{{ $day }}" style="{{ $daySessions->isNotEmpty() ? '' : 'display:none;' }}">
                            @forelse($daySessions as $i => $sched)
                                <div class="session-row">
                                    <input type="time" name="schedules[{{ $day }}][{{ $i }}][start_time]"
                                        value="{{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }}" required>
                                    <span class="session-sep">-</span>
                                    <input type="time" name="schedules[{{ $day }}][{{ $i }}][end_time]"
                                        value="{{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }}" required>
                                    <button type="button" class="btn-remove-session" onclick="removeSession(this)" aria-label="Remove session">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            @empty
                            @endforelse
                            <button type="button" class="btn-add-session" onclick="addSession('{{ $day }}')">+ Add session</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="display:flex; gap:0.5rem; justify-content:flex-end; margin-top: 1.5rem;">
                <a href="{{ route('staff.doctors') }}" class="btn-sm btn-secondary" style="padding:0.8rem 2rem;">Cancel</a>
                <button type="submit" class="btn-sm btn-primary" style="padding:0.8rem 2rem;">Save</button>
            </div>
        </form>

    </div>
</div>

<style>
    .day-list {
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
    }

    .day-block {
        background: #f4f5f7;
        border-radius: 12px;
        padding: 0.9rem 1rem;
    }

    .day-toggle {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        font-weight: 500;
        color: var(--text-dark, #2c3e50);
    }

    .day-toggle input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-deep-blue, #1a5fb4);
        cursor: pointer;
    }

    .day-sessions {
        margin-top: 0.75rem;
        padding-left: 1.9rem;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
    }

    .session-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .session-row input[type="time"] {
        padding: 0.5rem 0.7rem;
        border: 1px solid var(--light-gray, #e0e0e0);
        border-radius: 8px;
        font-size: 0.9rem;
        background: white;
    }

    .session-sep {
        color: #7f8c8d;
    }

    .btn-remove-session {
        border: none;
        background: transparent;
        color: #e74c3c;
        cursor: pointer;
        padding: 0.3rem;
        display: flex;
        align-items: center;
    }

    .btn-add-session {
        border: none;
        background: transparent;
        color: var(--primary-deep-blue, #1a5fb4);
        font-weight: 500;
        cursor: pointer;
        padding: 0.2rem 0;
        text-align: left;
        width: fit-content;
        font-size: 0.9rem;
    }

    /* Dark mode */
    body.dark-mode .day-block {
        background: #232b3d;
    }

    body.dark-mode .day-toggle {
        color: #e4e8ee;
    }

    body.dark-mode .session-row input[type="time"] {
        background: #1a2130;
        color: #e4e8ee;
        border-color: #333d4f;
    }

    body.dark-mode .session-row input[type="time"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
    }

    body.dark-mode .session-sep {
        color: #8b95a5;
    }

    body.dark-mode .btn-add-session {
        color: #6cb2f5;
    }
</style>

<script>
    document.querySelectorAll('.day-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            toggleDay(cb);
        });
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

    document.getElementById('scheduleForm').addEventListener('submit', function () {
        document.querySelectorAll('.day-check').forEach(function (cb) {
            if (!cb.checked) {
                const panel = document.getElementById('sessions-' + cb.dataset.day);
                panel.querySelectorAll('input').forEach(function (input) {
                    input.disabled = true;
                });
            }
        });
    });
</script>
@endsection