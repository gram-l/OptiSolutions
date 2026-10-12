@extends('staff.layouts.app')

@section('content')
<style>
    /* Scoped to this page only — mirrors admin's appointments.css look
       (filter bar, table, pastel action buttons) without touching the
       shared staff stylesheet. */
    .visits-filter-bar {
        background: #FFFFFF;
        padding: 1rem 1.5rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .visits-filter-bar select#serviceFilter {
        padding: 0.5rem 1rem;
        border: 1px solid #ECF0F1;
        border-radius: 8px;
        background: #FFFFFF;
        cursor: pointer;
        font-size: 0.9rem;
        font-family: inherit;
    }

    .visits-filter-bar .filter-bar-right {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .visits-filter-bar .date-nav {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .visits-filter-bar .date-nav-btn {
        padding: 0.5rem 1rem;
        background: #ECF0F1;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #333333;
    }

    .visits-filter-bar .date-nav-btn:hover {
        background: #0E62AA;
        color: #FFFFFF;
    }

    .visits-filter-bar input#visitDate {
        padding: 0.5rem 0.75rem;
        border: 1px solid #ECF0F1;
        border-radius: 8px;
        font-size: 0.9rem;
        font-family: inherit;
    }

    .visits-filter-bar .date-label {
        font-size: 0.85rem;
        color: #7f8c8d;
    }

    .visits-filter-bar .btn-sm {
        padding: 0.5rem 1.1rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .visits-filter-bar .btn-sm.btn-primary {
        background: #0E62AA;
        color: #FFFFFF;
    }

    .visits-filter-bar .btn-sm.btn-primary:hover {
        background: #0b4f8a;
    }

    .visits-filter-bar .btn-sm.btn-secondary {
        background: #ECF0F1;
        color: #333333;
    }

    .visits-filter-bar .btn-sm.btn-secondary:hover {
        background: #dde2e6;
    }

    #downloadBtn {
        background: #0E62AA !important;
        color: #FFFFFF !important;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    #downloadBtn:hover {
        background: #0b4f8a !important;
    }

    #exportMenu {
        background: #FFFFFF;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        border: 1px solid #ECF0F1;
        display: none;
    }

    #exportMenu.show {
        display: block;
    }

    #exportMenu .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 1rem;
        font-size: 0.88rem;
        color: #333333;
        transition: background 0.2s ease;
    }

    #exportMenu .dropdown-item:hover {
        background: #f4f6f8;
    }

    /* Table — same recipe as the admin appointments table */
    #visitsTable {
        width: 100%;
        border-collapse: collapse;
        background: #FFFFFF;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    #visitsTable thead th {
        text-align: left;
        padding: 0.75rem 1.25rem;
        background: #ECF0F1;
        color: #333333;
        font-weight: 600;
        font-size: 0.85rem;
    }

    #visitsTable tbody td {
        padding: 0.75rem 1.25rem;
        border-bottom: 1px solid #ECF0F1;
        vertical-align: middle;
        font-size: 0.92rem;
        color: #333333;
    }

    #visitsTable tbody tr:hover {
        background: #f8f9fa;
    }

    .notes-cell {
        max-width: 220px;
        color: #5a6474;
    }

    .btn-edit-notes {
        padding: 0.35rem 0.85rem;
        border: none;
        border-radius: 20px;
        cursor: pointer;
        font-size: 0.75rem;
        font-weight: 600;
        background: #e8f0fe;
        color: #0E62AA;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: all 0.2s ease;
    }

    .btn-edit-notes:hover {
        background: #d5e5fc;
    }

    /* Notes edit modal */
    .notes-modal {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .notes-modal.show {
        display: flex;
    }

    .notes-modal-content {
        background: #FFFFFF;
        padding: 1.75rem;
        border-radius: 20px;
        max-width: 420px;
        width: 90%;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .notes-modal-content h3 {
        margin-bottom: 1rem;
        color: #062744;
        font-size: 1.1rem;
    }

    .notes-modal-content textarea {
        width: 100%;
        min-height: 100px;
        padding: 0.7rem;
        border: 1px solid #ECF0F1;
        border-radius: 8px;
        font-family: inherit;
        font-size: 0.9rem;
        resize: vertical;
    }

    .notes-modal-buttons {
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
        margin-top: 1.25rem;
    }

    .notes-modal-buttons button {
        padding: 0.5rem 1.2rem;
        border: none;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .notes-modal-btn-cancel {
        background: #ECF0F1;
        color: #333333;
    }

    .notes-modal-btn-save {
        background: #0E62AA;
        color: #FFFFFF;
    }
</style>
<div class="container">
    <div class="page-title-group" style="margin-bottom: 1rem;">
        <h3 style="margin: 0;">
            <i class="bi bi-calendar-plus"></i> Scheduled Visits
        </h3>
        <p class="page-subtitle">Review and manage scheduled patient visits</p>
    </div>

    @php
        $services = $appointments->pluck('service')->filter()->unique()->sort()->values();
    @endphp

    <div class="visits-filter-bar">
        <select id="serviceFilter" onchange="filterVisitsTable()">
            <option value="">All Services</option>
            @foreach($services as $service)
                <option value="{{ strtolower($service) }}">{{ $service }}</option>
            @endforeach
        </select>

        <div class="filter-bar-right">
            <div class="date-nav">
                <button type="button" class="date-nav-btn" onclick="shiftDate(-1)" aria-label="Previous day">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <input type="date" id="visitDate" onchange="handleDateInputChange()">
                <button type="button" class="date-nav-btn" onclick="shiftDate(1)" aria-label="Next day">
                    <i class="bi bi-chevron-right"></i>
                </button>
                <button type="button" id="btnTodayFilter" class="btn-sm btn-primary" onclick="setTodayFilter()">Today</button>
                <button type="button" id="btnClearFilter" class="btn-sm btn-secondary" onclick="clearVisitFilters()">Clear</button>
                <span id="dateLabel" class="date-label"></span>
            </div>

            <div class="download-wrap" style="position: relative;">
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
                    <a href="{{ route('staff.visits.export', 'csv') }}" class="dropdown-item" style="text-decoration: none;">
                        <i class="bi bi-filetype-csv"></i> Export as CSV
                    </a>
                    <a href="{{ route('staff.visits.export', 'xlsx') }}" class="dropdown-item" style="text-decoration: none;">
                        <i class="bi bi-filetype-xlsx"></i> Export as Excel
                    </a>
                    <a href="{{ route('staff.visits.export', 'pdf') }}" target="_blank" class="dropdown-item" style="text-decoration: none;">
                        <i class="bi bi-filetype-pdf"></i> Export as PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($appointments->count() > 0)
        <table class="data-table table-cards" id="visitsTable">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Service</th>
                    <th>Scheduled At</th>
                    <th>Notes</th>
                    <th>Visited</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($appointments as $apt)
                @php
                    $visitDate = \Carbon\Carbon::parse($apt->visit_date)->format('Y-m-d');
                    $scheduledAt = \Carbon\Carbon::parse($apt->visit_date)->format('M j, g:i A');
                @endphp
                <tr data-date="{{ $visitDate }}" data-service="{{ strtolower($apt->service ?? '') }}">
                    <td data-label="Patient">{{ $apt->patient_name ?: 'N/A' }}</td>
                    <td data-label="Doctor">{{ $apt->doctor_name ?? 'N/A' }}</td>
                    <td data-label="Service">{{ $apt->service ?? 'N/A' }}</td>
                    <td data-label="Scheduled At">{{ $scheduledAt }}</td>
                    <td data-label="Notes" class="notes-cell" id="notesText-{{ $apt->visit_id }}">{{ $apt->notes ?: '—' }}</td>
                    <td data-label="Visited" id="visitedText-{{ $apt->visit_id }}">
                        @if($apt->visited)
                            <strong style="color:#15803d">Visited</strong><br><small>{{ $apt->review_pass_sent_at ? 'Review pass emailed' : 'Email not sent' }}</small>
                        @else
                            Not visited
                        @endif
                    </td>
                    <td data-label="" class="table-cards-actions">
                        @if(!$apt->visited || !$apt->review_pass_sent_at)
                            <button type="button" class="btn-edit-notes" onclick="markVisitCompleted({{ $apt->visit_id }}, this)">{{ $apt->visited ? 'Retry email' : 'Mark visited' }}</button>
                        @endif
                        <button
                            type="button"
                            class="btn-edit-notes"
                            onclick="openNotesModal('{{ $apt->visit_id }}', {{ Js::from($apt->notes ?? '') }})"
                        >
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p id="noResultsMsg" style="display: none; padding: 2rem; text-align: center; background: white; border-radius: 20px;">No visits scheduled for this day.</p>
    @else
        <p style="padding: 2rem; text-align: center; background: white; border-radius: 20px;">No appointments found.</p>
    @endif
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

<script>
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
        overlay.querySelector('.visit-message-icon').textContent = confirm ? '?' : (variant === 'success' ? '✓' : '!');
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
                    closeNotesModal();
                } else {
                    alert('Could not save notes. Please try again.');
                }
            })
            .catch(() => alert('Could not save notes. Please try again.'));
    }

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

    function formatDateLabel(dateStr) {
        const d = parseDateInputValue(dateStr);
        return d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }

    function updateDateLabel() {
        const input = document.getElementById('visitDate');
        const label = document.getElementById('dateLabel');
        label.textContent = input.value ? formatDateLabel(input.value) : 'Showing all scheduled visits';
    }

    function updateTodayButtonState() {
        const input = document.getElementById('visitDate');
        const isToday = input.value === todayISO();
        const btn = document.getElementById('btnTodayFilter');
        btn.classList.toggle('btn-primary', isToday);
        btn.classList.toggle('btn-secondary', !isToday);
    }

    function setTodayFilter() {
        document.getElementById('visitDate').value = todayISO();
        handleDateInputChange();
    }

    function clearVisitFilters() {
        document.getElementById('visitDate').value = '';
        document.getElementById('serviceFilter').value = '';
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

        const table = document.getElementById('visitsTable');
        if (!table) return;

        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        let visibleCount = 0;

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const rowDate = row.getAttribute('data-date');
            const rowService = row.getAttribute('data-service') || '';


            const matchesDate = !selectedDate || rowDate === selectedDate;
            const matchesService = serviceFilter === '' || rowService === serviceFilter;

            const isMatch = matchesDate && matchesService;
            row.style.display = isMatch ? '' : 'none';
            if (isMatch) visibleCount++;
        }

        const noResultsMsg = document.getElementById('noResultsMsg');
        if (noResultsMsg) {
            noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }


    updateDateLabel();
    updateTodayButtonState();
    filterVisitsTable();
</script>
@endsection
