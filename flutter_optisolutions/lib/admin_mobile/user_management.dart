import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import '../main.dart' show appMenuItems;
import 'services/user_service.dart';
import 'center_snackbar.dart';

class UserModel {
  final int    id;
  final String name;
  final String email;
  final String role;          // Admin | Staff
  final String lastLogin;
  final bool   isActive;
  final String avatarInitials;
  final Color  avatarColor;

  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.lastLogin,
    required this.isActive,
    required this.avatarInitials,
    required this.avatarColor,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    final name = (json['name'] ?? '').toString();
    final initials = name
        .trim()
        .split(' ')
        .where((s) => s.isNotEmpty)
        .map((s) => s[0])
        .take(2)
        .join()
        .toUpperCase();

    const colors = [
      Color(0xFF1565C0), Color(0xFF00897B), Color(0xFF6D4C41),
      Color(0xFF5E35B1), Color(0xFF546E7A), Color(0xFF00838F), Color(0xFF558B2F),
    ];
    final color = colors[(json['user_id'] ?? 0) % colors.length];

    return UserModel(
      id: json['user_id'],
      name: name,
      email: json['email'] ?? '',
      role: json['user_role'] ?? '',
      lastLogin: json['last_login_at']?.toString() ?? 'Never',
      isActive: json['status'] == 'active',
      avatarInitials: initials.isEmpty ? '?' : initials,
      avatarColor: color,
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  SCREEN
// ─────────────────────────────────────────────────────────────
class UserManagementScreen extends StatefulWidget {
  const UserManagementScreen({super.key});

  @override
  State<UserManagementScreen> createState() => _UserManagementScreenState();
}

class _UserManagementScreenState extends State<UserManagementScreen> {
  String _search = '';
  String _roleFilter = 'All';
  String _statusFilter = 'All';

  List<UserModel> _users = [];
  bool _loading = true;
  String? _error;

  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  @override
  void initState() {
    super.initState();
    _loadUsers();
  }

  Future<void> _loadUsers() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final data = await UserService.fetchUsers();

    if (!mounted) return;

    if (data['success'] == true) {
      final list = (data['users'] as List)
          .map((u) => UserModel.fromJson(u))
          .toList();
      setState(() {
        _users = list;
        _loading = false;
      });
    } else {
      setState(() {
        _error = data['message'] ?? 'Failed to load users.';
        _loading = false;
      });
    }
  }

  List<UserModel> get _filtered => _users.where((u) {
        final matchSearch = u.name.toLowerCase().contains(_search.toLowerCase()) ||
            u.email.toLowerCase().contains(_search.toLowerCase());
        final matchRole = _roleFilter == 'All' || u.role == _roleFilter;
        final matchStatus = _statusFilter == 'All' ||
            (_statusFilter == 'Active' ? u.isActive : !u.isActive);
        return matchSearch && matchRole && matchStatus;
      }).toList();

  Future<void> _toggleStatus(UserModel user) async {
    final result = await UserService.toggleStatus(user.id);
    if (!mounted) return;
    if (result['success'] == true) {
      _loadUsers();
      _showSnack(result['message']);
    } else {
      _showSnack(result['message'] ?? 'Action failed.', isError: true);
    }
  }

  Future<void> _deleteUser(UserModel user) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete user?'),
        content: Text('Are you sure you want to delete ${user.name}?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    final result = await UserService.deleteUser(user.id);
    if (!mounted) return;
    if (result['success'] == true) {
      _loadUsers();
      _showSnack(result['message']);
    } else {
      _showSnack(result['message'] ?? 'Delete failed.', isError: true);
    }
  }

  void _showSnack(String? msg, {bool isError = false}) {
    if (msg == null) return;
    showCenterSnackBar(context, msg, isError: isError);
  }

  void _openAddDialog() => _openUserForm();
  void _openEditDialog(UserModel user) => _openUserForm(user: user);

  void _openUserForm({UserModel? user}) {
    final isEdit = user != null;
    final nameCtrl = TextEditingController(text: user?.name ?? '');
    final emailCtrl = TextEditingController(text: user?.email ?? '');
    final passCtrl = TextEditingController();
    String role = (user?.role == 'Admin' || user?.role == 'Staff') ? user!.role : 'Staff';
    String status = (user?.isActive ?? true) ? 'active' : 'inactive';
    bool saving = false;
    String? formError;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setLocal) => AlertDialog(
          title: Text(isEdit ? 'Edit User' : 'Add User'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (formError != null) ...[
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(10),
                    margin: const EdgeInsets.only(bottom: 10),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFDEAEA),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      formError!,
                      style: const TextStyle(color: Colors.red, fontSize: 12.5),
                    ),
                  ),
                ],
                TextField(controller: nameCtrl, decoration: const InputDecoration(labelText: 'Name')),
                TextField(controller: emailCtrl, decoration: const InputDecoration(labelText: 'Email')),
                TextField(
                  controller: passCtrl,
                  obscureText: true,
                  decoration: InputDecoration(
                    labelText: isEdit ? 'New Password (optional)' : 'Password',
                  ),
                ),
                DropdownButtonFormField<String>(
                  initialValue: role,
                  decoration: const InputDecoration(labelText: 'Role'),
                  items: const ['Admin', 'Staff']
                      .map((r) => DropdownMenuItem(value: r, child: Text(r)))
                      .toList(),
                  onChanged: (v) => setLocal(() => role = v!),
                ),
                DropdownButtonFormField<String>(
                  initialValue: status,
                  decoration: const InputDecoration(labelText: 'Status'),
                  items: const ['active', 'inactive']
                      .map((s) => DropdownMenuItem(value: s, child: Text(s)))
                      .toList(),
                  onChanged: (v) => setLocal(() => status = v!),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
            ElevatedButton(
              onPressed: saving
                  ? null
                  : () async {
                      setLocal(() {
                        saving = true;
                        formError = null;
                      });

                      Map<String, dynamic> result;
                      if (isEdit) {
                        result = await UserService.updateUser(
                          user.id,
                          nameCtrl.text,
                          emailCtrl.text,
                          role,
                          status,
                          password: passCtrl.text,
                        );
                      } else {
                        result = await UserService.createUser(
                          nameCtrl.text,
                          emailCtrl.text,
                          role,
                          passCtrl.text,
                          status,
                        );
                      }

                      if (result['success'] == true) {
                        if (ctx.mounted) Navigator.pop(ctx);
                        _loadUsers();
                        _showSnack(result['message']);
                      } else {
                        setLocal(() {
                          saving = false;
                          formError = result['message'] ?? 'Something went wrong.';
                        });
                      }
                    },
              child: saving
                  ? const SizedBox(
                      width: 16, height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Text('Save'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      backgroundColor: AppColors.background,
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/user_management',
        onItemTap: (route) => Navigator.pushNamed(context, route),
      ),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildHeader(),
            _buildFilterRow(),
            Expanded(child: _buildBody()),
          ],
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.wifi_off_rounded, size: 36, color: AppColors.textGrey),
            const SizedBox(height: 8),
            Text(_error!, style: const TextStyle(color: Colors.red)),
            const SizedBox(height: 8),
            ElevatedButton(onPressed: _loadUsers, child: const Text('Retry')),
          ],
        ),
      );
    }
    if (_filtered.isEmpty) {
      return const Center(
        child: Text('No users found.', style: TextStyle(color: AppColors.textGrey)),
      );
    }
    return RefreshIndicator(
      onRefresh: _loadUsers,
      child: ListView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        itemCount: _filtered.length,
        itemBuilder: (_, i) => _UserCard(
          user: _filtered[i],
          onEdit: () => _openEditDialog(_filtered[i]),
          onToggle: () => _toggleStatus(_filtered[i]),
          onDelete: () => _deleteUser(_filtered[i]),
        ),
      ),
    );
  }

  // ── Merged header: hamburger + icon box + title + subtitle ──
  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 10, 16, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              IconButton(
                icon: const Icon(Icons.menu_rounded, color: AppColors.iconColor),
                onPressed: () => _scaffoldKey.currentState?.openDrawer(),
              ),
              Container(
                width: 30, height: 30,
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(
                  Icons.manage_accounts_rounded,
                  size: 15,
                  color: AppColors.iconColor,
                ),
              ),
              const SizedBox(width: 10),
              const Text('User Management',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppColors.darkNavy,
                  )),
            ],
          ),
          const Padding(
            padding: EdgeInsets.only(left: 48, top: 4),
            child: Text('Manage staff accounts & roles',
                style: TextStyle(color: AppColors.textGrey, fontSize: 13)),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterRow() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        children: [
          Expanded(
            child: _SearchField(
              onChanged: (v) => setState(() => _search = v),
            ),
          ),
          const SizedBox(width: 8),
          _DropdownChip(
            value: _roleFilter,
            items: const ['All', 'Admin', 'Staff'],
            onChanged: (v) => setState(() => _roleFilter = v!),
          ),
          const SizedBox(width: 6),
          _DropdownChip(
            value: _statusFilter,
            items: const ['All', 'Active', 'Inactive'],
            onChanged: (v) => setState(() => _statusFilter = v!),
          ),
          const SizedBox(width: 6),
          _AddButton(onTap: _openAddDialog),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  USER CARD
// ─────────────────────────────────────────────────────────────
class _UserCard extends StatelessWidget {
  final UserModel user;
  final VoidCallback onEdit;
  final VoidCallback onToggle;
  final VoidCallback onDelete;

  const _UserCard({
    required this.user,
    required this.onEdit,
    required this.onToggle,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: user.isActive ? Colors.transparent : const Color(0xFFEEEEEE),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CircleAvatar(
                radius: 18,
                backgroundColor: user.avatarColor,
                child: Text(user.avatarInitials,
                    style: const TextStyle(
                        color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(user.name,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 1),
                    Text(user.email,
                        style: const TextStyle(fontSize: 11.5, color: AppColors.textGrey)),
                  ],
                ),
              ),
              _StatusBadge(active: user.isActive),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              _RoleChip(role: user.role),
              const SizedBox(width: 10),
              const Icon(Icons.access_time_rounded, size: 12, color: AppColors.textGrey),
              const SizedBox(width: 3),
              Text('Last login: ${user.lastLogin}',
                  style: const TextStyle(fontSize: 11, color: AppColors.textGrey)),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: user.isActive
                ? [
                    _ActionBtn(label: 'Edit', icon: Icons.edit_outlined, color: AppColors.editBlue, onTap: onEdit),
                    const SizedBox(width: 6),
                    _ActionBtn(label: 'Deactivate', icon: Icons.block_outlined, color: AppColors.deactivateOrange, onTap: onToggle),
                    const SizedBox(width: 6),
                    _ActionBtn(label: 'Delete', icon: Icons.delete_outline_rounded, color: AppColors.deleteRed, onTap: onDelete),
                  ]
                : [
                    _ActionBtn(label: 'Edit', icon: Icons.edit_outlined, color: AppColors.editBlue, onTap: onEdit),
                    const SizedBox(width: 6),
                    _ActionBtn(label: 'Activate', icon: Icons.check_circle_outline_rounded, color: AppColors.activateGreen, onTap: onToggle),
                    const SizedBox(width: 6),
                    _ActionBtn(label: 'Delete', icon: Icons.delete_outline_rounded, color: AppColors.deleteRed, onTap: onDelete),
                  ],
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  SMALL REUSABLE WIDGETS
// ─────────────────────────────────────────────────────────────
class _StatusBadge extends StatelessWidget {
  final bool active;
  const _StatusBadge({required this.active});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: active ? const Color(0xFFE8F5E9) : const Color(0xFFF5F5F5),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        active ? 'ACTIVE' : 'INACTIVE',
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.bold,
          color: active ? AppColors.activeGreen : AppColors.inactiveGrey,
          letterSpacing: 0.4,
        ),
      ),
    );
  }
}

class _RoleChip extends StatelessWidget {
  final String role;
  const _RoleChip({required this.role});

  static const _colors = {
    'Admin': Color(0xFFE3F2FD),
    'Staff': Color(0xFFFFF3E0),
  };
  static const _textColors = {
    'Admin': Color(0xFF1565C0),
    'Staff': Color(0xFFE65100),
  };

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: _colors[role] ?? const Color(0xFFF5F5F5),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        role,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: _textColors[role] ?? AppColors.textDark,
        ),
      ),
    );
  }
}

