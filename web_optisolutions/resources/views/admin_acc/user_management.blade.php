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
    <title>OptiSolutions - User Management</title>
    <!-- Vite CSS -->
    @vite(['resources/css/admin_css/user_management.css', 'resources/css/admin_css/sidebar.css'])
</head>
<body>
    <!-- Header -->
    @include('admin_acc.header')

    <!-- Main Container -->
    <div class="container">
        @include('admin_acc.sidebar')
        
        <div class="page-header">
            <h2>
                <span><i class="fa-solid fa-users"></i></span> 
                User Management
            </h2>
            <p>Manage staff accounts, roles, and permissions</p>
            
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-number" id="totalUsers">0</div>
                <div class="stat-label">Total Staff</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="activeUsers">0</div>
                <div class="stat-label">Active Accounts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="adminCount">0</div>
                <div class="stat-label">Administrators</div>
            </div>
            
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            
            
                <input type="text" class="search-box" id="searchInput" placeholder="Search by name or email...">
                <select class="filter-select" id="roleFilter">
                    <option value="all">All Roles</option>
                    <option value="Admin">Administrator</option>
                    
                    <option value="Nurse">Nurse</option>
                    <option value="Staff">Staff</option>
                </select>
                <select class="filter-select" id="statusFilter">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <div style="display: flex; gap: 1rem;">
                <button class="add-user-btn" id="openAddModalBtn">
                <span><i class="fa-solid fa-user-plus"></i></span> Add New Staff Account
            </button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <!-- Dynamic content -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add New Staff Account</h3>
            <form id="userForm">
                <input type="hidden" id="userId" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" id="userName" required placeholder="e.g., John Smith">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" id="userEmail" required placeholder="john@optisolutions.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Role *</label>
                        <select id="userRole" required>
                            <option value="">Select Role</option>
                            <option value="Admin">Administrator</option>
                            <
                            <option value="Nurse">Nurse</option>
                            <option value="Staff">Staff</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" id="userPassword" placeholder="Enter password">
                        <small style="font-size: 0.7rem; color: #7f8c8d;" id="passwordHint">Minimum 6 characters</small>
                    </div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="userStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Account</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mock staff accounts data
        let usersData = [
            { id: 1, name: "Dr. Lara Cruz", email: "lara.cruz@optisolutions.com", role: "Admin", status: "active", lastLogin: "2026-05-22 09:30 AM", password: "admin123" },
            
            
            { id: 4, name: "Anna Santos", email: "anna.santos@optisolutions.com", role: "Nurse", status: "active", lastLogin: "2026-05-22 08:45 AM", password: "nurse123" },
            { id: 5, name: "Robert Gomez", email: "robert.gomez@optisolutions.com", role: "Staff", status: "inactive", lastLogin: "2026-05-10 04:20 PM", password: "staff123" },
            
            { id: 7, name: "Michael Tan", email: "michael.tan@optisolutions.com", role: "Staff", status: "active", lastLogin: "2026-05-22 10:00 AM", password: "staff456" },
            { id: 8, name: "Sarah Javier", email: "sarah.javier@optisolutions.com", role: "Nurse", status: "inactive", lastLogin: "2026-05-05 01:00 PM", password: "nurse456" }
        ];

        let nextId = 9;
        let currentEditId = null;

        // Get role badge class
        function getRoleBadgeClass(role) {
            switch(role) {
                case 'Admin': return 'role-admin';
                
                case 'Nurse': return 'role-nurse';
                default: return 'role-staff';
            }
        }

        // Render users table
        function renderUsers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            
            let filtered = usersData.filter(user => {
                const matchesSearch = user.name.toLowerCase().includes(searchTerm) || 
                                     user.email.toLowerCase().includes(searchTerm);
                const matchesRole = roleFilter === 'all' || user.role === roleFilter;
                const matchesStatus = statusFilter === 'all' || user.status === statusFilter;
                return matchesSearch && matchesRole && matchesStatus;
            });
            
            const tbody = document.getElementById('usersTableBody');
            
            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No staff accounts found. Click "Add New Staff Account" to create one.</td></tr>';
            } else {
                tbody.innerHTML = filtered.map(user => {
                    const initials = user.name.split(' ').map(n => n[0]).join('').substring(0, 2);
                    return `
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.8rem;">
                                    <div class="user-avatar-small">${initials}</div>
                                    <div><strong>${escapeHtml(user.name)}</strong></div>
                                </div>
                            </td>
                            <td>${escapeHtml(user.email)}</td>
                            <td><span class="role-badge ${getRoleBadgeClass(user.role)}">${user.role}</span></td>
                            <td><span class="status-badge status-${user.status}">${user.status === 'active' ? '● Active' : '○ Inactive'}</span></td>
                            <td style="font-size: 0.85rem; color: #7f8c8d;">${user.lastLogin || 'Never'}</td>
                            <td class="action-buttons">
                                <button class="btn-icon btn-edit" onclick="openEditModal(${user.id})">
                                    <i class="fa-solid fa-pencil"></i> Edit
                                </button>
                                ${user.status === 'active' ? 
                                    `<button class="btn-icon btn-deactivate" onclick="toggleUserStatus(${user.id}, false)">
                                        <i class="fa-solid fa-lock"></i> Deactivate
                                    </button>` :
                                    `<button class="btn-icon btn-activate" onclick="toggleUserStatus(${user.id}, true)">
                                        <i class="fa-solid fa-unlock"></i> Activate
                                    </button>`
                                }
                                <button class="btn-icon btn-delete" onclick="deleteUser(${user.id})">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
            
            // Update stats
            updateStats();
        }
        
        function updateStats() {
            const total = usersData.length;
            const active = usersData.filter(u => u.status === 'active').length;
            const adminCount = usersData.filter(u => u.role === 'Admin').length;
            
            
            document.getElementById('totalUsers').innerText = total;
            document.getElementById('activeUsers').innerText = active;
            document.getElementById('adminCount').innerText = adminCount;
            
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
            document.getElementById('modalTitle').innerText = 'Add New Staff Account';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('userStatus').value = 'active';
            document.getElementById('userPassword').required = true;
            document.getElementById('userPassword').placeholder = 'Enter password';
            document.getElementById('passwordHint').style.display = 'block';
            document.getElementById('userModal').style.display = 'flex';
        }
        
        // Open Edit Modal
        function openEditModal(id) {
            const user = usersData.find(u => u.id === id);
            if (!user) return;
            
            currentEditId = id;
            document.getElementById('modalTitle').innerText = 'Edit Staff Account';
            document.getElementById('userId').value = user.id;
            document.getElementById('userName').value = user.name;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userStatus').value = user.status;
            document.getElementById('userPassword').value = '';
            document.getElementById('userPassword').required = false;
            document.getElementById('userPassword').placeholder = 'Leave blank to keep current password';
            document.getElementById('passwordHint').style.display = 'block';
            document.getElementById('passwordHint').innerText = 'Leave blank to keep existing password';
            document.getElementById('userModal').style.display = 'flex';
        }
        
        // Save user (add or edit)
        function saveUser(event) {
            event.preventDefault();
            
            const id = document.getElementById('userId').value;
            const name = document.getElementById('userName').value.trim();
            const email = document.getElementById('userEmail').value.trim();
            const role = document.getElementById('userRole').value;
            const status = document.getElementById('userStatus').value;
            const password = document.getElementById('userPassword').value;
            
            if (!name || !email || !role) {
                alert('Please fill in all required fields (Name, Email, Role).');
                return;
            }
            
            if (!id && (!password || password.length < 6)) {
                alert('Please enter a password with at least 6 characters for new accounts.');
                return;
            }
            
            if (id) {
                // Edit existing
                const index = usersData.findIndex(u => u.id == id);
                if (index !== -1) {
                    usersData[index] = { 
                        ...usersData[index], 
                        name, 
                        email, 
                        role, 
                        status,
                        password: password ? password : usersData[index].password
                    };
                    alert(`Staff account for ${name} has been updated.`);
                }
            } else {
                // Add new
                const newUser = {
                    id: nextId++,
                    name: name,
                    email: email,
                    role: role,
                    status: status,
                    lastLogin: 'Never',
                    password: password
                };
                usersData.push(newUser);
                alert(`New staff account for ${name} has been created with role ${role}.`);
            }
            
            closeModal();
            renderUsers();
        }
        
        // Toggle user active/inactive status
        function toggleUserStatus(id, activate) {
            const user = usersData.find(u => u.id === id);
            if (user) {
                user.status = activate ? 'active' : 'inactive';
                const action = activate ? 'activated' : 'deactivated';
                alert(`${user.name}'s account has been ${action}.`);
                renderUsers();
            }
        }
        
        // Delete user (permanent deletion)
        function deleteUser(id) {
            const user = usersData.find(u => u.id === id);
            if (!user) return;
            
            if (user.role === 'Admin') {
                if (!confirm(`Warning: ${user.name} is an Administrator. Are you sure you want to delete this admin account?`)) {
                    return;
                }
            }
            
            if (confirm(`Are you sure you want to permanently delete ${user.name}'s account? This action cannot be undone.`)) {
                usersData = usersData.filter(u => u.id !== id);
                alert(`${user.name}'s account has been deleted.`);
                renderUsers();
            }
        }
        
        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
            currentEditId = null;
            document.getElementById('passwordHint').innerText = 'Minimum 6 characters';
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
        document.getElementById('userForm').addEventListener('submit', saveUser);
        document.getElementById('searchInput').addEventListener('input', () => renderUsers());
        document.getElementById('roleFilter').addEventListener('change', () => renderUsers());
        document.getElementById('statusFilter').addEventListener('change', () => renderUsers());
        
        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target === modal) closeModal();
        }
        
        // Initial render
        renderUsers();
    </script>
</body>
</html>