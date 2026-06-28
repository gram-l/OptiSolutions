import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import 'main.dart' show appMenuItems;

class UserModel {
  final String name;
  final String email;
  final String role;          // Admin | Doctor | Staff 
  final String lastLogin;
  final bool   isActive;
  final String avatarInitials;
  final Color  avatarColor;

  const UserModel({
    required this.name,
    required this.email,
    required this.role,
    required this.lastLogin,
    required this.isActive,
    required this.avatarInitials,
    required this.avatarColor,
  });
}

const _users = <UserModel>[
  UserModel(
    name: 'Dr. Lara Cruz', email: 'lara.cruz@polyclinic.com',
    role: 'Admin',  lastLogin: '2026-05-22 · 09:30 AM',
    isActive: true, avatarInitials: 'LC', avatarColor: Color(0xFF1565C0),
  ),
  UserModel(
    name: 'Dr. Maria Reyes', email: 'maria.reyes@polyclinic.com',
    role: 'Doctor', lastLogin: '2026-05-22 · 08:15 AM',
    isActive: true, avatarInitials: 'MR', avatarColor: Color(0xFF00897B),
  ),
  UserModel(
    name: 'Dr. Jose Mendoza', email: 'jose.mendoza@polyclinic.com',
    role: 'Doctor', lastLogin: '2026-05-21 · 04:20 PM',
    isActive: true, avatarInitials: 'JM', avatarColor: Color(0xFF6D4C41),
  ),
  UserModel(
    name: 'Anna Santos', email: 'anna.santos@polyclinic.com',
    role: 'Doctor', lastLogin: '2026-05-22 · 03:45 AM',
    isActive: true, avatarInitials: 'AS', avatarColor: Color(0xFF5E35B1),
  ),
  UserModel(
    name: 'Robert Gomez', email: 'robert.gomez@polyclinic.com',
    role: 'Staff', lastLogin: '2026-05-10 · 04:10 PM',
    isActive: false, avatarInitials: 'RG', avatarColor: Color(0xFF546E7A),
  ),
  UserModel(
    name: 'Michael Tan', email: 'michael.tan@polyclinic.com',
    role: 'Staff', lastLogin: '2026-05-22 · 09:00 AM',
    isActive: true, avatarInitials: 'MT', avatarColor: Color(0xFF00838F),
  ),
  UserModel(
    name: 'Sarah Javier', email: 'sarah.javier@polyclinic.com',
    role: 'Nurse', lastLogin: '2026-05-15 · 10:00 AM',
    isActive: false, avatarInitials: 'SJ', avatarColor: Color(0xFF558B2F),
  ),
];

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

  List<UserModel> get _filtered => _users.where((u) {
        final matchSearch = u.name.toLowerCase().contains(_search.toLowerCase()) ||
            u.email.toLowerCase().contains(_search.toLowerCase());
        final matchRole = _roleFilter == 'All' || u.role == _roleFilter;
        final matchStatus = _statusFilter == 'All' ||
            (_statusFilter == 'Active' ? u.isActive : !u.isActive);
        return matchSearch && matchRole && matchStatus;
      }).toList();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F6FB),
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/user_management',
        onItemTap: (route) => Navigator.pushNamed(context, route),
      ),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildTopBar(),
            _buildHeader(),
            _buildFilterRow(),
            Expanded(
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                itemCount: _filtered.length,
                itemBuilder: (_, i) => _UserCard(user: _filtered[i]),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ── Top navigation bar ──
  Widget _buildTopBar() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      child: Row(
        children: [
          Builder(
            builder: (ctx) => IconButton(
              icon: const Icon(Icons.menu, color: AppColors.textDark),
              onPressed: () => Scaffold.of(ctx).openDrawer(),
            ),
          ),
          const CircleAvatar(
            radius: 13,
            backgroundColor: Color(0xFFE3F2FD),
            child: Icon(Icons.local_hospital, color: AppColors.primary, size: 14),
          ),
          const SizedBox(width: 6),
          const Text('Polyclinic',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          const Spacer(),
          IconButton(
            icon: const Icon(Icons.notifications_none_rounded, color: AppColors.textDark, size: 22),
            onPressed: () => Navigator.pushNamed(context, '/notifications'),
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
          ),
          const SizedBox(width: 4),
          CircleAvatar(
            radius: 14,
            backgroundColor: AppColors.primary,
            child: const Text('DL',
                style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
          ),
          const SizedBox(width: 8),
          const Icon(Icons.logout_outlined, color: AppColors.textGrey, size: 20),
        ],
      ),
    );
  }

  // ── Page heading ──
  Widget _buildHeader() {
    return const Padding(
      padding: EdgeInsets.fromLTRB(16, 4, 16, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.manage_accounts_rounded, color: AppColors.primary, size: 22),
              SizedBox(width: 8),
              Text('User Management',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            ],
          ),
          SizedBox(height: 2),
          Text('Manage staff accounts & roles',
              style: TextStyle(color: AppColors.textGrey, fontSize: 12.5)),
        ],
      ),
    );
  }

  // ── Search + filter row ──
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
            items: const ['All', 'Admin', 'Doctor', 'Staff', 'Nurse'],
            onChanged: (v) => setState(() => _roleFilter = v!),
          ),
          const SizedBox(width: 6),
          _DropdownChip(
            value: _statusFilter,
            items: const ['All', 'Active', 'Inactive'],
            onChanged: (v) => setState(() => _statusFilter = v!),
          ),
          const SizedBox(width: 6),
          _AddButton(onTap: () {}),
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
  const _UserCard({required this.user});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: user.isActive
              ? Colors.transparent
              : const Color(0xFFEEEEEE),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Avatar + Name + badge ──
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
                        style: const TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 1),
                    Text(user.email,
                        style: const TextStyle(
                            fontSize: 11.5, color: AppColors.textGrey)),
                  ],
                ),
              ),
              _StatusBadge(active: user.isActive),
            ],
          ),
          const SizedBox(height: 8),
          // ── Role chip + last login ──
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
          // ── Action buttons ──
          Row(
            children: user.isActive
                ? [
                    _ActionBtn(label: 'Edit', icon: Icons.edit_outlined, color: AppColors.editBlue, onTap: () {}),
                    const SizedBox(width: 6),
                    _ActionBtn(label: 'Deactivate', icon: Icons.block_outlined, color: AppColors.deactivateOrange, onTap: () {}),
                    const SizedBox(width: 6),
                    _ActionBtn(label: 'Delete', icon: Icons.delete_outline_rounded, color: AppColors.deleteRed, onTap: () {}),
                  ]
                : [
                    _ActionBtn(label: 'Edit', icon: Icons.edit_outlined, color: AppColors.editBlue, onTap: () {}),
                    const SizedBox(width: 6),
                    _ActionBtn(label: 'Activate', icon: Icons.check_circle_outline_rounded, color: AppColors.activateGreen, onTap: () {}),
                    const SizedBox(width: 6),
                    _ActionBtn(label: 'Delete', icon: Icons.delete_outline_rounded, color: AppColors.deleteRed, onTap: () {}),
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
        color: active
            ? const Color(0xFFE8F5E9)
            : const Color(0xFFF5F5F5),
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
    'Admin':  Color(0xFFE3F2FD),
    'Doctor': Color(0xFFE8F5E9),
    'Staff':  Color(0xFFFFF3E0),
    'Nurse':  Color(0xFFF3E5F5),
  };
  static const _textColors = {
    'Admin':  Color(0xFF1565C0),
    'Doctor': Color(0xFF2E7D32),
    'Staff':  Color(0xFFE65100),
    'Nurse':  Color(0xFF6A1B9A),
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
      color: color.withOpacity(0.12),
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
        border: Border.all(color: const Color(0xFFE0E0E0)),
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
        border: Border.all(color: const Color(0xFFE0E0E0)),
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