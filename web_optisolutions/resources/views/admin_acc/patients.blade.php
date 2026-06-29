<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Font Awesome -->
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>OptiSolutions - Patient Records</title>
@vite(['resources/css/admin_css/patients.css', 'resources/css/admin_css/sidebar.css'])
</head>
<body>
    <!-- Header -->
     @include('admin_acc.header')
    

    <!-- Main Container -->
    <div class="container">
        @include('admin_acc.sidebar')
        <div class="page-header">
            <h2>
                <span><i class="fa-regular fa-hospital"></i></span> 
                Patient Records
            </h2>
            <p>View and manage all registered patients, their medical history, and assigned doctors</p>
            
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-number" id="totalPatients">0</div>
                <div class="stat-label">Total Patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="activePatients">0</div>
                <div class="stat-label">Active Patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="newThisMonth">0</div>
                <div class="stat-label">New This Month</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="departmentsCount">0</div>
                <div class="stat-label">Departments</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <input type="text" class="search-box" id="searchInput" placeholder="Search by name, ID, or doctor...">
                <select class="filter-select" id="departmentFilter">
                    <option value="all">All Departments</option>
                    <option value="Ophthalmology">Ophthalmology</option>
                    <option value="Pediatrics">Pediatrics</option>
                    <option value="ENT">ENT</option>
                    <option value="Cardiology">Cardiology</option>
                    <option value="Dermatology">Dermatology</option>
                    <option value="Orthopedics">Orthopedics</option>
                </select>
                <select class="filter-select" id="statusFilter">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button class="add-patient-btn" id="openAddModalBtn">
                <span></span>
            </button>
        </div>

        <!-- Patients Table -->
        <div class="patients-table-container">
            <table class="patients-table" id="patientsTable">
                <thead>
                    <tr>
                        <th onclick="sortTable('name')">Patient Name <span class="sort-indicator" id="sort-name"><i class="fa-solid fa-sort"></i></span></th>
                        <th onclick="sortTable('age')">Age <span class="sort-indicator" id="sort-age"><i class="fa-solid fa-sort"></i></span></th>
                        <th onclick="sortTable('department')">Department <span class="sort-indicator" id="sort-dept"><i class="fa-solid fa-sort"></i></span></th>
                        <th onclick="sortTable('doctor')">Assigned Doctor <span class="sort-indicator" id="sort-doctor"><i class="fa-solid fa-sort"></i></span></th>
                        <th>Patient ID</th>
                        <th>Status</th>
                        <th> </th>
                    </tr>
                </thead>
                <tbody id="patientsTableBody">
                    <!-- Dynamic content -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Patient Modal -->
    <div id="patientModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add New Patient</h3>
            <form id="patientForm">
                <input type="hidden" id="patientId" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" id="patientName" required placeholder="e.g., Maria Santos">
                    </div>
                    <div class="form-group">
                        <label>Age *</label>
                        <input type="number" id="patientAge" required placeholder="e.g., 35">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Department *</label>
                        <select id="patientDepartment" required>
                            <option value="">Select Department</option>
                            <option>Ophthalmology</option>
                            <option>Pediatrics</option>
                            <option>ENT</option>
                            <option>Cardiology</option>
                            <option>Dermatology</option>
                            <option>Orthopedics</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Assigned Doctor *</label>
                        <select id="patientDoctor" required>
                            <option value="">Select Doctor</option>
                            <option>Dr. Maria Reyes</option>
                            <option>Dr. Jose Mendoza</option>
                            <option>Dr. Anna Garcia</option>
                            <option>Dr. Carlos Santos</option>
                            <option>Dr. Elena Lopez</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" id="patientPhone" placeholder="e.g., 09123456789">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="patientStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Medical Notes / Conditions</label>
                    <textarea id="patientNotes" rows="2" placeholder="Allergies, chronic conditions, medications..."></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Patient</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Patient Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <h3>Patient Details</h3>
            <div id="viewDetails">
                <!-- Dynamic content -->
            </div>
            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Mock patient data
        let patientsData = [
            { id: "P-1001", name: "Maria Santos", age: 34, department: "Ophthalmology", doctor: "Dr. Maria Reyes", phone: "09123456789", status: "active", notes: "Cataract surgery scheduled for June 15. No known allergies." },
            { id: "P-1002", name: "John Dela Cruz", age: 5, department: "Pediatrics", doctor: "Dr. Jose Mendoza", phone: "09234567890", status: "active", notes: "Routine vaccination. Mild fever last week." },
            { id: "P-1003", name: "Anna Rivera", age: 28, department: "ENT", doctor: "Dr. Anna Garcia", phone: "09345678901", status: "active", notes: "Chronic sinusitis. Prescribed antibiotics." },
            { id: "P-1004", name: "Carlos Gomez", age: 58, department: "Cardiology", doctor: "Dr. Carlos Santos", phone: "09456789012", status: "active", notes: "Hypertension. Regular blood pressure monitoring." },
            { id: "P-1005", name: "Elena Garcia", age: 42, department: "Dermatology", doctor: "Dr. Elena Lopez", phone: "09567890123", status: "inactive", notes: "Acne treatment completed. Follow-up in 3 months." },
            { id: "P-1006", name: "Roberto Javier", age: 67, department: "Ophthalmology", doctor: "Dr. Maria Reyes", phone: "09678901234", status: "active", notes: "Glaucoma. Uses eye drops daily." },
            { id: "P-1007", name: "Sofia Villanueva", age: 12, department: "Pediatrics", doctor: "Dr. Jose Mendoza", phone: "09789012345", status: "active", notes: "Asthma. Uses inhaler as needed." },
            { id: "P-1008", name: "Luis Martinez", age: 45, department: "ENT", doctor: "Dr. Anna Garcia", phone: "09890123456", status: "active", notes: "Hearing loss. Hearing aid recommended." },
            { id: "P-1009", name: "Patricia Cruz", age: 52, department: "Cardiology", doctor: "Dr. Carlos Santos", phone: "09901234567", status: "active", notes: "Post-heart attack recovery. Regular checkups." },
            { id: "P-1010", name: "Miguel Tan", age: 31, department: "Dermatology", doctor: "Dr. Elena Lopez", phone: "09012345678", status: "active", notes: "Eczema. Topical cream prescribed." }
        ];

        let nextPatientIdNum = 1011;
        let currentSort = { column: 'name', direction: 'asc' };

        // Helper to generate new patient ID
        function generatePatientId() {
            return `P-${nextPatientIdNum++}`;
        }

        // Calculate age from birth year (simplified)
        function calculateAge(birthYear) {
            return new Date().getFullYear() - birthYear;
        }

        // Render patients table
        function renderPatients() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const deptFilter = document.getElementById('departmentFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            
            let filtered = patientsData.filter(patient => {
                const matchesSearch = patient.name.toLowerCase().includes(searchTerm) || 
                                     patient.id.toLowerCase().includes(searchTerm) ||
                                     patient.doctor.toLowerCase().includes(searchTerm);
                const matchesDept = deptFilter === 'all' || patient.department === deptFilter;
                const matchesStatus = statusFilter === 'all' || patient.status === statusFilter;
                return matchesSearch && matchesDept && matchesStatus;
            });
            
            // Sort
            filtered.sort((a, b) => {
                let valA = a[currentSort.column];
                let valB = b[currentSort.column];
                if (currentSort.column === 'age') {
                    valA = Number(valA);
                    valB = Number(valB);
                } else {
                    valA = String(valA).toLowerCase();
                    valB = String(valB).toLowerCase();
                }
                if (valA < valB) return currentSort.direction === 'asc' ? -1 : 1;
                if (valA > valB) return currentSort.direction === 'asc' ? 1 : -1;
                return 0;
            });
            
            const tbody = document.getElementById('patientsTableBody');
            
            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No patients found. Click "Add New Patient" to create a record.</td></tr>';
            } else {
                tbody.innerHTML = filtered.map(patient => `
                    <tr>
                        <td style="font-weight: 500;">${escapeHtml(patient.name)}</td>
                        <td>${patient.age} years</td>
                        <td>${escapeHtml(patient.department)}</td>
                        <td>${escapeHtml(patient.doctor)}</td>
                        <td><code>${patient.id}</code></td>
                        <td><span class="status-badge status-${patient.status}">${patient.status === 'active' ? '● Active' : '○ Inactive'}</span></td>
                        <td class="action-buttons">
                            <button class="btn-icon btn-view" onclick="viewPatient('${patient.id}')"> <i class="fa-regular fa-eye"></i> View</button>
                            <button class="btn-icon btn-edit" onclick="editPatient('${patient.id}')"> <i class="fa-regular fa-edit"></i> Edit</button>
                            <button class="btn-icon btn-record" onclick="viewMedicalHistory('${patient.id}')"> <i class="fa-regular fa-file-medical"></i> Records</button>
                        </td>
                    </tr>
                `).join('');
            }
            
            // Update stats
            updateStats();
        }
        
        function updateStats() {
            const total = patientsData.length;
            const active = patientsData.filter(p => p.status === 'active').length;
            const currentMonth = new Date().getMonth();
            const newThisMonth = patientsData.filter(p => {
                // Mock: assume patients added in last 30 days (simulate)
                return p.id >= 'P-1006';
            }).length;
            const departments = [...new Set(patientsData.map(p => p.department))].length;
            
            document.getElementById('totalPatients').innerText = total;
            document.getElementById('activePatients').innerText = active;
            document.getElementById('newThisMonth').innerText = newThisMonth;
            document.getElementById('departmentsCount').innerText = departments;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Sorting
        function sortTable(column) {
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }
            
            // Update sort indicators
            ['name', 'age', 'department', 'doctor'].forEach(col => {
                const indicator = document.getElementById(`sort-${col === 'department' ? 'dept' : col}`);
                if (col === column) {
                    indicator.textContent = currentSort.direction === 'asc' ? '↑' : '↓';
                } else {
                    indicator.textContent = '↕';
                }
            });
            
            renderPatients();
        }
        
        // Open Add Modal
        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'Add New Patient';
            document.getElementById('patientForm').reset();
            document.getElementById('patientId').value = '';
            document.getElementById('patientStatus').value = 'active';
            document.getElementById('patientModal').style.display = 'flex';
        }
        
        // Edit Patient
        function editPatient(id) {
            const patient = patientsData.find(p => p.id === id);
            if (!patient) return;
            
            document.getElementById('modalTitle').innerText = 'Edit Patient';
            document.getElementById('patientId').value = patient.id;
            document.getElementById('patientName').value = patient.name;
            document.getElementById('patientAge').value = patient.age;
            document.getElementById('patientDepartment').value = patient.department;
            document.getElementById('patientDoctor').value = patient.doctor;
            document.getElementById('patientPhone').value = patient.phone || '';
            document.getElementById('patientStatus').value = patient.status;
            document.getElementById('patientNotes').value = patient.notes || '';
            document.getElementById('patientModal').style.display = 'flex';
        }
        
        // Save patient (add or edit)
        function savePatient(event) {
            event.preventDefault();
            
            const id = document.getElementById('patientId').value;
            const name = document.getElementById('patientName').value.trim();
            const age = parseInt(document.getElementById('patientAge').value);
            const department = document.getElementById('patientDepartment').value;
            const doctor = document.getElementById('patientDoctor').value;
            const phone = document.getElementById('patientPhone').value;
            const status = document.getElementById('patientStatus').value;
            const notes = document.getElementById('patientNotes').value;
            
            if (!name || !age || !department || !doctor) {
                alert('Please fill in all required fields.');
                return;
            }
            
            if (id) {
                // Edit existing
                const index = patientsData.findIndex(p => p.id === id);
                if (index !== -1) {
                    patientsData[index] = { ...patientsData[index], name, age, department, doctor, phone, status, notes };
                    alert(`Patient record for ${name} has been updated.`);
                }
            } else {
                // Add new
                const newPatient = {
                    id: generatePatientId(),
                    name: name,
                    age: age,
                    department: department,
                    doctor: doctor,
                    phone: phone || '',
                    status: status,
                    notes: notes || ''
                };
                patientsData.push(newPatient);
                alert(`New patient ${name} has been added with ID ${newPatient.id}.`);
            }
            
            closeModal();
            renderPatients();
        }
        
        // View patient details
        function viewPatient(id) {
            const patient = patientsData.find(p => p.id === id);
            if (!patient) return;
            
            const detailsHtml = `
                <div class="form-group"><strong>Patient ID:</strong> ${patient.id}</div>
                <div class="form-group"><strong>Full Name:</strong> ${escapeHtml(patient.name)}</div>
                <div class="form-group"><strong>Age:</strong> ${patient.age} years</div>
                <div class="form-group"><strong>Department:</strong> ${escapeHtml(patient.department)}</div>
                <div class="form-group"><strong>Assigned Doctor:</strong> ${escapeHtml(patient.doctor)}</div>
                <div class="form-group"><strong>Contact:</strong> ${patient.phone || 'Not provided'}</div>
                <div class="form-group"><strong>Status:</strong> <span class="status-badge status-${patient.status}">${patient.status}</span></div>
                <div class="form-group"><strong>Medical Notes:</strong><br>${patient.notes || 'No notes available.'}</div>
            `;
            document.getElementById('viewDetails').innerHTML = detailsHtml;
            document.getElementById('viewModal').style.display = 'flex';
        }
        
        function viewMedicalHistory(id) {
            const patient = patientsData.find(p => p.id === id);
            alert(`Medical records for ${patient?.name}\n\nThis would show consultation history, prescriptions, lab results, etc. (UI Demo)`);
        }
        
        function closeModal() {
            document.getElementById('patientModal').style.display = 'none';
        }
        
        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }
        
        
        
        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                alert('Logging out... Redirecting to login page.');
            }
        }
        
        // Event listeners
        document.getElementById('openAddModalBtn').addEventListener('click', openAddModal);
        document.getElementById('patientForm').addEventListener('submit', savePatient);
        document.getElementById('searchInput').addEventListener('input', () => renderPatients());
        document.getElementById('departmentFilter').addEventListener('change', () => renderPatients());
        document.getElementById('statusFilter').addEventListener('change', () => renderPatients());
        
        // Close modals on outside click
        window.onclick = function(event) {
            const modal1 = document.getElementById('patientModal');
            const modal2 = document.getElementById('viewModal');
            if (event.target === modal1) closeModal();
            if (event.target === modal2) closeViewModal();
        }
        
        // Initial render
        renderPatients();
    </script>
</body>
</html>