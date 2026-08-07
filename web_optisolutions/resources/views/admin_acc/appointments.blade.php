<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
        .current-day-label {
            font-weight: 600;
            color: #062744;
            margin-left: 0.25rem;
        }

        /* ── Day table (no more collapsible per-date groups; it's one day) ── */
        .day-panel {
            background: #fff;
            border-radius: 16px;
            padding: 1.25rem;
        }
        .field-error { color: #c0392b; font-size: 0.75rem; margin-top: 0.25rem; }
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
                <span class="current-day-label" id="currentDayLabel"></span>
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

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const DAY_URL = '/admin_acc/appointments/day';
        const VISIT_BASE = '/admin_acc/appointments';

        let visitsData = [];
        let currentDate = new Date().toISOString().slice(0, 10); // YYYY-MM-DD, local-ish
        let pendingRescheduleId = null;

        function shiftDate(dateStr, days) {
            const d = new Date(dateStr + 'T00:00:00');
            d.setDate(d.getDate() + days);
            return d.toISOString().slice(0, 10);
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
            document.getElementById('currentDayLabel').innerText = formatDayLabel(currentDate);

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
                container.innerHTML = '<div style="text-align: center; padding: 3rem; background: white; border-radius: 20px;">No appointments found for this day.</div>';
                return;
            }

            container.innerHTML = `
                <div class="day-panel">
                    <table class="appointments-table">
                        <thead><tr><th>Patient</th><th>Doctor</th><th>Service</th><th>Booked At</th><th>Notes</th><th>Actions</th></tr></thead>
                        <tbody>
                            ${filtered.map(v => `
                                <tr>
                                    <td><strong>${escapeHtml(v.patient_name || 'Unknown patient')}</strong></td>
                                    <td>${escapeHtml(v.doctor_name || '—')}</td>
                                    <td>${escapeHtml(v.service_type || '—')}</td>
                                    <td><small style="color:#7f8c8d">${formatBookedAt(v.scheduled_at)}</small></td>
                                    <td>${escapeHtml((v.notes || '').length > 40 ? v.notes.substring(0, 40) + '...' : (v.notes || '—'))}</td>
                                    <td class="action-buttons">
                                        <button class="btn-sm btn-reschedule" onclick="openRescheduleModal(${v.visit_id})"><i class="fa-regular fa-calendar-xmark"></i> Reschedule</button>
                                        <button class="btn-sm btn-cancel" onclick="cancelAppointment(${v.visit_id})">✖ Remove</button>
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

        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                alert('Logging out... Redirecting to login page.');
            }
        }

        document.getElementById('serviceFilter').addEventListener('change', () => renderDay());
        document.getElementById('prevDayBtn').addEventListener('click', () => { currentDate = shiftDate(currentDate, -1); loadDay(); });
        document.getElementById('nextDayBtn').addEventListener('click', () => { currentDate = shiftDate(currentDate, 1); loadDay(); });
        document.getElementById('todayBtn').addEventListener('click', () => { currentDate = new Date().toISOString().slice(0, 10); loadDay(); });
        document.getElementById('dayPicker').addEventListener('change', (e) => {
            if (e.target.value) { currentDate = e.target.value; loadDay(); }
        });

        window.onclick = function(event) {
            const modal = document.getElementById('rescheduleModal');
            if (event.target === modal) closeModal();
        }

        loadDay();
    </script>
</body>
</html>