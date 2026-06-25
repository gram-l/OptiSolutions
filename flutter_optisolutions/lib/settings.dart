// settings.dart
import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import 'main.dart' show appMenuItems;

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  bool _pushNotifications = true;
  bool _emailNotifications = true;

  void _navigateTo(String route) {
    if (route != '/settings') {
      Navigator.pushNamed(context, route);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/settings',
        onItemTap: _navigateTo,
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _TopBar(onMenuTap: () => Scaffold.of(context).openDrawer()),
              const SizedBox(height: 16),

              // Page title
              Row(
                children: const [
                  Icon(Icons.settings_outlined, color: AppColors.textDark, size: 22),
                  SizedBox(width: 8),
                  Text(
                    'Settings',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textDark,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              const Text(
                'Manage your account preferences',
                style: TextStyle(fontSize: 13, color: AppColors.textGrey),
              ),
              const SizedBox(height: 20),

              // Profile card
              _ProfileCard(),
              const SizedBox(height: 20),

              // Personal Information section
              _SectionHeader(icon: Icons.person_outline_rounded, label: 'PERSONAL INFORMATION'),
              const SizedBox(height: 8),
              _SettingsTile(
                icon: Icons.badge_outlined,
                iconColor: AppColors.primary,
                iconBg: const Color(0xFFE3F2FD),
                title: 'My Profile',
                subtitle: 'View and edit your personal details',
                onTap: () => _showComingSoon(context, 'My Profile'),
              ),
              const SizedBox(height: 20),

              // Login Security section
              _SectionHeader(icon: Icons.lock_outline_rounded, label: 'LOGIN SECURITY'),
              const SizedBox(height: 8),
              _SettingsTile(
                icon: Icons.lock_reset_rounded,
                iconColor: const Color(0xFF388E3C),
                iconBg: const Color(0xFFE8F5E9),
                title: 'Change Password',
                subtitle: 'Update your password',
                onTap: () => _showComingSoon(context, 'Change Password'),
              ),
              const SizedBox(height: 2),
              _SettingsTile(
                icon: Icons.security_rounded,
                iconColor: const Color(0xFF0288D1),
                iconBg: const Color(0xFFE1F5FE),
                title: 'Two-Factor Authentication',
                subtitle: 'Add an extra layer of security',
                onTap: () => _showComingSoon(context, 'Two-Factor Authentication'),
              ),
              const SizedBox(height: 20),

              // Notifications section
              _SectionHeader(icon: Icons.notifications_none_rounded, label: 'NOTIFICATIONS'),
              const SizedBox(height: 8),
              _ToggleTile(
                icon: Icons.notifications_active_outlined,
                iconColor: const Color(0xFF7B1FA2),
                iconBg: const Color(0xFFF3E5F5),
                title: 'Push Notifications',
                subtitle: 'Receive alerts on your device',
                value: _pushNotifications,
                onChanged: (val) => setState(() => _pushNotifications = val),
              ),
              const SizedBox(height: 2),
              _ToggleTile(
                icon: Icons.email_outlined,
                iconColor: const Color(0xFF0288D1),
                iconBg: const Color(0xFFE1F5FE),
                title: 'Email Notifications',
                subtitle: 'Get updates via email',
                value: _emailNotifications,
                onChanged: (val) => setState(() => _emailNotifications = val),
              ),
              const SizedBox(height: 20),

              // Support section
              _SectionHeader(icon: Icons.help_outline_rounded, label: 'SUPPORT'),
              const SizedBox(height: 8),
              _SettingsTile(
                icon: Icons.description_outlined,
                iconColor: const Color(0xFFE65100),
                iconBg: const Color(0xFFFFF3E0),
                title: 'Terms & Conditions',
                subtitle: 'Review our terms of service',
                onTap: () => _showComingSoon(context, 'Terms & Conditions'),
              ),
              const SizedBox(height: 2),
              _SettingsTile(
                icon: Icons.help_center_outlined,
                iconColor: const Color(0xFF00838F),
                iconBg: const Color(0xFFE0F7FA),
                title: 'Help Support',
                subtitle: 'FAQs, guides, and contact support',
                onTap: () => _showComingSoon(context, 'Help Support'),
              ),
              const SizedBox(height: 2),
              _SettingsTile(
                icon: Icons.logout_rounded,
                iconColor: AppColors.deleteRed,
                iconBg: const Color(0xFFFFEBEE),
                title: 'Log Out',
                subtitle: 'Sign out of your account',
                titleColor: AppColors.deleteRed,
                onTap: () => _showLogoutDialog(context),
              ),
              const SizedBox(height: 24),

              // Footer
              const Center(
                child: Text(
                  'Polyclinic Admin v2.0',
                  style: TextStyle(color: AppColors.textGrey, fontSize: 12),
                ),
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
    );
  }

  void _showComingSoon(BuildContext context, String feature) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('$feature — coming soon'),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        duration: const Duration(seconds: 2),
      ),
    );
  }

  void _showLogoutDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Log Out', style: TextStyle(fontWeight: FontWeight.bold)),
        content: const Text('Are you sure you want to sign out of your account?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel', style: TextStyle(color: AppColors.textGrey)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.deleteRed,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: () {
              Navigator.pop(ctx);
              Navigator.pushNamedAndRemoveUntil(context, '/', (_) => false);
            },
            child: const Text('Log Out'),
          ),
        ],
      ),
    );
  }
}

