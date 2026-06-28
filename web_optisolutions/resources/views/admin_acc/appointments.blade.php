<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>OptiSolutions - Appointments & Scheduling</title>
    
    @vite(['resources/css/admin_css/appointments.css', 'resources/css/admin_css/sidebar.css'])
</head>
<body>
    <!-- Header -->
    @include('admin_acc.header')

    <div class="container">
        <div class="page-header">
            
            <h2>
                <span><i class="bi bi-calendar2-plus"></i></span> 
                Appointments & Scheduling
            </h2>
            <p>Review, approve, and manage patient appointment requests organized by day</p>
        </div>
            @include('admin_acc.sidebar')
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="totalPending">0</div>
                <div class="stat-label">Pending Approval</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalApproved">0</div>
                <div class="stat-label">Approved Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalAppointments">0</div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalCompleted">0</div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <div class="filter-bar">
            <div class="filter-group">
                <select class="filter-select" id="statusFilter">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select class="filter-select" id="departmentFilter">
                    <option value="all">All Departments</option>
                    <option value="Ophthalmology">Ophthalmology</option>
                    <option value="Pediatrics">Pediatrics</option>
                    <option value="ENT">ENT</option>
                    <option value="Cardiology">Cardiology</option>
                    <option value="Dermatology">Dermatology</option>
                </select>
            </div>
            <div class="date-nav">
                <button class="date-nav-btn" id="prevWeekBtn">◀ Prev</button>
                <span class="current-date" id="currentWeekRange">May 18 - May 24, 2026</span>
                <button class="date-nav-btn" id="nextWeekBtn">Next ▶</button>
            </div>
        </div>

        <div class="appointments-container" id="appointmentsContainer"></div>
    </div>

    <div id="rescheduleModal" class="modal">
        <div class="modal-content">
            <h3>Reschedule Appointment</h3>
            <p id="modalPatientName"></p>
            <label>New Date:</label>
            <input type="date" id="newDate">
            <div class="modal-buttons">
                <button onclick="closeModal()">Cancel</button>
                <button class="btn-approve" onclick="confirmReschedule()">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        let appointmentsData = [
            { id: 1, patientName: "Maria Santos", department: "Ophthalmology", doctor: "Dr. Reyes", date: "2026-05-22", status: "pending", phone: "09123456789", concern: "Cataract consultation" },
            { id: 2, patientName: "John Dela Cruz", department: "Pediatrics", doctor: "Dr. Mendoza", date: "2026-05-22", status: "approved", phone: "09234567890", concern: "Child fever follow-up" },
            { id: 3, patientName: "Anna Rivera", department: "ENT", doctor: "Dr. Garcia", date: "2026-05-22", status: "pending", phone: "09345678901", concern: "Tinnitus evaluation" },
            { id: 4, patientName: "Carlos Gomez", department: "Cardiology", doctor: "Dr. Santos", date: "2026-05-23", status: "approved", phone: "09456789012", concern: "Blood pressure check" },
            { id: 5, patientName: "Elena Garcia", department: "Dermatology", doctor: "Dr. Lopez", date: "2026-05-23", status: "pending", phone: "09567890123", concern: "Skin rash" },
            { id: 6, patientName: "Roberto Javier", department: "Ophthalmology", doctor: "Dr. Reyes", date: "2026-05-23", status: "approved", phone: "09678901234", concern: "Eye exam" },
            { id: 7, patientName: "Sofia Villanueva", department: "Pediatrics", doctor: "Dr. Mendoza", date: "2026-05-24", status: "pending", phone: "09789012345", concern: "Vaccination" },
            { id: 8, patientName: "Luis Martinez", department: "ENT", doctor: "Dr. Garcia", date: "2026-05-24", status: "completed", phone: "09890123456", concern: "Ear infection follow-up" },
            { id: 9, patientName: "Patricia Cruz", department: "Cardiology", doctor: "Dr. Santos", date: "2026-05-25", status: "pending", phone: "09901234567", concern: "Chest pain" },
            { id: 10, patientName: "Miguel Tan", department: "Dermatology", doctor: "Dr. Lopez", date: "2026-05-25", status: "approved", phone: "09012345678", concern: "Acne treatment" },
            { id: 11, patientName: "Isabel Flores", department: "Ophthalmology", doctor: "Dr. Reyes", date: "2026-05-26", status: "pending", phone: "09111223344", concern: "Blurred vision" },
            { id: 12, patientName: "Ricardo Lopez", department: "Pediatrics", doctor: "Dr. Mendoza", date: "2026-05-26", status: "approved", phone: "09222334455", concern: "Growth check" },
        ];

        let currentWeekOffset = 0;
        let pendingRescheduleId = null;

        function getWeekRange(offset) {
            const today = new Date();
            today.setDate(today.getDate() + (offset * 7));
            const day = today.getDay();
            const diffToMonday = day === 0 ? -6 : 1 - day;
            const monday = new Date(today);
            monday.setDate(today.getDate() + diffToMonday);
            const sunday = new Date(monday);
            sunday.setDate(monday.getDate() + 6);
            const formatDate = (date) => {
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            };
            return { start: monday, end: sunday, label: `${formatDate(monday)} - ${formatDate(sunday)}, ${sunday.getFullYear()}` };
        }

        function updateWeekRange() {
            const range = getWeekRange(currentWeekOffset);
            document.getElementById('currentWeekRange').innerText = range.label;
            renderAppointments();
        }

        function groupByDate(appointments) {
            const groups = {};
            appointments.forEach(apt => {
                if (!groups[apt.date]) groups[apt.date] = [];
                groups[apt.date].push(apt);
            });
            return groups;
        }

        function getFilteredAppointments() {
            const statusFilter = document.getElementById('statusFilter').value;
            const deptFilter = document.getElementById('departmentFilter').value;
            const weekRange = getWeekRange(currentWeekOffset);
            let filtered = appointmentsData.filter(apt => {
                const aptDate = new Date(apt.date);
                const withinWeek = aptDate >= weekRange.start && aptDate <= weekRange.end;
                const statusMatch = statusFilter === 'all' || apt.status === statusFilter;
                const deptMatch = deptFilter === 'all' || apt.department === deptFilter;
                return withinWeek && statusMatch && deptMatch;
            });
            filtered.sort((a,b) => new Date(a.date) - new Date(b.date));
            return filtered;
        }

        function updateStats() {
            const allInWeek = appointmentsData.filter(apt => {
                const weekRange = getWeekRange(currentWeekOffset);
                const aptDate = new Date(apt.date);
                return aptDate >= weekRange.start && aptDate <= weekRange.end;
            });
            const pending = allInWeek.filter(a => a.status === 'pending').length;
            const approved = allInWeek.filter(a => a.status === 'approved').length;
            const completed = allInWeek.filter(a => a.status === 'completed').length;
            document.getElementById('totalPending').innerText = pending;
            document.getElementById('totalApproved').innerText = approved;
            document.getElementById('totalAppointments').innerText = allInWeek.length;
            document.getElementById('totalCompleted').innerText = completed;
        }

        function approveAppointment(id) {
            const apt = appointmentsData.find(a => a.id === id);
            if (apt && apt.status === 'pending') {
                apt.status = 'approved';
                renderAppointments();
                alert(`Appointment for ${apt.patientName} has been approved.`);
            }
        }

        function cancelAppointment(id) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                const apt = appointmentsData.find(a => a.id === id);
                if (apt) {
                    apt.status = 'cancelled';
                    renderAppointments();
                    alert(`Appointment for ${apt.patientName} has been cancelled.`);
                }
            }
        }

        function completeAppointment(id) {
            const apt = appointmentsData.find(a => a.id === id);
            if (apt && apt.status === 'approved') {
                apt.status = 'completed';
                renderAppointments();
                alert(`Appointment for ${apt.patientName} marked as completed.`);
            }
        }

        function openRescheduleModal(id) {
            const apt = appointmentsData.find(a => a.id === id);
            if (apt) {
                pendingRescheduleId = id;
                document.getElementById('modalPatientName').innerText = `${apt.patientName} - Current: ${apt.date}`;
                document.getElementById('newDate').value = apt.date;
                document.getElementById('rescheduleModal').style.display = 'flex';
            }
        }

        function confirmReschedule() {
            if (pendingRescheduleId) {
                const apt = appointmentsData.find(a => a.id === pendingRescheduleId);
                if (apt) {
                    const newDate = document.getElementById('newDate').value;
                    apt.date = newDate;
                    apt.status = 'pending';
                    renderAppointments();
                    alert(`Appointment rescheduled to ${newDate}. Status set to pending for re-approval.`);
                }
                closeModal();
            }
        }

        function closeModal() {
            document.getElementById('rescheduleModal').style.display = 'none';
            pendingRescheduleId = null;
        }

        function renderAppointments() {
            const filtered = getFilteredAppointments();
            const grouped = groupByDate(filtered);
            const sortedDates = Object.keys(grouped).sort();
            const container = document.getElementById('appointmentsContainer');
            container.innerHTML = '';
            if (sortedDates.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 3rem; background: white; border-radius: 20px;">No appointments found for this period.</div>';
                updateStats();
                return;
            }
            sortedDates.forEach(date => {
                const appointments = grouped[date];
                const formattedDate = new Date(date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                const pendingCount = appointments.filter(a => a.status === 'pending').length;
                const dayGroup = document.createElement('div');
                dayGroup.className = 'day-group';
                dayGroup.innerHTML = `
                    <div class="day-header" data-date="${date}">
                        <div class="day-title">
                            <span><i class="bi bi-calendar-week"></i></span>
                            <span>${formattedDate}</span>
                            <span class="appointment-count">${appointments.length} appointments</span>
                            ${pendingCount > 0 ? `<span class="appointment-count" style="background: #e67e22;">${pendingCount} pending</span>` : ''}
                        </div>
                        <div class="toggle-icon">▼</div>
                    </div>
                    <div class="day-content" data-date-content="${date}">
                        <table class="appointments-table">
                            <thead><tr><th>Patient</th><th>Department</th><th>Doctor</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                ${appointments.map(apt => `
                                    <tr>
                                        <td><strong>${apt.patientName}</strong><br><small style="color:#7f8c8d">${apt.phone}</small><br><small>${apt.concern.substring(0,30)}</small></td>
                                        <td>${apt.department}</td>
                                        <td>${apt.doctor}</td>
                                        <td><span class="status-badge status-${apt.status}">${apt.status.toUpperCase()}</span></td>
                                        <td class="action-buttons">
                                            ${apt.status === 'pending' ? `<button class="btn-sm btn-approve" onclick="approveAppointment(${apt.id})">✓ Approve</button>` : ''}
                                            ${apt.status === 'approved' ? `<button class="btn-sm btn-approve" onclick="completeAppointment(${apt.id})">✔ Complete</button>` : ''}
                                            <button class="btn-sm btn-reschedule" onclick="openRescheduleModal(${apt.id})"><i class="fa-regular fa-calendar-xmark"></i> Reschedule</button>
                                            ${apt.status !== 'cancelled' ? `<button class="btn-sm btn-cancel" onclick="cancelAppointment(${apt.id})">✖ Cancel</button>` : ''}
                                            <button class="btn-sm btn-view" onclick="alert('View details for ${apt.patientName}')">👁 View</button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                container.appendChild(dayGroup);
                const header = dayGroup.querySelector('.day-header');
                const content = dayGroup.querySelector('.day-content');
                header.addEventListener('click', () => {
                    content.classList.toggle('collapsed');
                    const icon = header.querySelector('.toggle-icon');
                    icon.style.transform = content.classList.contains('collapsed') ? 'rotate(-90deg)' : 'rotate(0deg)';
                });
            });
            updateStats();
        }

        document.getElementById('statusFilter').addEventListener('change', () => renderAppointments());
        document.getElementById('departmentFilter').addEventListener('change', () => renderAppointments());
        document.getElementById('prevWeekBtn').addEventListener('click', () => { currentWeekOffset--; updateWeekRange(); });
        document.getElementById('nextWeekBtn').addEventListener('click', () => { currentWeekOffset++; updateWeekRange(); });
        
      
        
        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                alert('Logging out... Redirecting to login page.');
            }
        }
        
        updateWeekRange();
        
        window.onclick = function(event) {
            const modal = document.getElementById('rescheduleModal');
            if (event.target === modal) closeModal();
        }
    </script>
</body>
</html>