class _ActionBtn extends StatelessWidget {
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  const _ActionBtn({required this.label, required this.icon, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: color.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          child: Row(
            children: [
              Icon(icon, size: 13, color: color),
              const SizedBox(width: 4),
              Text(label, style: TextStyle(fontSize: 12, color: color, fontWeight: FontWeight.w600)),
            ],
          ),
        ),
      ),
    );
  }
}

class _SearchField extends StatelessWidget {
  final ValueChanged<String> onChanged;
  const _SearchField({required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 38,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: TextField(
        onChanged: onChanged,
        style: const TextStyle(fontSize: 13),
        decoration: const InputDecoration(
          prefixIcon: Icon(Icons.search, size: 16, color: AppColors.textGrey),
          hintText: 'Search users...',
          hintStyle: TextStyle(color: AppColors.textGrey, fontSize: 13),
          border: InputBorder.none,
          contentPadding: EdgeInsets.symmetric(vertical: 10),
        ),
      ),
    );
  }
}

class _DropdownChip extends StatelessWidget {
  final String value;
  final List<String> items;
  final ValueChanged<String?> onChanged;
  const _DropdownChip({required this.value, required this.items, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 38,
      padding: const EdgeInsets.symmetric(horizontal: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: value,
          items: items.map((e) => DropdownMenuItem(value: e, child: Text(e, style: const TextStyle(fontSize: 12)))).toList(),
          onChanged: onChanged,
          style: const TextStyle(fontSize: 12, color: AppColors.textDark),
          icon: const Icon(Icons.keyboard_arrow_down_rounded, size: 16, color: AppColors.textGrey),
        ),
      ),
    );
  }
}

class _AddButton extends StatelessWidget {
  final VoidCallback onTap;
  const _AddButton({required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 38,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(
          color: AppColors.primary,
          borderRadius: BorderRadius.circular(10),
        ),
        child: const Row(
          children: [
            Icon(Icons.add, color: Colors.white, size: 16),
            SizedBox(width: 4),
            Text('Add', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
          ],
        ),
      ),
    );
  }
}