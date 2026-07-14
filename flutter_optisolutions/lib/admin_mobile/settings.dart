// settings.dart
import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import '../main.dart' show appMenuItems, themeModeNotifier;
import 'package:flutter_optisolutions/auth/auth_service.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
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

              // Personal Information section
             _SectionHeader(icon: Icons.person_outline_rounded, label: 'PERSONAL INFORMATION'),
              const SizedBox(height: 8),
              _SettingsTile(
                icon: Icons.badge_outlined,
                iconColor: AppColors.primary,
                iconBg: const Color(0xFFE3F2FD),
                title: 'My Profile',
                subtitle: 'View your personal details',
                onTap: () => Navigator.pushNamed(context, '/profile'),
              ),
              const SizedBox(height: 20),

              // Preferences section
              _SectionHeader(icon: Icons.tune_rounded, label: 'PREFERENCES'),
              const SizedBox(height: 8),
              ValueListenableBuilder<ThemeMode>(
                valueListenable: themeModeNotifier,
                builder: (context, currentMode, _) {
                  final isDark = currentMode == ThemeMode.dark;
                  return _TileWrapper(
                    onTap: () {
                      themeModeNotifier.value = isDark ? ThemeMode.light : ThemeMode.dark;
                    },
                    child: Row(
                      children: [
                        Container(
                          width: 38,
                          height: 38,
                          decoration: BoxDecoration(
                            color: const Color(0xFFEDE7F6),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Icon(
                            isDark ? Icons.dark_mode_rounded : Icons.light_mode_rounded,
                            color: const Color(0xFF5E35B1),
                            size: 20,
                          ),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Theme',
                                style: TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.textDark,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                isDark ? 'Dark mode' : 'Light mode',
                                style: const TextStyle(fontSize: 12, color: AppColors.textGrey),
                              ),
                            ],
                          ),
                        ),
                        Switch(
                          value: isDark,
                          onChanged: (val) {
                            themeModeNotifier.value = val ? ThemeMode.dark : ThemeMode.light;
                          },
                          activeColor: AppColors.primary,
                        ),
                      ],
                    ),
                  );
                },
              ),
              const SizedBox(height: 20),

              // Login Security section
             /* _SectionHeader(icon: Icons.lock_outline_rounded, label: 'LOGIN SECURITY'),
              const SizedBox(height: 8),
              _SettingsTile(
                icon: Icons.lock_reset_rounded,
                iconColor: const Color(0xFF388E3C),
                iconBg: const Color(0xFFE8F5E9),
                title: 'Change Password',
                subtitle: 'Update your password',
                onTap: () => _showComingSoon(context, 'Change Password'),
              ),
              const SizedBox(height: 20),*/

              // Support section
              _SectionHeader(icon: Icons.help_outline_rounded, label: 'SUPPORT'),
              const SizedBox(height: 8),
              _SettingsTile(
                icon: Icons.help_center_outlined,
                iconColor: const Color(0xFF00838F),
                iconBg: const Color(0xFFE0F7FA),
                title: 'Help Support',
                subtitle: 'FAQs and contact information',
                onTap: () => _showHelpSupportSheet(context),
              ),
              const SizedBox(height: 2),
              _SettingsTile(
                icon: Icons.privacy_tip_outlined,
                iconColor: const Color(0xFF6D4C41),
                iconBg: const Color(0xFFEFEBE9),
                title: 'Data & Privacy',
                subtitle: 'How patient data is handled',
                onTap: () => _showDataPrivacySheet(context),
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

  void _showHelpSupportSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => const _HelpSupportSheet(),
    );
  }

  void _showDataPrivacySheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => const _DataPrivacySheet(),
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
          onPressed: () async {
            Navigator.pop(ctx); // close the dialog first
            await AuthService.logout(); // clear stored session
            if (!context.mounted) return;
            Navigator.pushNamedAndRemoveUntil(context, '/', (_) => false);
          },
          child: const Text('Log Out'),
        ),
      ],
    ),
  );
}
}

// ── Help & Support bottom sheet: FAQs about the system + contact info ─────────
class _HelpSupportSheet extends StatelessWidget {
  const _HelpSupportSheet();

