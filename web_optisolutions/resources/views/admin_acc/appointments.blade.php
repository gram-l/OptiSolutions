<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <title>OptiSolutions - Scheduled Visits</title>

    @vite(['resources/css/admin_css/appointments.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css', 'resources/css/admin_css/feedback.css'])

    <style>
        /* ── Day nav row ── */
        .day-nav {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .day-nav-btn {
            background: #fff;
            border: 1px solid #d7dce3;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.95rem;
            color: #062744;
        }
        .day-nav-btn:hover { background: #f0f4f9; }
        .day-nav-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .day-nav-btn:disabled:hover { background: #fff; }
        .day-picker {
            border: 1px solid #d7dce3;
            border-radius: 8px;
            padding: 0.45rem 0.6rem;
            font-size: 0.85rem;
        }
        .today-btn {
            background: #eef2f7;
            border: 1px solid #d7dce3;
            border-radius: 8px;
            padding: 0.45rem 0.75rem;
            font-size: 0.8rem;
            cursor: pointer;
            color: #062744;
        }

        /* ── Day table (no more collapsible per-date groups; it's one day) ── */
        .day-panel {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px var(--shadow);
        }
        .field-error { color: #c0392b; font-size: 0.75rem; margin-top: 0.25rem; }

        /* ── Row action buttons — soft pill style ── */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .btn-sm {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border: none;
            border-radius: 999px;
            padding: 0.42rem 0.9rem;
            font-size: 0.78rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.15s ease, transform 0.1s ease;
        }
        .btn-sm:hover { transform: translateY(-1px); }
        .btn-sm:active { transform: translateY(0); }
        .btn-sm i { font-size: 0.82rem; }

        .btn-edit {
            background: #eaf2fb;
            color: #0E62AA;
        }
        .btn-edit:hover { background: #d9e9f8; }

        .btn-reschedule {
            background: var(--light-gray);
            color: #4a5560;
        }
        .btn-reschedule:hover { background: #dfe4e8; }

        .btn-cancel {
            background: #fdeceb;
            color: #c0392b;
        }
        .btn-cancel:hover { background: #fbdbd8; }

        #editNotesModal textarea {
            width: 100%;
            min-height: 110px;
            resize: vertical;
            border: 1px solid #d7dce3;
            border-radius: 8px;
            padding: 0.6rem 0.75rem;
            font-family: inherit;
            font-size: 0.9rem;
            box-sizing: border-box;
            margin-top: 0.3rem;
        }
        #editNotesModal textarea:focus { outline: none; border-color: #0E62AA; }

        /* ── Download button + dropdown ── */
        .download-dropdown {
            position: relative;
            display: inline-block;
        }
        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--primary-main);
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            font-family: inherit;
            transition: background 0.2s ease, transform 0.1s ease;
        }
        .download-btn:hover { background: #0b4f8a; }
        .download-btn .chevron { font-size: 0.65rem; transition: transform 0.2s ease; }
        .download-dropdown.open .download-btn .chevron { transform: rotate(180deg); }
        .download-menu {
            display: none;
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 0;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 8px 24px var(--shadow);
            overflow: hidden;
            min-width: 170px;
            z-index: 50;
        }
        .download-dropdown.open .download-menu { display: block; }
        .download-menu button {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            width: 100%;
            padding: 0.65rem 1rem;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
            font-size: 0.85rem;
            font-family: inherit;
            color: var(--text-dark);
            transition: background 0.15s ease;
        }
        .download-menu button:hover { background: var(--light-gray); }
        .download-menu button i { width: 16px; color: var(--primary-main); }
    </style>
</head>
<body>
    <!-- Header -->
    @include('admin_acc.header')

    <div class="container">
           @include('admin_acc.sidebar')
        <div style="flex: 1; min-width: 0;">
        <div class="page-header">
            <h2>
                <span><i class="bi bi-calendar2-plus"></i></span>
                Scheduled Visits
            </h2>
            <p>Review and manage scheduled patient visits, one day at a time</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="totalAppointments">0</div>
                <div class="stat-label">Scheduled Visits Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalPatients">0</div>
                <div class="stat-label">Patients Scheduled</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalDoctors">0</div>
                <div class="stat-label">Doctors Involved</div>
            </div>
        </div>

        <div class="filter-bar">
            <div class="filter-group">
                <select class="filter-select" id="serviceFilter">
                    <option value="all">All Services</option>
                </select>
            </div>
            <div class="day-nav">
                <button class="day-nav-btn" id="prevDayBtn" title="Previous day">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <input type="date" class="day-picker" id="dayPicker">
                <button class="day-nav-btn" id="nextDayBtn" title="Next day">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
                <button class="today-btn" id="todayBtn">Today</button>
                <div class="download-dropdown" id="downloadDropdown">
                    <button type="button" class="download-btn" id="downloadBtn">
                        <i class="fa-solid fa-download"></i> Download <i class="fa-solid fa-chevron-down chevron"></i>
                    </button>
                    <div class="download-menu">
                        <button type="button" onclick="exportPDF()"><i class="fa-regular fa-file-pdf"></i> PDF</button>
                        <button type="button" onclick="exportCSV()"><i class="fa-regular fa-file-lines"></i> CSV</button>
                        <button type="button" onclick="exportExcel()"><i class="fa-regular fa-file-excel"></i> Excel</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="appointments-container" id="appointmentsContainer"></div>
        </div>
    </div>

    <div id="rescheduleModal" class="modal">
        <div class="modal-content">
            <h3>Reschedule Appointment</h3>
            <p id="modalPatientName"></p>
            <label>New Date:</label>
            <input type="date" id="newDate">
            <div class="field-error" id="err-reschedule"></div>
            <div class="modal-buttons">
                <button onclick="closeModal()">Cancel</button>
                <button class="btn-approve" id="confirmRescheduleBtn" onclick="confirmReschedule()">Confirm</button>
            </div>
        </div>
    </div>

    <div id="editNotesModal" class="modal">
        <div class="modal-content">
            <h3>Edit Notes</h3>
            <p id="editNotesPatientName"></p>
            <label>Notes:</label>
            <textarea id="editNotesText" placeholder="Add notes for this visit..." maxlength="1000"></textarea>
            <div class="field-error" id="err-edit-notes"></div>
            <div class="modal-buttons">
                <button onclick="closeEditNotesModal()">Cancel</button>
                <button class="btn-approve" id="confirmEditNotesBtn" onclick="confirmEditNotes()">Save</button>
            </div>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const DAY_URL = '/admin_acc/appointments/day';
        const VISIT_BASE = '/admin_acc/appointments';

        let visitsData = [];

        // Formats a Date object as a local YYYY-MM-DD string. Deliberately
        // avoids toISOString() here — that converts to UTC first, which in
        // a UTC+8 timezone silently cancelled out the "next day" shift
        // (and skipped a day on "previous"), since local midnight is
        // already the previous day in UTC.
        function toLocalDateStr(d) {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function todayStr() {
            return toLocalDateStr(new Date());
        }

        function shiftDate(dateStr, days) {
            const d = new Date(dateStr + 'T00:00:00');
            d.setDate(d.getDate() + days);
            return toLocalDateStr(d);
        }

        let currentDate = todayStr();
        let pendingRescheduleId = null;
        let pendingEditNotesId = null;

        // How many days to search in one direction before giving up (covers
        // roughly a year either way — enough for any real gap in the
        // schedule without hammering the server indefinitely).
        const MAX_DAY_SEARCH = 365;

        async function hasVisitsForDate(dateStr) {
            try {
                const res = await fetch(`${DAY_URL}?date=${dateStr}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                return !!(data.success && Array.isArray(data.visits) && data.visits.length > 0);
            } catch (e) {
                return false;
            }
        }

        // Moves the day-nav in `direction` (-1 or 1), skipping over any day
        // that has no scheduled visits, and loads the first day it finds
        // that does.
        async function goToDayWithVisits(direction) {
            const prevBtn = document.getElementById('prevDayBtn');
            const nextBtn = document.getElementById('nextDayBtn');
            const container = document.getElementById('appointmentsContainer');

            prevBtn.disabled = true;
            nextBtn.disabled = true;
            container.innerHTML = '<div style="text-align:center;padding:3rem;background:#fff;border-radius:16px;">Searching...</div>';

            let candidate = currentDate;
            let found = null;

            for (let i = 0; i < MAX_DAY_SEARCH; i++) {
                candidate = shiftDate(candidate, direction);
                if (await hasVisitsForDate(candidate)) {
                    found = candidate;
                    break;
                }
            }

            prevBtn.disabled = false;
            nextBtn.disabled = false;

            if (found) {
                currentDate = found;
                loadDay();
            } else {
                container.innerHTML = `<div style="text-align:center;padding:3rem;background:#fff;border-radius:16px;">No ${direction > 0 ? 'upcoming' : 'earlier'} days with scheduled visits found.</div>`;
            }
        }

        function formatDayLabel(dateStr) {
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }

        function formatBookedAt(ts) {
            if (!ts) return '';
            const d = new Date(ts.replace(' ', 'T'));
            if (isNaN(d)) return ts;
            return d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
        }

        async function loadDay() {
            document.getElementById('dayPicker').value = currentDate;

            const container = document.getElementById('appointmentsContainer');
            container.innerHTML = '<div style="text-align:center;padding:3rem;background:#fff;border-radius:16px;">Loading...</div>';

            try {
                const res = await fetch(`${DAY_URL}?date=${currentDate}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (!data.success) {
                    container.innerHTML = `<div style="text-align:center;padding:3rem;background:#fff;border-radius:16px;">${escapeHtml(data.message || 'Failed to load appointments.')}</div>`;
                    return;
                }
                visitsData = data.visits;
                populateServiceFilter();
                renderDay();
            } catch (e) {
                container.innerHTML = '<div style="text-align:center;padding:3rem;background:#fff;border-radius:16px;">Could not reach the server. Please try again.</div>';
            }
        }

        function populateServiceFilter() {
            const select = document.getElementById('serviceFilter');
            const current = select.value;
            const services = [...new Set(visitsData.map(v => v.service_type).filter(Boolean))].sort();
            select.innerHTML = '<option value="all">All Services</option>' +
                services.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');
            if (services.includes(current)) select.value = current;
        }

        function getFiltered() {
            const serviceFilter = document.getElementById('serviceFilter').value;
            return visitsData.filter(v => serviceFilter === 'all' || v.service_type === serviceFilter);
        }

        function updateStats(filtered) {
            document.getElementById('totalAppointments').innerText = filtered.length;
            document.getElementById('totalPatients').innerText = new Set(filtered.map(v => v.patient_name)).size;
            document.getElementById('totalDoctors').innerText = new Set(filtered.map(v => v.doctor_name).filter(Boolean)).size;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function renderDay() {
            const filtered = getFiltered();
            updateStats(filtered);

            const container = document.getElementById('appointmentsContainer');

            if (filtered.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 3rem; background: white; border-radius: 20px;">No scheduled visit found for this day.</div>';
                return;
            }

            container.innerHTML = `
                <div class="day-panel">
                    <table class="appointments-table">
                        <thead><tr><th>Patient</th><th>Doctor</th><th>Service</th><th>Scheduled At</th><th>Notes</th><th> </th></tr></thead>
                        <tbody>
                            ${filtered.map(v => `
                                <tr>
                                    <td><strong>${escapeHtml(v.patient_name || 'Unknown patient')}</strong></td>
                                    <td>${escapeHtml(v.doctor_name || '—')}</td>
                                    <td>${escapeHtml(v.service_type || '—')}</td>
                                    <td><small style="color:#7f8c8d">${formatBookedAt(v.scheduled_at)}</small></td>
                                    <td>${escapeHtml((v.notes || '').length > 40 ? v.notes.substring(0, 40) + '...' : (v.notes || '—'))}</td>
                                    <td class="action-buttons">
                                        <button class="btn-sm btn-edit" onclick="openEditNotesModal(${v.visit_id})"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
                                        <button class="btn-sm btn-reschedule" onclick="openRescheduleModal(${v.visit_id})"><i class="fa-regular fa-calendar-xmark"></i> Reschedule</button>
                                        <button class="btn-sm btn-cancel" onclick="cancelAppointment(${v.visit_id})"><i class="fa-solid fa-trash"></i> Remove</button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function openRescheduleModal(id) {
            const visit = visitsData.find(v => v.visit_id === id);
            if (!visit) return;
            pendingRescheduleId = id;
            document.getElementById('err-reschedule').textContent = '';
            document.getElementById('modalPatientName').innerText = `${visit.patient_name || 'Unknown patient'} — Current: ${visit.visit_date}`;
            document.getElementById('newDate').value = visit.visit_date;
            document.getElementById('rescheduleModal').style.display = 'flex';
        }

        async function confirmReschedule() {
            if (!pendingRescheduleId) return;
            const newDate = document.getElementById('newDate').value;
            const errEl = document.getElementById('err-reschedule');
            errEl.textContent = '';

            if (!newDate) {
                errEl.textContent = 'Pick a date.';
                return;
            }

            const btn = document.getElementById('confirmRescheduleBtn');
            btn.disabled = true;
            btn.textContent = 'Saving...';

            try {
                const res = await fetch(`${VISIT_BASE}/${pendingRescheduleId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ visit_date: newDate }),
                });
                const data = await res.json();

                if (!data.success) {
                    errEl.textContent = data.message || 'Could not reschedule this appointment.';
                    return;
                }

                closeModal();
                await loadDay(); // the visit may have moved off the currently viewed day
            } catch (e) {
                errEl.textContent = 'Could not reach the server. Please try again.';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Confirm';
            }
        }

        async function cancelAppointment(id) {
            const visit = visitsData.find(v => v.visit_id === id);
            if (!visit) return;

            if (!confirm(`Remove the appointment for ${visit.patient_name || 'this patient'}? This cannot be undone.`)) {
                return;
            }

            try {
                const res = await fetch(`${VISIT_BASE}/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    await loadDay();
                } else {
                    alert(data.message || 'Could not remove this appointment.');
                }
            } catch (e) {
                alert('Could not reach the server. Please try again.');
            }
        }

        function closeModal() {
            document.getElementById('rescheduleModal').style.display = 'none';
            pendingRescheduleId = null;
        }

        function openEditNotesModal(id) {
            const visit = visitsData.find(v => v.visit_id === id);
            if (!visit) return;
            pendingEditNotesId = id;
            document.getElementById('err-edit-notes').textContent = '';
            document.getElementById('editNotesPatientName').innerText = `${visit.patient_name || 'Unknown patient'} — ${visit.visit_date}`;
            document.getElementById('editNotesText').value = visit.notes || '';
            document.getElementById('editNotesModal').style.display = 'flex';
        }

        async function confirmEditNotes() {
            if (!pendingEditNotesId) return;
            const newNotes = document.getElementById('editNotesText').value;
            const errEl = document.getElementById('err-edit-notes');
            errEl.textContent = '';

            const btn = document.getElementById('confirmEditNotesBtn');
            btn.disabled = true;
            btn.textContent = 'Saving...';

            try {
                const res = await fetch(`${VISIT_BASE}/${pendingEditNotesId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ notes: newNotes }),
                });
                const data = await res.json();

                if (!data.success) {
                    errEl.textContent = data.message || 'Could not save these notes.';
                    return;
                }

                closeEditNotesModal();
                await loadDay();
            } catch (e) {
                errEl.textContent = 'Could not reach the server. Please try again.';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Save';
            }
        }

        function closeEditNotesModal() {
            document.getElementById('editNotesModal').style.display = 'none';
            pendingEditNotesId = null;
        }

        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                alert('Logging out... Redirecting to login page.');
            }
        }

        /* ── Download dropdown ── */
        function toggleDownloadMenu() {
            document.getElementById('downloadDropdown').classList.toggle('open');
        }

        function getExportRows() {
            return getFiltered().map(v => ({
                Patient: v.patient_name || 'Unknown patient',
                Doctor: v.doctor_name || '—',
                Service: v.service_type || '—',
                'Scheduled At': formatBookedAt(v.scheduled_at),
                Notes: v.notes || '',
            }));
        }

        function downloadBlob(blob, filename) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function exportCSV() {
            const rows = getExportRows();
            if (rows.length === 0) { alert('No appointments to export for this day.'); return; }
            const headers = Object.keys(rows[0]);
            const csvLines = [
                headers.join(','),
                ...rows.map(r => headers.map(h => `"${String(r[h]).replace(/"/g, '""')}"`).join(','))
            ];
            const blob = new Blob(["\ufeff" + csvLines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            downloadBlob(blob, `appointments_${currentDate}.csv`);
            toggleDownloadMenu();
        }

        function exportExcel() {
            const rows = getExportRows();
            if (rows.length === 0) { alert('No appointments to export for this day.'); return; }
            const ws = XLSX.utils.json_to_sheet(rows);
            ws['!cols'] = [{ wch: 22 }, { wch: 20 }, { wch: 18 }, { wch: 20 }, { wch: 40 }];
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Appointments');
            XLSX.writeFile(wb, `appointments_${currentDate}.xlsx`);
            toggleDownloadMenu();
        }

        function exportPDF() {
            const rows = getExportRows();
            if (rows.length === 0) { alert('No appointments to export for this day.'); return; }
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.setFontSize(14);
            doc.text(`Scheduled Visits - ${formatDayLabel(currentDate)}`, 14, 15);
            doc.autoTable({
                startY: 22,
                head: [['Patient', 'Doctor', 'Service', 'Scheduled At', 'Notes']],
                body: rows.map(r => [r.Patient, r.Doctor, r.Service, r['Scheduled At'], r.Notes]),
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [14, 98, 170] },
                columnStyles: { 4: { cellWidth: 60 } },
            });
            doc.save(`appointments_${currentDate}.pdf`);
            toggleDownloadMenu();
        }

        document.getElementById('downloadBtn').addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDownloadMenu();
        });
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('downloadDropdown');
            if (dropdown.classList.contains('open') && !dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });

        document.getElementById('serviceFilter').addEventListener('change', () => renderDay());
        document.getElementById('prevDayBtn').addEventListener('click', () => goToDayWithVisits(-1));
        document.getElementById('nextDayBtn').addEventListener('click', () => goToDayWithVisits(1));
        document.getElementById('todayBtn').addEventListener('click', () => { currentDate = todayStr(); loadDay(); });
        document.getElementById('dayPicker').addEventListener('change', (e) => {
            if (e.target.value) { currentDate = e.target.value; loadDay(); }
        });

        window.onclick = function(event) {
            const rescheduleModal = document.getElementById('rescheduleModal');
            const editNotesModal = document.getElementById('editNotesModal');
            if (event.target === rescheduleModal) closeModal();
            if (event.target === editNotesModal) closeEditNotesModal();
        }

        loadDay();
    </script>
</body>
</html>