// appointments.dart
import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import 'main.dart' show appMenuItems;

class AppointmentsScreen extends StatefulWidget {
  const AppointmentsScreen({super.key});

  @override
  State<AppointmentsScreen> createState() => _AppointmentsScreenState();
}

class _AppointmentsScreenState extends State<AppointmentsScreen> {
  String _searchQuery = '';
  String _selectedStatus = 'All status';
  String _selectedDept = 'All dept';

  // Week navigation
  DateTime _weekStart = _getWeekStart(DateTime.now());

  static DateTime _getWeekStart(DateTime date) {
    return date.subtract(Duration(days: date.weekday - 1));
  }

  String get _weekLabel {
    final end = _weekStart.add(const Duration(days: 6));
    final months = [
      '', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    if (_weekStart.month == end.month) {
      return '${months[_weekStart.month]} ${_weekStart.day}–${end.day}';
    }
    return '${months[_weekStart.month]} ${_weekStart.day} – ${months[end.month]} ${end.day}';
  }

  void _prevWeek() => setState(() => _weekStart = _weekStart.subtract(const Duration(days: 7)));
  void _nextWeek() => setState(() => _weekStart = _weekStart.add(const Duration(days: 7)));

  // Sample appointments — empty for now to match the design
  List<Map<String, String>> get _filtered => [];

  void _navigateTo(String route) {
    if (route != '/appointments') Navigator.pushNamed(context, route);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/appointments',
        onItemTap: _navigateTo,
      ),
      body: SafeArea(
        child: Column(
          children: [
            _TopBar(
              weekLabel: _weekLabel,
              onMenuTap: () => Scaffold.of(context).openDrawer(),
            ),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const SizedBox(height: 16),

                    // Page title
                    Row(
                      children: const [
                        Icon(Icons.event_note_rounded, color: AppColors.textDark, size: 22),
                        SizedBox(width: 8),
                        Text(
                          'Appointments',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textDark,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 2),
                    const Text(
                      'Review & manage patient requests',
                      style: TextStyle(fontSize: 13, color: AppColors.textGrey),
                    ),
                    const SizedBox(height: 18),

                    // Search + filter row
                    Row(
                      children: [
                        // Search
                        Expanded(
                          flex: 3,
                          child: SizedBox(
                            height: 40,
                            child: TextField(
                              onChanged: (v) => setState(() => _searchQuery = v),
                              style: const TextStyle(fontSize: 13),
                              decoration: InputDecoration(
                                hintText: 'Search..',
                                hintStyle: const TextStyle(color: AppColors.textGrey, fontSize: 13),
                                prefixIcon: const Icon(Icons.search, size: 18, color: AppColors.textGrey),
                                filled: true,
                                fillColor: Colors.white,
                                contentPadding: EdgeInsets.zero,
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(10),
                                  borderSide: const BorderSide(color: AppColors.border),
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(10),
                                  borderSide: const BorderSide(color: AppColors.border),
                                ),
                                focusedBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(10),
                                  borderSide: const BorderSide(color: AppColors.primary),
                                ),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        // Status filter
                        _FilterChip(
                          value: _selectedStatus,
                          options: const ['All status', 'Pending', 'Confirmed', 'Cancelled', 'Completed'],
                          onChanged: (v) => setState(() => _selectedStatus = v),
                        ),
                        const SizedBox(width: 8),
                        // Dept filter
                        _FilterChip(
                          value: _selectedDept,
                          options: const ['All dept', 'General', 'Pediatrics', 'OB-GYN', 'Internal Medicine'],
                          onChanged: (v) => setState(() => _selectedDept = v),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    // Week navigator
                    Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        _WeekNavButton(icon: Icons.chevron_left, onTap: _prevWeek),
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                          decoration: BoxDecoration(
                            color: AppColors.darkNavy,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            _weekLabel,
                            style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w500),
                          ),
                        ),
                        const SizedBox(width: 6),
                        _WeekNavButton(icon: Icons.chevron_right, onTap: _nextWeek),
                      ],
                    ),
                    const SizedBox(height: 32),

                    // Empty state
                    if (_filtered.isEmpty) const _EmptyState(),

                    const SizedBox(height: 24),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Top Bar ───────────────────────────────────────────────────────────────────
class _TopBar extends StatelessWidget {
  final String weekLabel;
  final VoidCallback onMenuTap;
  const _TopBar({required this.weekLabel, required this.onMenuTap});

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        children: [
          IconButton(
            icon: const Icon(Icons.menu_rounded, color: AppColors.textDark),
            onPressed: onMenuTap,
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
          ),
          const SizedBox(width: 10),
          // Logo image
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: Image.asset(
              'assets/polyclinic_logo.png',
              width: 32,
              height: 32,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Container(
                width: 32,
                height: 32,
                decoration: BoxDecoration(
                  color: const Color(0xFFE3F2FD),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.local_hospital, color: AppColors.primary, size: 18),
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
          const SizedBox(width: 12),
          IconButton(
            icon: const Icon(Icons.logout_rounded, color: AppColors.textDark, size: 22),
            onPressed: () => Navigator.pushNamedAndRemoveUntil(context, '/', (_) => false),
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
          ),
        ],
      ),
    );
  }
}

// ── Filter chip / dropdown ────────────────────────────────────────────────────
class _FilterChip extends StatelessWidget {
  final String value;
  final List<String> options;
  final ValueChanged<String> onChanged;

  const _FilterChip({required this.value, required this.options, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () async {
        final picked = await showMenu<String>(
          context: context,
          position: RelativeRect.fromLTRB(
            MediaQuery.of(context).size.width,
            kToolbarHeight + 80,
            0,
            0,
          ),
          items: options
              .map((o) => PopupMenuItem(value: o, child: Text(o, style: const TextStyle(fontSize: 13))))
              .toList(),
        );
        if (picked != null) onChanged(picked);
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(value, style: const TextStyle(fontSize: 12, color: AppColors.textDark)),
            const SizedBox(width: 4),
            const Icon(Icons.keyboard_arrow_down_rounded, size: 14, color: AppColors.textGrey),
          ],
        ),
      ),
    );
  }
}

// ── Week nav button ───────────────────────────────────────────────────────────
class _WeekNavButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;

  const _WeekNavButton({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 28,
        height: 28,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(6),
          border: Border.all(color: AppColors.border),
        ),
        child: Icon(icon, size: 18, color: AppColors.textDark),
      ),
    );
  }
}

// ── Empty state ───────────────────────────────────────────────────────────────
class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 48),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: const [
            Text('📬', style: TextStyle(fontSize: 52)),
            SizedBox(height: 16),
            Text(
              'No appointments this week',
              style: TextStyle(
                fontSize: 15,
                color: AppColors.textGrey,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}