  static const _faqs = <_FaqItem>[
    _FaqItem(
      question: 'How do I add or edit a doctor?',
      answer: 'Go to Manage Doctors from the side menu, tap the + button to add a new doctor, '
          'or tap Edit on an existing doctor card to update their details.',
    ),
    _FaqItem(
      question: 'How do I view scheduled visits for a specific day?',
      answer: 'Open Appointments, then tap the date pill at the top to pick any day, or use the '
          'arrows on either side to move to the previous or next day.',
    ),
    _FaqItem(
      question: 'Can I export patient or appointment records?',
      answer: 'Yes. On the Patients or Appointments screen, tap the export icon in the top-right '
          'and choose CSV, Excel, or PDF. The file will open in your share menu so you can save '
          'or send it.',
    ),
    _FaqItem(
      question: 'Why can\'t I see the same screens as a staff account?',
      answer: 'Admin and Staff accounts have different access levels. If a screen or action seems '
          'to be missing, it may not be part of your role\'s permissions — contact your system '
          'administrator if you believe this is incorrect.',
    ),
    _FaqItem(
      question: 'How do I change my profile photo?',
      answer: 'Go to My Profile from Settings, then tap the camera icon on your avatar to choose '
          'a new photo. Your name, email, and role cannot be changed from the app.',
    ),
    _FaqItem(
      question: 'I forgot my password. What do I do?',
      answer: 'On the login screen, tap "Forgot Password?" and follow the steps to verify your '
          'email and set a new password.',
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      expand: false,
      initialChildSize: 0.7,
      maxChildSize: 0.92,
      builder: (_, ctrl) => Padding(
        padding: const EdgeInsets.all(20),
        child: ListView(
          controller: ctrl,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFDDE1E8),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            const Text('Help & Support',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.textDark)),
            const SizedBox(height: 4),
            const Text('Frequently asked questions about the system',
                style: TextStyle(fontSize: 12.5, color: AppColors.textGrey)),
            const SizedBox(height: 16),

            ..._faqs.map((f) => _FaqTile(item: f)),

            const SizedBox(height: 20),
            const Text('CONTACT SUPPORT',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: AppColors.textGrey,
                  letterSpacing: 0.8,
                )),
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFF4F6FB),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: const [
                  _ContactRow(icon: Icons.email_outlined, label: 'polycliniclipa@gmail.com'),
                  SizedBox(height: 10),
                  _ContactRow(icon: Icons.phone_outlined, label: '0985 475 5511'),
                  SizedBox(height: 10),
                  _ContactRow(
                    icon: Icons.access_time_outlined,
                    label: 'Mon–Fri: 8:00 AM – 6:00 PM · Sat: 9:00 AM – 1:00 PM',
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }
}

// ── Data & Privacy bottom sheet: note on how patient data is handled ─────────
class _DataPrivacySheet extends StatelessWidget {
  const _DataPrivacySheet();

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      expand: false,
      initialChildSize: 0.6,
      maxChildSize: 0.9,
      builder: (_, ctrl) => Padding(
        padding: const EdgeInsets.all(20),
        child: ListView(
          controller: ctrl,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFDDE1E8),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            const Text('Data & Privacy',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.textDark)),
            const SizedBox(height: 4),
            const Text('How patient information is stored and used',
                style: TextStyle(fontSize: 12.5, color: AppColors.textGrey)),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFF4F6FB),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _PrivacyPoint(
                    icon: Icons.lock_outline_rounded,
                    text: 'Patient records are only accessible to authorized Admin and Staff '
                        'accounts based on their assigned role and permissions.',
                  ),
                  SizedBox(height: 12),
                  _PrivacyPoint(
                    icon: Icons.storage_rounded,
                    text: 'Personal and medical information is stored securely and is not shared '
                        'with third parties outside the clinic\'s operations.',
                  ),
                  SizedBox(height: 12),
                  _PrivacyPoint(
                    icon: Icons.history_rounded,
                    text: 'Access to patient records may be logged for accountability and audit '
                        'purposes.',
                  ),
                  SizedBox(height: 12),
                  _PrivacyPoint(
                    icon: Icons.description_outlined,
                    text: 'Exported files (CSV, Excel, PDF) contain sensitive data and should be '
                        'handled and shared responsibly by staff.',
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }
}

class _PrivacyPoint extends StatelessWidget {
  final IconData icon;
  final String text;
  const _PrivacyPoint({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 16, color: AppColors.primary),
        const SizedBox(width: 10),
        Expanded(
          child: Text(text,
              style: const TextStyle(fontSize: 12.5, color: AppColors.textDark, height: 1.4)),
        ),
      ],
    );
  }
}

class _FaqItem {
  final String question;
  final String answer;
  const _FaqItem({required this.question, required this.answer});
}

class _FaqTile extends StatefulWidget {
  final _FaqItem item;
  const _FaqTile({required this.item});

  @override
  State<_FaqTile> createState() => _FaqTileState();
}

class _FaqTileState extends State<_FaqTile> {
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => setState(() => _expanded = !_expanded),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(widget.item.question,
                        style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600, color: AppColors.textDark)),
                  ),
                  Icon(
                    _expanded ? Icons.expand_less_rounded : Icons.expand_more_rounded,
                    color: AppColors.textGrey,
                    size: 20,
                  ),
                ],
              ),
              if (_expanded) ...[
                const SizedBox(height: 8),
                Text(widget.item.answer,
                    style: const TextStyle(fontSize: 12.5, color: AppColors.textGrey, height: 1.4)),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _ContactRow extends StatelessWidget {
  final IconData icon;
  final String label;
  const _ContactRow({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 16, color: AppColors.primary),
        const SizedBox(width: 10),
        Expanded(
          child: Text(label, style: const TextStyle(fontSize: 12.5, color: AppColors.textDark)),
        ),
      ],
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
          Icon(Icons.chevron_right_rounded, color: AppColors.textGrey.withValues(alpha: 0.6), size: 20),
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