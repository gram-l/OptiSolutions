<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>OptiSolutions - User Management</title>
    @vite(['resources/css/admin_css/user_management.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css', 'resources/css/admin_css/system_settings.css'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @include('admin_acc.header')

    <div class="container">
        @include('admin_acc.sidebar')

        <div style="flex: 1; min-width: 0;">

            <div class="page-header">
                <h2><span><i class="fa-solid fa-users"></i></span> User Management</h2>
                <p>Manage staff accounts, roles, and permissions</p>
            </div>

            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-card">
                    <div class="stat-number" id="totalUsers">{{ $stats['total'] }}</div>
                    <div class="stat-label">Total Staff</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="activeUsers">{{ $stats['active'] }}</div>
                    <div class="stat-label">Active Accounts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="adminCount">{{ $stats['admins'] }}</div>
                    <div class="stat-label">Administrators</div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="action-bar">
                <input type="text" class="search-box" id="searchInput" placeholder="Search by name or email...">
                <select class="filter-select" id="roleFilter">
                    <option value="all">All Roles</option>
                    <option value="Admin">Administrator</option>
                    
                    <option value="Staff">Staff</option>
                </select>
                <select class="filter-select" id="statusFilter">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button class="add-user-btn" id="openAddModalBtn">
                    <span><i class="fa-solid fa-user-plus"></i></span> Add New Staff Account
                </button>
            </div>

            <!-- Toast -->
            <div id="toast" style="
                display:none; position:fixed; bottom:2rem; right:2rem;
                background:#062744; color:#fff; padding:0.9rem 1.5rem;
                border-radius:12px; font-size:0.9rem; z-index:9999;
                box-shadow:0 4px 20px rgba(0,0,0,0.2); max-width:350px;
            "></div>

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
                    <tbody id="usersTableBody"></tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Add New Staff Account</h3>
            <div id="modalError" style="
                display:none; background:#fdeaea; color:#e74c3c;
                padding:0.7rem 1rem; border-radius:8px;
                margin-bottom:1rem; font-size:0.85rem;
            "></div>
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
                            
                            <option value="Staff">Staff</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" id="userPassword" placeholder="Enter password">
                        <small style="font-size:0.7rem; color:#7f8c8d;" id="passwordHint">Minimum 6 characters</small>
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
                    <button type="submit" class="btn-save" id="saveBtn">Save Account</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Seeded from PHP — uses actual column names
        let usersData = @json($users);
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;

        function showToast(msg, isError = false) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.style.background = isError ? '#e74c3c' : '#062744';
            t.style.display = 'block';
            setTimeout(() => t.style.display = 'none', 3500);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const d = document.createElement('div');
            d.textContent = text;
            return d.innerHTML;
        }

        function getRoleBadgeClass(role) {
            return role === 'Admin' ? 'role-admin' : 'role-staff';
        }

        function renderUsers() {
            const search     = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const statFilter = document.getElementById('statusFilter').value;

            const filtered = usersData.filter(u => {
                const matchSearch = u.name.toLowerCase().includes(search) || u.email.toLowerCase().includes(search);
                const matchRole   = roleFilter === 'all' || u.user_role === roleFilter;
                const matchStat   = statFilter === 'all' || u.status === statFilter;
                return matchSearch && matchRole && matchStat;
            });

            const tbody = document.getElementById('usersTableBody');

            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No staff accounts found.</td></tr>';
            } else {
                tbody.innerHTML = filtered.map(u => {
                    const initials = u.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                    const isActive = u.status === 'active';
                    return `
                        <tr id="row-${u.user_id}">
                            <td>
                                <div style="display:flex;align-items:center;gap:0.8rem;">
                                    <div class="user-avatar-small">${initials}</div>
                                    <strong>${escapeHtml(u.name)}</strong>
                                </div>
                            </td>
                            <td>${escapeHtml(u.email)}</td>
                            <td><span class="role-badge ${getRoleBadgeClass(u.user_role)}">${u.user_role}</span></td>
                            <td><span class="status-badge status-${u.status}">${isActive ? '● Active' : '○ Inactive'}</span></td>
                            <td style="font-size:0.85rem;color:#7f8c8d;">${u.last_login_at ?? 'Never'}</td>
                            <td class="action-buttons">
                                <button class="btn-icon btn-edit" onclick="openEditModal(${u.user_id})">
                                    <i class="fa-solid fa-pencil"></i> Edit
                                </button>
                                ${isActive
                                    ? `<button class="btn-icon btn-deactivate" onclick="toggleStatus(${u.user_id})">
                                            <i class="fa-solid fa-lock"></i> Deactivate
                                       </button>`
                                    : `<button class="btn-icon btn-activate" onclick="toggleStatus(${u.user_id})">
                                            <i class="fa-solid fa-unlock"></i> Activate
                                       </button>`
                                }
                                <button class="btn-icon btn-delete" onclick="deleteUser(${u.user_id})">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>`;
                }).join('');
            }

            updateStats();
        }

        function updateStats() {
            document.getElementById('totalUsers').innerText  = usersData.length;
            document.getElementById('activeUsers').innerText = usersData.filter(u => u.status === 'active').length;
            document.getElementById('adminCount').innerText  = usersData.filter(u => u.user_role === 'Admin').length;
        }

        function openAddModal() {
            document.getElementById('modalTitle').innerText     = 'Add New Staff Account';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value            = '';
            document.getElementById('userPassword').required   = true;
            document.getElementById('userPassword').placeholder = 'Enter password (min 6 characters)';
            document.getElementById('passwordHint').innerText  = 'Minimum 6 characters';
            document.getElementById('modalError').style.display = 'none';
            document.getElementById('userModal').style.display  = 'flex';
        }

        function openEditModal(user_id) {
            const u = usersData.find(u => u.user_id === user_id);
            if (!u) return;

            document.getElementById('modalTitle').innerText     = 'Edit Staff Account';
            document.getElementById('userId').value             = u.user_id;
            document.getElementById('userName').value           = u.name;
            document.getElementById('userEmail').value          = u.email;
            document.getElementById('userRole').value           = u.user_role;
            document.getElementById('userStatus').value         = u.status;
            document.getElementById('userPassword').value       = '';
            document.getElementById('userPassword').required    = false;
            document.getElementById('userPassword').placeholder = 'Leave blank to keep current password';
            document.getElementById('passwordHint').innerText   = 'Leave blank to keep existing password';
            document.getElementById('modalError').style.display = 'none';
            document.getElementById('userModal').style.display  = 'flex';
        }

        function closeModal() {
            document.getElementById('userModal').style.display  = 'none';
            document.getElementById('modalError').style.display = 'none';
        }

        function showModalError(msg) {
            const el = document.getElementById('modalError');
            el.textContent   = msg;
            el.style.display = 'block';
        }

        // Save (Add or Edit)
        document.getElementById('userForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const user_id  = document.getElementById('userId').value;
            const name     = document.getElementById('userName').value.trim();
            const email    = document.getElementById('userEmail').value.trim();
            const user_role = document.getElementById('userRole').value;
            const status   = document.getElementById('userStatus').value;
            const password = document.getElementById('userPassword').value;

            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled    = true;
            saveBtn.textContent = 'Saving...';

            const isEdit = !!user_id;
            const url    = isEdit ? `/admin_acc/user_management/${user_id}` : '/admin_acc/user_management';
            const body   = { name, email, user_role, status, _token: CSRF };
            if (!isEdit || password) body.password = password;
            if (isEdit) body._method = 'PUT';

            try {
                const res  = await fetch(url, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body:    JSON.stringify(body),
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    const firstError = data.errors
                        ? Object.values(data.errors)[0][0]
                        : (data.message || 'Something went wrong.');
                    showModalError(firstError);
                    return;
                }

                if (isEdit) {
                    const idx = usersData.findIndex(u => u.user_id == user_id);
                    if (idx !== -1) usersData[idx] = data.user;
                } else {
                    usersData.push(data.user);
                }

                closeModal();
                renderUsers();
                showToast(data.message);

            } catch (err) {
                showModalError('Network error. Please try again.');
            } finally {
                saveBtn.disabled    = false;
                saveBtn.textContent = 'Save Account';
            }
        });

        // Toggle Active / Inactive
        async function toggleStatus(user_id) {
            try {
                const res  = await fetch(`/admin_acc/user_management/${user_id}/toggle`, {
                    method:  'PATCH',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (!data.success) { showToast(data.message, true); return; }

                const u = usersData.find(u => u.user_id === user_id);
                if (u) u.status = data.new_status;

                renderUsers();
                showToast(data.message);
            } catch {
                showToast('Network error. Please try again.', true);
            }
        }

        // Delete
        async function deleteUser(user_id) {
            const u = usersData.find(u => u.user_id === user_id);
            if (!u) return;

            const warn = u.user_role === 'Admin' ? `⚠️ ${u.name} is an Administrator. ` : '';
            if (!confirm(`${warn}Permanently delete ${u.name}'s account? This cannot be undone.`)) return;

            try {
                const res  = await fetch(`/admin_acc/user_management/${user_id}`, {
                    method:  'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (!data.success) { showToast(data.message, true); return; }

                usersData = usersData.filter(u => u.user_id !== user_id);
                renderUsers();
                showToast(data.message);
            } catch {
                showToast('Network error. Please try again.', true);
            }
        }

        // Event listeners
        document.getElementById('openAddModalBtn').addEventListener('click', openAddModal);
        document.getElementById('searchInput').addEventListener('input', renderUsers);
        document.getElementById('roleFilter').addEventListener('change', renderUsers);
        document.getElementById('statusFilter').addEventListener('change', renderUsers);
        window.addEventListener('click', e => {
            if (e.target === document.getElementById('userModal')) closeModal();
        });

        renderUsers();
    </script>
</body>
</html>