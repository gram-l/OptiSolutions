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
    <title>OptiSolutions - Manage Doctors</title>

    <!-- Vite CSS -->
    @vite(['resources/css/admin_css/doctors.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css'])
</head>
<body>
    <!-- Header -->
    @include('admin_acc.header')

    <!-- Main Container -->
    <div class="container">
        <!-- Sidebar Navigation -->
         @include('admin_acc.sidebar')
          <div style="flex: 1; min-width: 0;">
        <div class="page-header">
            <h2>
                <span><i class="fa-solid fa-user-doctor"></i></span> 
                Manage Doctors
            </h2>
            <p>Add, edit, or remove doctor profiles and manage their schedules</p>
            
        </div>

         <div class="action-bar">
           
            <div style="display: flex; gap: 1rem;">
                <input type="text" class="search-box" id="searchInput" placeholder="Search by name or specialty...">
                <select class="filter-select" id="statusFilter">
                    <option value="all">All Doctors</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>
            </div>
             <button class="add-doctor-btn" id="openAddModalBtn">
                <span><i class="fa-solid fa-plus"></i></span> Add New Doctor
            </button>
        </div>

        <!-- Doctors Grid -->
        <div class="doctors-grid" id="doctorsGrid">
            <!-- Dynamic content -->
        </div>
        </div>
    </div>

    <!-- Add/Edit Doctor Modal -->
    <div id="doctorModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add New Doctor</h3>
            <form id="doctorForm">
                <input type="hidden" id="doctorId" value="">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" id="docName" required placeholder="e.g., Dr. Maria Reyes">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Specialty *</label>
                        <input type="text" id="docSpecialty" required placeholder="e.g., Ophthalmology">
                    </div>
                    <div class="form-group">
                        <label>Schedule *</label>
                        <input type="text" id="docSchedule" required placeholder="e.g., Mon/Wed/Fri 9AM-5PM">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description / Bio</label>
                    <textarea id="docDescription" rows="3" placeholder="Doctor's background, experience, and qualifications..."></textarea>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" id="docPhone" placeholder="e.g., 09123456789">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Doctor</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mock doctor data
        let doctorsData = [
            { 
                id: 1, 
                name: "Dr. Maria Reyes", 
                specialty: "Ophthalmology", 
                schedule: "Mon, Wed, Fri • 9:00 AM - 5:00 PM",
                description: "Board-certified ophthalmologist with over 15 years of experience in cataract surgery and LASIK procedures. Specializes in retinal disorders and diabetic eye care.",
                phone: "09123456789",
                active: true
            },
            { 
                id: 2, 
                name: "Dr. Jose Mendoza", 
                specialty: "Pediatrics", 
                schedule: "Tue, Thu, Sat • 10:00 AM - 6:00 PM",
                description: "Experienced pediatrician focused on child development, vaccinations, and preventive care. Fluent in English and Spanish.",
                phone: "09234567890",
                active: true
            },
            { 
                id: 3, 
                name: "Dr. Anna Garcia", 
                specialty: "ENT", 
                schedule: "Mon, Tue, Thu • 8:00 AM - 4:00 PM",
                description: "Otolaryngologist specializing in sinus disorders, hearing loss, and pediatric ENT conditions. Published research in tinnitus management.",
                phone: "09345678901",
                active: true
            },
            { 
                id: 4, 
                name: "Dr. Carlos Santos", 
                specialty: "Cardiology", 
                schedule: "Wed, Thu, Fri • 1:00 PM - 7:00 PM",
                description: "Interventional cardiologist with expertise in hypertension, heart failure, and preventive cardiology. Performs echocardiograms and stress tests.",
                phone: "09456789012",
                active: false
            },
            { 
                id: 5, 
                name: "Dr. Elena Lopez", 
                specialty: "Dermatology", 
                schedule: "Mon, Tue, Fri • 9:00 AM - 3:00 PM",
                description: "Dermatologist offering medical and cosmetic dermatology. Specializes in acne treatment, skin cancer screening, and eczema management.",
                phone: "09567890123",
                active: true
            }
        ];

        let nextId = 6;
        let currentEditId = null;

        // Render doctors grid
        function renderDoctors() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            
            let filtered = doctorsData.filter(doc => {
                const matchesSearch = doc.name.toLowerCase().includes(searchTerm) || 
                                     doc.specialty.toLowerCase().includes(searchTerm);
                const matchesStatus = statusFilter === 'all' || 
                                     (statusFilter === 'active' && doc.active) ||
                                     (statusFilter === 'inactive' && !doc.active);
                return matchesSearch && matchesStatus;
            });
            
            const grid = document.getElementById('doctorsGrid');
            
            if (filtered.length === 0) {
                grid.innerHTML = '<div class="empty-state">No doctors found. Click "Add New Doctor" to create a profile.</div>';
                return;
            }
            
            grid.innerHTML = filtered.map(doc => `
                <div class="doctor-card ${!doc.active ? 'inactive' : ''}">
                    <div class="card-header">
                        <div class="doctor-avatar">
                            <i class="fa-solid fa-user-doctor"></i>
                        </div>
                        <div class="doctor-name">${escapeHtml(doc.name)}</div>
                        <div class="doctor-specialty">${escapeHtml(doc.specialty)}</div>
                        <span class="status-badge ${doc.active ? 'status-active' : 'status-inactive'}">
                            ${doc.active ? '● ACTIVE' : '○ INACTIVE'}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="doctor-description">
                            ${escapeHtml(doc.description.length > 100 ? doc.description.substring(0, 100) + '...' : doc.description)}
                        </div>
                        <div class="schedule-info">
                            <div class="schedule-title">
                                <span><i class="fa-solid fa-clock"></i></span> Schedule
                            </div>
                            <div class="schedule-text">${escapeHtml(doc.schedule)}</div>
                            ${doc.phone ? `<div class="schedule-text" style="margin-top: 0.5rem;">📞 ${escapeHtml(doc.phone)}</div>` : ''}
                        </div>
                        <div class="card-actions">
                            <button class="btn-icon btn-edit" onclick="openEditModal(${doc.id})">
                                <i class="fa-solid fa-edit"></i> Edit
                            </button>
                            ${doc.active ? 
                                `<button class="btn-icon btn-deactivate" onclick="toggleActiveStatus(${doc.id}, false)">
                                    <i class="fa-solid fa-lock"></i> Deactivate
                                </button>` :
                                `<button class="btn-icon btn-activate" onclick="toggleActiveStatus(${doc.id}, true)">
                                    <i class="fa-solid fa-unlock"></i> Activate
                                </button>`
                            }
                            <button class="btn-icon btn-remove" onclick="removeDoctor(${doc.id})">
                                <i class="fa-solid fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Open Add Modal
        function openAddModal() {
            currentEditId = null;
            document.getElementById('modalTitle').innerText = 'Add New Doctor';
            document.getElementById('doctorForm').reset();
            document.getElementById('doctorId').value = '';
            document.getElementById('doctorModal').style.display = 'flex';
        }
        
        // Open Edit Modal
        function openEditModal(id) {
            const doctor = doctorsData.find(d => d.id === id);
            if (!doctor) return;
            
            currentEditId = id;
            document.getElementById('modalTitle').innerText = 'Edit Doctor Profile';
            document.getElementById('doctorId').value = doctor.id;
            document.getElementById('docName').value = doctor.name;
            document.getElementById('docSpecialty').value = doctor.specialty;
            document.getElementById('docSchedule').value = doctor.schedule;
            document.getElementById('docDescription').value = doctor.description;
            document.getElementById('docPhone').value = doctor.phone || '';
            document.getElementById('doctorModal').style.display = 'flex';
        }
        
        // Save doctor (add or edit)
        function saveDoctor(event) {
            event.preventDefault();
            
            const id = document.getElementById('doctorId').value;
            const name = document.getElementById('docName').value.trim();
            const specialty = document.getElementById('docSpecialty').value.trim();
            const schedule = document.getElementById('docSchedule').value.trim();
            const description = document.getElementById('docDescription').value.trim();
            const phone = document.getElementById('docPhone').value.trim();
            
            if (!name || !specialty || !schedule) {
                alert('Please fill in all required fields (Name, Specialty, Schedule).');
                return;
            }
            
            if (id) {
                // Edit existing
                const index = doctorsData.findIndex(d => d.id == id);
                if (index !== -1) {
                    doctorsData[index] = {
                        ...doctorsData[index],
                        name: name,
                        specialty: specialty,
                        schedule: schedule,
                        description: description || doctorsData[index].description,
                        phone: phone || doctorsData[index].phone
                    };
                    alert(`Doctor profile for ${name} has been updated.`);
                }
            } else {
                // Add new
                const newDoctor = {
                    id: nextId++,
                    name: name,
                    specialty: specialty,
                    schedule: schedule,
                    description: description || 'No description provided.',
                    phone: phone || '',
                    active: true
                };
                doctorsData.push(newDoctor);
                alert(`New doctor ${name} has been added successfully.`);
            }
            
            closeModal();
            renderDoctors();
        }
        
        // Toggle active/inactive status
        function toggleActiveStatus(id, activate) {
            const doctor = doctorsData.find(d => d.id === id);
            if (doctor) {
                doctor.active = activate;
                const action = activate ? 'activated' : 'deactivated';
                alert(`${doctor.name} has been ${action}.`);
                renderDoctors();
            }
        }
        
        // Remove doctor (permanent deletion)
        function removeDoctor(id) {
            const doctor = doctorsData.find(d => d.id === id);
            if (!doctor) return;
            
            if (confirm(`Are you sure you want to permanently remove ${doctor.name} from the system? This action cannot be undone.`)) {
                doctorsData = doctorsData.filter(d => d.id !== id);
                alert(`${doctor.name} has been removed.`);
                renderDoctors();
            }
        }
        
        function closeModal() {
            document.getElementById('doctorModal').style.display = 'none';
            currentEditId = null;
        }
        
        // Navigation functions
        function goBackToDashboard() {
            window.location.href = "dashboard.html";
        }
        
        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                alert('Logging out... Redirecting to login page.');
            }
        }
        
        // Event listeners
        document.getElementById('openAddModalBtn').addEventListener('click', openAddModal);
        document.getElementById('doctorForm').addEventListener('submit', saveDoctor);
        document.getElementById('searchInput').addEventListener('input', () => renderDoctors());
        document.getElementById('statusFilter').addEventListener('change', () => renderDoctors());
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('doctorModal');
            if (event.target === modal) closeModal();
        }
        
        // Initial render
        renderDoctors();
    </script>
</body>
</html>