// ── Top Bar ──────────────────────────────────────────────────────────────────
class _TopBar extends StatelessWidget {
  final VoidCallback onMenuTap;
  const _TopBar({required this.onMenuTap});

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 6),
      child: Row(
        children: [
          IconButton(
            icon: const Icon(Icons.menu_rounded, color: AppColors.textDark),
            onPressed: onMenuTap,
          ),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: Image.asset(
              'assets/polyclinic_logo.png',
              width: 28,
              height: 28,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Container(
                width: 28,
                height: 28,
                decoration: BoxDecoration(
                  color: const Color(0xFFE3F2FD),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.local_hospital, color: AppColors.primary, size: 16),
              ),
            ),
          ),
          const SizedBox(width: 8),
          const Text(
            'Polyclinic',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.textDark),
          ),
          const Spacer(),
          IconButton(
            icon: const Icon(Icons.notifications_none_rounded, color: AppColors.textDark, size: 22),
            onPressed: () => Navigator.pushNamed(context, '/notifications'),
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
          ),
          const SizedBox(width: 8),
          IconButton(
            icon: const Icon(Icons.logout_rounded, color: AppColors.textDark, size: 22),
            onPressed: () => Navigator.pushNamedAndRemoveUntil(context, '/', (_) => false),
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
          ),
          const SizedBox(width: 4),
        ],
      ),
    );
  }
}


// ── Profile Card ─────────────────────────────────────────────────────────────
class _ProfileCard extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          const CircleAvatar(
            radius: 28,
            backgroundColor: AppColors.darkNavy,
            child: Text(
              'DL',
              style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: const [
                Text(
                  'Dr. Lara Cruz',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.textDark),
                ),
                SizedBox(height: 2),
                Text('Administrator', style: TextStyle(fontSize: 12, color: AppColors.textGrey)),
                SizedBox(height: 1),
                Text('lara.cruz@polyclinic.com', style: TextStyle(fontSize: 12, color: AppColors.textGrey)),
              ],
            ),
          ),
          OutlinedButton(
            onPressed: () {},
            style: OutlinedButton.styleFrom(
              foregroundColor: AppColors.primary,
              side: const BorderSide(color: AppColors.primary),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
            ),
            child: const Text('Edit', style: TextStyle(fontSize: 13)),
          ),
        ],
      ),
    );
  }
}

// ── Section Header ────────────────────────────────────────────────────────────
class _SectionHeader extends StatelessWidget {
  final IconData icon;
  final String label;

  const _SectionHeader({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 14, color: AppColors.textGrey),
        const SizedBox(width: 6),
        Text(
          label,
          style: const TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w600,
            color: AppColors.textGrey,
            letterSpacing: 0.8,
          ),
        ),
      ],
    );
  }
}

// ── Settings Tile (arrow) ─────────────────────────────────────────────────────
class _SettingsTile extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final Color iconBg;
  final String title;
  final String subtitle;
  final Color? titleColor;
  final VoidCallback? onTap;

  const _SettingsTile({
    required this.icon,
    required this.iconColor,
    required this.iconBg,
    required this.title,
    required this.subtitle,
    this.titleColor,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return _TileWrapper(
      onTap: onTap,
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(color: iconBg, borderRadius: BorderRadius.circular(10)),
            child: Icon(icon, color: iconColor, size: 20),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: titleColor ?? AppColors.textDark,
                  ),
                ),
                const SizedBox(height: 2),
                Text(subtitle, style: const TextStyle(fontSize: 12, color: AppColors.textGrey)),
              ],
            ),
          ),
          Icon(Icons.chevron_right_rounded, color: AppColors.textGrey.withOpacity(0.6), size: 20),
        ],
      ),
    );
  }
}

// ── Toggle Tile ───────────────────────────────────────────────────────────────
class _ToggleTile extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final Color iconBg;
  final String title;
  final String subtitle;
  final bool value;
  final ValueChanged<bool> onChanged;

  const _ToggleTile({
    required this.icon,
    required this.iconColor,
    required this.iconBg,
    required this.title,
    required this.subtitle,
    required this.value,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return _TileWrapper(
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(color: iconBg, borderRadius: BorderRadius.circular(10)),
            child: Icon(icon, color: iconColor, size: 20),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.textDark)),
                const SizedBox(height: 2),
                Text(subtitle, style: const TextStyle(fontSize: 12, color: AppColors.textGrey)),
              ],
            ),
          ),
          Switch(
            value: value,
            onChanged: onChanged,
            activeColor: AppColors.primary,
          ),
        ],
      ),
    );
  }
}

// ── Shared tile container ─────────────────────────────────────────────────────
class _TileWrapper extends StatelessWidget {
  final Widget child;
  final VoidCallback? onTap;

  const _TileWrapper({required this.child, this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppColors.border),
        ),
        child: child,
      ),
    );
  }
}