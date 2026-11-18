@extends('staff.layouts.app')

@section('content')
<style>
    /* Admin "Scheduled Visits" look, scoped under .visits-admin. */
    .visits-admin { --light-gray: #ECF0F1; --primary-dark: #062744; --shadow: rgba(0,0,0,0.1); }
    .visits-admin .va-page-header { margin-bottom: 1.5rem; padding-left: 0.35rem; }
    .visits-admin .va-page-header h2 { font-size: 1.75rem; color: var(--primary-dark); display: flex; align-items: center; gap: 0.65rem; margin: 0; }
    .visits-admin .va-page-header h2 span { display: inline-flex; align-items: center; justify-content: center; width: 2.15rem; height: 2.15rem; flex-shrink: 0; border-radius: 10px; background: rgba(14,98,170,0.1); color: var(--primary-main); font-size: 1.05rem; }
    .visits-admin .va-page-header p { color: #7f8c8d; margin: 0.4rem 0 0; padding-left: 0.35rem; }

    .visits-admin .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .visits-admin .stat-card { background: #fff; padding: 1rem; border-radius: 12px; box-shadow: 0 2px 8px var(--shadow); text-align: center; border-left: 0; }
    .visits-admin .stat-number { font-size: 1.8rem; font-weight: bold; color: var(--primary-main); margin: 0; }
    .visits-admin .stat-label { font-size: 0.8rem; color: #7f8c8d; margin: 0; }

    .visits-admin .filter-bar { background: #fff; padding: 1rem 1.5rem; border-radius: 15px; margin-bottom: 1.5rem !important; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; justify-content: space-between; box-shadow: 0 2px 10px var(--shadow); }
    .visits-admin .filter-group { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
    .visits-admin .search-box { padding: 0.6rem 1rem; border: 1px solid var(--light-gray); border-radius: 8px; min-width: 180px; max-width: 100%; outline: none; background: #fff; font-size: 0.9rem; }
    .visits-admin .search-box:focus { border-color: var(--primary-main); box-shadow: 0 0 0 2px rgba(14,98,170,0.2); }
    .visits-admin .filter-select { padding: 0.5rem 1rem; border: 1px solid var(--light-gray); border-radius: 8px; background: #fff; cursor: pointer; font-size: 0.9rem; }

    .visits-admin .day-nav { display: flex; align-items: center; gap: 0.6rem; }
    .visits-admin .day-nav-btn { background: #fff; border: 1px solid #d7dce3; border-radius: 8px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.95rem; color: #062744; padding: 0; }
    .visits-admin .day-nav-btn:hover { background: #f0f4f9; }
    .visits-admin .day-picker { border: 1px solid #d7dce3; border-radius: 8px; padding: 0.45rem 0.6rem; font-size: 0.85rem; background: #fff; }
    .visits-admin .today-btn { background: #eef2f7; border: 1px solid #d7dce3; border-radius: 8px; padding: 0.45rem 0.75rem; font-size: 0.8rem; cursor: pointer; color: #062744; }
    .visits-admin .download-dropdown { position: relative; display: inline-block; }
    .visits-admin .download-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: var(--primary-main); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: background .2s ease; }
    .visits-admin .download-btn:hover { background: #0b4f8a; }
    .visits-admin .download-btn .chevron { font-size: 0.65rem; transition: transform .2s ease; }
    .visits-admin .download-dropdown.open .download-btn .chevron { transform: rotate(180deg); }
    .visits-admin .download-menu { display: none; position: absolute; top: calc(100% + 0.5rem); left: 0; background: #fff; border-radius: 10px; box-shadow: 0 8px 24px var(--shadow); overflow: hidden; min-width: 170px; z-index: 50; }
    .visits-admin .download-dropdown.open .download-menu { display: block; }
    .visits-admin .download-menu a { display: flex; align-items: center; gap: 0.65rem; width: 100%; padding: 0.65rem 1rem; text-decoration: none; font-size: 0.85rem; color: #333; transition: background .15s ease; }
    .visits-admin .download-menu a:hover { background: var(--light-gray); }
    .visits-admin .download-menu a i { width: 16px; color: var(--primary-main); }

    .visits-admin .day-panel { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px var(--shadow); }
    .visits-admin .appointments-table { width: 100%; border-collapse: collapse; background: #fff; }
    .visits-admin .appointments-table th { text-align: left; padding: 0.75rem 1.25rem; font-size: 0.85rem; }
    .visits-admin .appointments-table td { padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--light-gray); vertical-align: middle; font-size: 0.92rem; color: #333; }
    .visits-admin .appointments-table tr:hover td { background: #f8f9fa; }
    .visits-admin .notes-cell { max-width: 220px; }
    .visits-admin .btn-sm { display: inline-flex; align-items: center; gap: 0.4rem; border: none; border-radius: 999px; padding: 0.42rem 0.9rem; font-size: 0.78rem; font-weight: 600; cursor: pointer; white-space: nowrap; transition: background .15s ease, transform .1s ease; }
    .visits-admin .btn-sm:hover { transform: translateY(-1px); }
    .visits-admin .btn-edit { background: #eaf2fb; color: #0E62AA; }
    .visits-admin .btn-edit:hover { background: #d9e9f8; }
    .visits-admin .visited-cell .btn-sm { display: block; margin-top: 0.4rem; }
    .visits-admin .va-empty { text-align: center; padding: 1.5rem; background: #fff; border-radius: 20px; }

    body.dark-mode .visits-admin .va-page-header h2 { color: #e7edf5; }
    body.dark-mode .visits-admin :is(.stat-card, .filter-bar, .day-panel, .va-empty, .download-menu) { background: #1a2232; color: #e7edf5; }
    body.dark-mode .visits-admin :is(.search-box, .filter-select, .day-picker, .day-nav-btn, .today-btn) { background: #1a2232; color: #e7edf5; border-color: #344155; }
    body.dark-mode .visits-admin .appointments-table :is(tbody, tr, td) { background: #1a2232 !important; color: #e7edf5 !important; border-color: #344155 !important; }
    body.dark-mode .visits-admin .appointments-table tr:hover > td { background: #25344a !important; }
    body.dark-mode .visits-admin .download-menu a { color: #e7edf5; }
    body.dark-mode .visits-admin .download-menu a:hover { background: #25344a; }

    @media (max-width: 768px) {
        .visits-admin .va-page-header { padding-left: 0.15rem; }
        .visits-admin .va-page-header h2 { font-size: 1.4rem; gap: 0.55rem; }
        .visits-admin .va-page-header h2 span { width: 1.9rem; height: 1.9rem; font-size: 0.9rem; border-radius: 8px; }
        .visits-admin .va-page-header p { font-size: 0.85rem; padding-left: 0.15rem; }
        .visits-admin .filter-bar { padding: 0.75rem; gap: 0.5rem; margin-bottom: 0.75rem !important; border-radius: 12px; }
        .visits-admin .filter-group { display: flex; flex-wrap: wrap; gap: 0.5rem; width: 100%; }
        .visits-admin .filter-group :is(.search-box, .filter-select) { flex: 1 1 100%; width: 100%; min-width: 0; min-height: 42px; padding: 0.5rem 0.75rem; border-radius: 10px; }
        /* Row 1: [<] [date] [>]   Row 2: [Today] [Download] */
        .visits-admin .day-nav { flex-wrap: wrap; align-items: stretch; gap: 0.5rem; width: 100%; }
        .visits-admin .day-nav .day-nav-btn { flex: 0 0 44px; min-height: 42px; }
        .visits-admin .day-nav .day-picker { flex: 1 1 calc(100% - 88px - 1rem); width: auto; min-width: 0; min-height: 42px; border-radius: 10px; }
        .visits-admin .day-nav :is(.today-btn, .download-dropdown) { flex: 1 1 calc(50% - 0.25rem); min-width: 0; }
        .visits-admin .day-nav .today-btn { min-height: 42px; border-radius: 10px; }
        .visits-admin .day-nav .download-btn { width: 100%; min-height: 42px; justify-content: center; border-radius: 10px; }
        .visits-admin .day-panel { display: block; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .visits-admin .appointments-table { min-width: 640px; }
        .visits-admin .appointments-table th, .visits-admin .appointments-table td { padding: 0.75rem 0.5rem; font-size: 0.8rem; white-space: normal; }
    }

    /* Notes edit modal (unchanged behaviour) */
    .notes-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
    .notes-modal.show { display: flex; }
    .notes-modal-content { background: #FFFFFF; padding: 1.75rem; border-radius: 20px; max-width: 420px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .notes-modal-content h3 { margin-bottom: 1rem; color: #062744; font-size: 1.1rem; }
    .notes-modal-content textarea { width: 100%; min-height: 100px; padding: 0.7rem; border: 1px solid #ECF0F1; border-radius: 8px; font-family: inherit; font-size: 0.9rem; resize: vertical; }
    .notes-modal-buttons { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.25rem; }
    .notes-modal-buttons button { padding: 0.5rem 1.2rem; border: none; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; }
    .notes-modal-btn-cancel { background: #ECF0F1; color: #333333; }
    .notes-modal-btn-save { background: #0E62AA; color: #FFFFFF; }
</style>
<div class="container">
<div class="visits-admin">
    <div class="va-page-header">
        <h2><span><i class="bi bi-calendar2-plus"></i></span> Scheduled Visits</h2>
        <p>Review and manage scheduled patient visits, one day at a time</p>
    </div>

    @php $services = $visitServices; @endphp

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-number" id="totalAppointments">0</div><div class="stat-label">Scheduled Visits Today</div></div>
        <div class="stat-card"><div class="stat-number" id="totalPatients">0</div><div class="stat-label">Patients Scheduled</div></div>
        <div class="stat-card"><div class="stat-number" id="totalDoctors">0</div><div class="stat-label">Doctors Involved</div></div>
    </div>

    <div class="filter-bar">
        <div class="filter-group">
            <input type="search" id="visitSearch" class="search-box" placeholder="Search patient, doctor or notes" aria-label="Search scheduled visits" oninput="filterVisitsTable()">
            <select id="doctorFilter" class="filter-select" aria-label="Filter by doctor" onchange="syncVisitFilters('doctor')">
                <option value="">All Doctors</option>
                @foreach($visitDoctors->pluck('doctor_name')->unique() as $doctorName)
                    <option value="{{ $doctorName }}">{{ $doctorName }}</option>
                @endforeach
            </select>
            <select id="serviceFilter" class="filter-select" aria-label="Filter by doctor specialty" onchange="syncVisitFilters('service')">
                <option value="">All Services</option>
                @foreach($services as $service)
                    <option value="{{ $service }}">{{ $service }}</option>
                @endforeach
            </select>
        </div>

        <div class="day-nav">
            <button type="button" class="day-nav-btn" onclick="shiftDate(-1)" title="Previous day" aria-label="Previous day"><i class="fa-solid fa-chevron-left"></i></button>
            <input type="date" class="day-picker" id="visitDate" onchange="handleDateInputChange()">
            <button type="button" class="day-nav-btn" onclick="shiftDate(1)" title="Next day" aria-label="Next day"><i class="fa-solid fa-chevron-right"></i></button>
            <button type="button" class="today-btn" id="btnTodayFilter" onclick="setTodayFilter()">Today</button>
            <div class="download-dropdown" id="downloadDropdown">
                <button type="button" class="download-btn" id="downloadBtn" onclick="toggleExportMenu()">
                    <i class="fa-solid fa-download"></i> Download <i class="fa-solid fa-chevron-down chevron"></i>
                </button>
                <div class="download-menu" id="exportMenu">
                    <a href="{{ route('staff.visits.export', 'pdf') }}" target="_blank"><i class="fa-regular fa-file-pdf"></i> PDF</a>
                    <a href="{{ route('staff.visits.export', 'csv') }}"><i class="fa-regular fa-file-lines"></i> CSV</a>
                    <a href="{{ route('staff.visits.export', 'xlsx') }}"><i class="fa-regular fa-file-excel"></i> Excel</a>
                </div>
            </div>
        </div>
    </div>

    @if($appointments->count() > 0)
        <div class="day-panel" id="visitsPanel">
        <table class="appointments-table" id="visitsTable">
            <thead>
                <tr>
                    <th>Patient</th><th>Doctor</th><th>Service</th><th>Scheduled At</th><th>Notes</th><th>Visited</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($appointments as $apt)
                @php
                    $visitDate = \Carbon\Carbon::parse($apt->visit_date)->format('Y-m-d');
                    $scheduledAt = $apt->visit_time
                        ? \Carbon\Carbon::parse($apt->visit_date . ' ' . $apt->visit_time)->format('M j, g:i A') . ' – ' . \Carbon\Carbon::parse($apt->visit_end_time)->format('g:i A')
                        : \Carbon\Carbon::parse($apt->visit_date)->format('M j, g:i A');
                @endphp
                <tr data-visited="{{ $apt->visited ? '1' : '0' }}" data-date="{{ $visitDate }}" data-service="{{ strtolower($apt->service ?? '') }}" data-doctor="{{ $apt->doctor_name ?? '' }}" data-patient="{{ $apt->patient_name ?? '' }}" data-search="{{ strtolower(implode(' ', [$apt->patient_name, $apt->doctor_name, $apt->service, $apt->notes])) }}">
                    <td data-label="Patient"><strong>{{ $apt->patient_name ?: 'Unknown patient' }}</strong></td>
                    <td data-label="Doctor">{{ $apt->doctor_name ?? '—' }}</td>
                    <td data-label="Service">{{ $apt->service ?? '—' }}</td>
                    <td data-label="Scheduled At"><small style="color:#7f8c8d">{{ $scheduledAt }}</small></td>
                    <td data-label="Notes" class="notes-cell" id="notesText-{{ $apt->visit_id }}">{{ $apt->notes ?: '—' }}</td>
                    <td data-label="Visited" class="visited-cell" id="visitedText-{{ $apt->visit_id }}">
                        @if($apt->visited)
                            <strong style="color:#15803d">Visited</strong><br><small>{{ $apt->review_pass_sent_at ? 'Review pass emailed' : 'Email not sent' }}</small>
                        @else
                            <span class="visit-no-show">{{ \App\Services\VisitReviewService::attendanceLabel($apt) === 'Did not visit' ? 'Did not visit' : '' }}</span>
                        @endif
                    </td>
                    <td data-label="Actions">
                        <div class="visit-actions">
                            @if(!$apt->visited || !$apt->review_pass_sent_at)
                                <button type="button" class="btn-sm btn-edit" onclick="markVisitCompleted({{ $apt->visit_id }}, this)">{{ $apt->visited ? 'Retry email' : 'Mark visited' }}</button>
                            @endif
                            <details class="visit-menu">
                                <summary aria-label="More actions for {{ $apt->patient_name ?: 'this patient' }}">&#8942;</summary>
                                <div class="visit-menu-items">
                                    <button type="button" onclick="openNotesModal('{{ $apt->visit_id }}', {{ Js::from($apt->notes ?? '') }})">Edit</button>
                                    @if(!$apt->visited)
                                        <button type="button" data-pending-visit-action onclick="openVisitReschedule({{ $apt->visit_id }})">Reschedule</button>
                                    @endif
                                </div>
                            </details>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        <div class="va-empty" id="noResultsMsg" style="display: none;">No scheduled visit found for this day.</div>
    @else
        <div class="va-empty">No scheduled visit found for this day.</div>
    @endif
</div>
</div>

<!-- Notes edit modal -->
<div class="notes-modal" id="notesModal">
    <div class="notes-modal-content">
        <h3>Edit Notes</h3>
        <textarea id="notesModalTextarea" maxlength="1000"></textarea>
        <div class="notes-modal-buttons">
            <button type="button" class="notes-modal-btn-cancel" onclick="closeNotesModal()">Cancel</button>
            <button type="button" class="notes-modal-btn-save" onclick="saveNotes()">Save</button>
        </div>
    </div>
</div>

<script src="{{ asset('js/doctor-specialties.js') }}"></script>
<script>
    const visitDoctors = @json($visitDoctors);
    const visitServices = @json($visitServices);

    function syncVisitFilters(changed) {
        const doctorSelect = document.getElementById('doctorFilter');
        const serviceSelect = document.getElementById('serviceFilter');
        let doctor = visitDoctors.find(d => d.doctor_name === doctorSelect.value);
        let selected = serviceSelect.value;
        if (changed === 'service' && doctor && (!selected || !doctorSpecialties(doctor.specialty).includes(selected))) {
            doctorSelect.value = '';
            doctor = null;
        }
        const services = doctor ? doctorSpecialties(doctor.specialty).filter(s => visitServices.includes(s))
            : visitServices;
        if (changed === 'doctor' && doctor && services.length === 1) selected = services[0];
        serviceSelect.replaceChildren(new Option('All Services', ''), ...services.map(s => new Option(s, s)));
        serviceSelect.value = services.includes(selected) ? selected : '';
        const currentDoctor = doctorSelect.value;
        const allowed = visitDoctors.filter(d => !serviceSelect.value || doctorSpecialties(d.specialty).includes(serviceSelect.value));
        doctorSelect.replaceChildren(new Option('All Doctors', ''), ...allowed.map(d => new Option(d.doctor_name, d.doctor_name)));
        doctorSelect.value = allowed.some(d => d.doctor_name === currentDoctor) ? currentDoctor : '';
        filterVisitsTable();
    }
    // --- Notes edit modal ---
    let currentNotesVisitId = null;

    function openNotesModal(visitId, currentNotes) {
        currentNotesVisitId = visitId;
        document.getElementById('notesModalTextarea').value = currentNotes || '';
        document.getElementById('notesModal').classList.add('show');
    }

    function closeNotesModal() {
        document.getElementById('notesModal').classList.remove('show');
        currentNotesVisitId = null;
    }

function showVisitMessage(message, { confirm = false, variant = 'primary', title = 'Mark visit as completed?' } = {}) {
        let overlay = document.getElementById('visitMessageOverlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'visitMessageOverlay';
            overlay.className = 'visit-message-overlay';
            overlay.hidden = true;
            overlay.innerHTML = '<section class="visit-message-dialog" role="dialog" aria-modal="true" aria-labelledby="visitMessageTitle" aria-describedby="visitMessageText"><div class="visit-message-icon" aria-hidden="true"></div><h3 id="visitMessageTitle"></h3><p id="visitMessageText"></p><div class="visit-message-actions"><button type="button" class="visit-message-cancel">Cancel</button><button type="button" class="visit-message-ok">Confirm</button></div></section>';
            document.body.appendChild(overlay);
        }
        const cancel = overlay.querySelector('.visit-message-cancel');
        const ok = overlay.querySelector('.visit-message-ok');
        const priorFocus = document.activeElement;
        const priorOverflow = document.body.style.overflow;
        overlay.dataset.variant = variant;
        const icon = overlay.querySelector('.visit-message-icon');
        icon.style.display = confirm ? 'none' : '';
        icon.textContent = variant === 'success' ? '✓' : '!';
        overlay.querySelector('#visitMessageTitle').textContent = title;
        overlay.querySelector('#visitMessageText').textContent = message;
        cancel.hidden = !confirm;
        ok.textContent = confirm ? 'Confirm' : 'OK';
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        (confirm ? cancel : ok).focus();
        return new Promise(resolve => {
            function close(answer) {
                overlay.hidden = true;
                document.body.style.overflow = priorOverflow;
                cancel.removeEventListener('click', onCancel);
                ok.removeEventListener('click', onOk);
                overlay.removeEventListener('click', onBackdrop);
                document.removeEventListener('keydown', onKey);
                if (priorFocus?.isConnected) priorFocus.focus();
                resolve(answer);
            }
            function onCancel() { close(false); }
            function onOk() { close(true); }
            function onBackdrop(event) { if (event.target === overlay) close(false); }
            function onKey(event) {
                if (event.key === 'Escape') { event.preventDefault(); close(false); }
                if (event.key === 'Tab') {
                    event.preventDefault();
                    if (!confirm) ok.focus();
                    else (document.activeElement === cancel ? ok : cancel).focus();
                }
            }
            cancel.addEventListener('click', onCancel);
            ok.addEventListener('click', onOk);
            overlay.addEventListener('click', onBackdrop);
            document.addEventListener('keydown', onKey);
        });
    }
    async function markVisitCompleted(id, button) {
        if (button.disabled) return;
        button.disabled = true;
        try {
            if (!await showVisitMessage('Confirm this patient attended the visit and send their review pass?', { confirm: true })) return;
            const response = await fetch(`/staff/appointments/${id}/visited`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            });
            const data = await response.json();
            await showVisitMessage(data.message || Object.values(data.errors || {}).flat()[0] || 'Unable to update this visit.', {
                title: data.success ? (data.email_sent ? 'Visit completed' : 'Email needs attention') : 'Unable to update visit',
                variant: data.success ? (data.email_sent ? 'success' : 'warning') : 'error',
            });
            if (data.success) {
                document.getElementById(`visitedText-${id}`).innerHTML = '<strong style="color:#15803d">Visited</strong><br><small>' + (data.email_sent ? 'Review pass emailed' : 'Email not sent') + '</small>';
                const row = document.getElementById(`visitedText-${id}`).closest('tr');
                row.dataset.visited = '1';
                row.querySelectorAll('[data-pending-visit-action]').forEach(action => action.remove());
                document.dispatchEvent(new CustomEvent('visit-completed', { detail: { id } }));
                if (!data.email_sent) {
                    button.textContent = 'Retry email';

                }
                if (data.email_sent) button.remove();
                else button.textContent = 'Retry email';
            }
        } catch (error) {
            await showVisitMessage('Could not reach the server. Please refresh to check the visit status before retrying.', { title: 'Connection problem', variant: 'error' });
        } finally { button.disabled = false; }
    }

    function saveNotes() {
        if (!currentNotesVisitId) return;
        const notes = document.getElementById('notesModalTextarea').value;

        // NOTE: adjust this URL to match your actual staff route once
        // it's registered, e.g. route('staff.visits.notes.update', $id)
        fetch(`/staff/visits/${currentNotesVisitId}/notes`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
            },
            body: JSON.stringify({ notes })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`notesText-${currentNotesVisitId}`).textContent = notes || '—';
                    const row = document.getElementById(`notesText-${currentNotesVisitId}`).closest('tr');
                    row.dataset.search = [row.cells[0].textContent, row.dataset.doctor, row.dataset.service, notes].join(' ').toLowerCase();
                    filterVisitsTable();
                    closeNotesModal();
                } else {
                    alert('Could not save notes. Please try again.');
                }
            })
            .catch(() => alert('Could not save notes. Please try again.'));
    }

    function toggleExportMenu() {
        document.getElementById('downloadDropdown').classList.toggle('open');
    }

    document.addEventListener('click', function (e) {
        const dd = document.getElementById('downloadDropdown');
        if (dd && !dd.contains(e.target)) dd.classList.remove('open');
    });

    function todayISO() {
        const now = new Date();
        const offset = now.getTimezoneOffset();
        const local = new Date(now.getTime() - offset * 60000);
        return local.toISOString().slice(0, 10);
    }

    function parseDateInputValue(str) {
        const [y, m, d] = str.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    function toISO(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function updateDateLabel() {}

    function updateTodayButtonState() {}

    function setTodayFilter() {
        document.getElementById('visitDate').value = todayISO();
        handleDateInputChange();
    }


    function shiftDate(days) {
        const input = document.getElementById('visitDate');
        const base = input.value ? parseDateInputValue(input.value) : new Date();
        base.setDate(base.getDate() + days);
        input.value = toISO(base);
        handleDateInputChange();
    }

    function handleDateInputChange() {
        updateDateLabel();
        updateTodayButtonState();
        filterVisitsTable();
    }

    function filterVisitsTable() {
        const selectedDate = document.getElementById('visitDate').value;
        const serviceFilter = document.getElementById('serviceFilter').value;
        const doctor = document.getElementById('doctorFilter').value;
        const search = document.getElementById('visitSearch').value.trim().toLowerCase();
        document.querySelectorAll('#exportMenu a').forEach(link => {
            const url = new URL(link.href);
            url.search = new URLSearchParams({ date: selectedDate, service: serviceFilter, doctor, search }).toString();
            link.href = url.toString();
        });

        const table = document.getElementById('visitsTable');
        if (!table) { updateVisitStats([]); return; }

        const rows = table.tBodies[0].rows;
        const patients = new Set();
        const doctors = new Set();
        let visibleCount = 0;

        for (const row of rows) {
            const matchesDate = !selectedDate || row.dataset.date === selectedDate;
            const rowDoctor = visitDoctors.find(d => d.doctor_name === row.dataset.doctor);
            const matchesService = serviceFilter === '' || doctorSpecialties(rowDoctor?.specialty).includes(serviceFilter);
            const isMatch = matchesDate && matchesService && (!doctor || row.dataset.doctor === doctor)
                && (row.dataset.search || '').includes(search);
            row.style.display = isMatch ? '' : 'none';
            if (isMatch) {
                visibleCount++;
                if (row.dataset.patient) patients.add(row.dataset.patient);
                if (row.dataset.doctor) doctors.add(row.dataset.doctor);
            }
        }

        updateVisitStats([visibleCount, patients.size, doctors.size]);
        document.getElementById('visitsPanel').style.display = visibleCount === 0 ? 'none' : '';
        document.getElementById('noResultsMsg').style.display = visibleCount === 0 ? 'block' : 'none';
    }

    function updateVisitStats(values) {
        ['totalAppointments', 'totalPatients', 'totalDoctors'].forEach((id, i) => {
            document.getElementById(id).textContent = values[i] ?? 0;
        });
    }

    document.getElementById('visitDate').value = todayISO();
    filterVisitsTable();
    function refreshVisitAttendance() {
        const parts = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Manila', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date());
        const value = type => parts.find(part => part.type === type).value;
        const today = `${value('year')}-${value('month')}-${value('day')}`;
        document.querySelectorAll('#visitsTable tbody tr[data-visited="0"]').forEach(row => {
            const label = row.querySelector('.visit-no-show');
            if (label) label.textContent = row.dataset.date && row.dataset.date < today ? 'Did not visit' : '';
        });
    }
    refreshVisitAttendance();
    setInterval(refreshVisitAttendance, 60000);
</script>
@endsection
@push('scripts')
@include('partials.visit-reschedule', ['rescheduleBase' => '/staff/appointments'])
@include('partials.visit-actions')
@include('partials.visit-calendar', ['calendarInput' => 'visitDate'])
@endpush