// notifications.dart
import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import 'main.dart' show appMenuItems;

// ── Data model ────────────────────────────────────────────────────────────────
enum NotifType { inquiry, appointment, feedback, system }

class AppNotification {
  final String id;
  final NotifType type;
  final String title;
  final String body;
  final DateTime time;
  bool isRead;

  AppNotification({
    required this.id,
    required this.type,
    required this.title,
    required this.body,
    required this.time,
    this.isRead = false,
  });
}

// ── Sample data ───────────────────────────────────────────────────────────────
List<AppNotification> _sampleNotifications() {
  final now = DateTime.now();
  return [
    AppNotification(
      id: '1',
      type: NotifType.inquiry,
      title: 'New Chatbot Inquiry',
      body: 'Patient Maria Santos submitted a new inquiry about vaccination schedules.',
      time: now.subtract(const Duration(minutes: 5)),
    ),
    AppNotification(
      id: '2',
      type: NotifType.appointment,
      title: 'Appointment Request',
      body: 'Juan dela Cruz requested an appointment with Dr. Reyes on June 25 at 10:00 AM.',
      time: now.subtract(const Duration(minutes: 30)),
    ),
    AppNotification(
      id: '3',
      type: NotifType.feedback,
      title: 'New Patient Feedback',
      body: 'A patient left a 5-star review: "Excellent service and very accommodating staff!"',
      time: now.subtract(const Duration(hours: 1)),
      isRead: true,
    ),
    AppNotification(
      id: '4',
      type: NotifType.system,
      title: 'System Update',
      body: 'OptiSolutions has been updated to v2.1. New sentiment analysis features are now available.',
      time: now.subtract(const Duration(hours: 3)),
      isRead: true,
    ),
    AppNotification(
      id: '5',
      type: NotifType.appointment,
      title: 'Appointment Confirmed',
      body: 'Dr. Lara Cruz confirmed the appointment with Ana Reyes scheduled for June 24.',
      time: now.subtract(const Duration(hours: 5)),
      isRead: true,
    ),
    AppNotification(
      id: '6',
      type: NotifType.inquiry,
      title: 'Unresolved Inquiry',
      body: 'An inquiry from patient Carlo Mendoza has been pending for more than 24 hours.',
      time: now.subtract(const Duration(days: 1)),
      isRead: true,
    ),
    AppNotification(
      id: '7',
      type: NotifType.system,
      title: 'Backup Completed',
      body: 'Daily system backup completed successfully at 2:00 AM.',
      time: now.subtract(const Duration(days: 1, hours: 4)),
      isRead: true,
    ),
  ];
}

// ── Screen ────────────────────────────────────────────────────────────────────
class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  late List<AppNotification> _notifications;
  String _filter = 'All';

  final _filters = ['All', 'Unread', 'Inquiry', 'Appointment', 'Feedback', 'System'];

  @override
  void initState() {
    super.initState();
    _notifications = _sampleNotifications();
  }

  int get _unreadCount => _notifications.where((n) => !n.isRead).length;

  List<AppNotification> get _filtered {
    return _notifications.where((n) {
      if (_filter == 'Unread') return !n.isRead;
      if (_filter == 'Inquiry') return n.type == NotifType.inquiry;
      if (_filter == 'Appointment') return n.type == NotifType.appointment;
      if (_filter == 'Feedback') return n.type == NotifType.feedback;
      if (_filter == 'System') return n.type == NotifType.system;
      return true;
    }).toList();
  }

  void _markAllRead() {
    setState(() {
      for (final n in _notifications) {
        n.isRead = true;
      }
    });
  }

  void _markRead(String id) {
    setState(() {
      _notifications.firstWhere((n) => n.id == id).isRead = true;
    });
  }

  void _delete(String id) {
    setState(() => _notifications.removeWhere((n) => n.id == id));
  }

  void _navigateTo(String route) {
    if (route != '/notifications') Navigator.pushNamed(context, route);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/notifications',
        onItemTap: _navigateTo,
      ),
      body: SafeArea(
        child: Column(
          children: [
            Builder(
              builder: (ctx) => _TopBar(onMenuTap: () => Scaffold.of(ctx).openDrawer()),
            ),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Page title row
                        Row(
                          children: [
                            const Icon(Icons.notifications_none_rounded,
                                color: AppColors.textDark, size: 22),
                            const SizedBox(width: 8),
                            const Text(
                              'Notifications',
                              style: TextStyle(
                                  fontSize: 20,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.textDark),
                            ),
                            if (_unreadCount > 0) ...[
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 8, vertical: 2),
                                decoration: BoxDecoration(
                                  color: AppColors.primary,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  '$_unreadCount unread',
                                  style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 11,
                                      fontWeight: FontWeight.w600),
                                ),
                              ),
                            ],
                            const Spacer(),
                            if (_unreadCount > 0)
                              TextButton(
                                onPressed: _markAllRead,
                                style: TextButton.styleFrom(
                                  padding: EdgeInsets.zero,
                                  minimumSize: Size.zero,
                                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                ),
                                child: const Text('Mark all read',
                                    style: TextStyle(
                                        fontSize: 12, color: AppColors.primary)),
                              ),
                          ],
                        ),
                        const SizedBox(height: 2),
                        const Text(
                          'Stay updated on system activity',
                          style:
                              TextStyle(fontSize: 13, color: AppColors.textGrey),
                        ),
                        const SizedBox(height: 14),

                        // Filter chips
                        SizedBox(
                          height: 34,
                          child: ListView.separated(
                            scrollDirection: Axis.horizontal,
                            itemCount: _filters.length,
                            separatorBuilder: (_, __) => const SizedBox(width: 8),
                            itemBuilder: (context, i) {
                              final f = _filters[i];
                              final selected = _filter == f;
                              return GestureDetector(
                                onTap: () => setState(() => _filter = f),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 14, vertical: 7),
                                  decoration: BoxDecoration(
                                    color: selected
                                        ? AppColors.primary
                                        : Colors.white,
                                    borderRadius: BorderRadius.circular(20),
                                    border: Border.all(
                                      color: selected
                                          ? AppColors.primary
                                          : AppColors.border,
                                    ),
                                  ),
                                  child: Text(
                                    f,
                                    style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w500,
                                      color: selected
                                          ? Colors.white
                                          : AppColors.textDark,
                                    ),
                                  ),
                                ),
                              );
                            },
                          ),
                        ),
                        const SizedBox(height: 14),
                      ],
                    ),
                  ),

                  // List
                  Expanded(
                    child: _filtered.isEmpty
                        ? const _EmptyState()
                        : ListView.separated(
                            padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                            itemCount: _filtered.length,
                            separatorBuilder: (_, __) =>
                                const SizedBox(height: 8),
                            itemBuilder: (context, i) {
                              final notif = _filtered[i];
                              return _NotifCard(
                                notif: notif,
                                onTap: () => _markRead(notif.id),
                                onDelete: () => _delete(notif.id),
                              );
                            },
                          ),
                  ),
                ],
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
  final VoidCallback onMenuTap;
  const _TopBar({required this.onMenuTap});

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
                child: const Icon(Icons.local_hospital,
                    color: AppColors.primary, size: 18),
              ),
            ),
          ),
          const SizedBox(width: 8),
          const Text(
            'Polyclinic',
            style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
                color: AppColors.textDark),
          ),
          const Spacer(),
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
            decoration: BoxDecoration(
              color: AppColors.primary,
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Text(
              'Notifications',
              style: TextStyle(
                  color: Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.w500),
            ),
          ),
          const SizedBox(width: 10),
          const CircleAvatar(
            radius: 16,
            backgroundColor: AppColors.darkNavy,
            child: Text('DL',
                style: TextStyle(
                    color: Colors.white,
                    fontSize: 10,
                    fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }
}

// ── Notification card ─────────────────────────────────────────────────────────
class _NotifCard extends StatelessWidget {
  final AppNotification notif;
  final VoidCallback onTap;
  final VoidCallback onDelete;

  const _NotifCard({
    required this.notif,
    required this.onTap,
    required this.onDelete,
  });

  static const _typeConfig = {
    NotifType.inquiry: (
      icon: Icons.chat_bubble_outline_rounded,
      color: Color(0xFF1565C0),
      bg: Color(0xFFE3F2FD),
      label: 'Inquiry',
    ),
    NotifType.appointment: (
      icon: Icons.event_note_rounded,
      color: Color(0xFF388E3C),
      bg: Color(0xFFE8F5E9),
      label: 'Appointment',
    ),
    NotifType.feedback: (
      icon: Icons.star_border_rounded,
      color: Color(0xFFF9A825),
      bg: Color(0xFFFFFDE7),
      label: 'Feedback',
    ),
    NotifType.system: (
      icon: Icons.settings_outlined,
      color: Color(0xFF6D4C41),
      bg: Color(0xFFEFEBE9),
      label: 'System',
    ),
  };

  String _timeAgo(DateTime time) {
    final diff = DateTime.now().difference(time);
    if (diff.inMinutes < 1) return 'Just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    if (diff.inDays == 1) return 'Yesterday';
    return '${diff.inDays}d ago';
  }

  @override
  Widget build(BuildContext context) {
    final cfg = _typeConfig[notif.type]!;

    return Dismissible(
      key: Key(notif.id),
      direction: DismissDirection.endToStart,
      background: Container(
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 20),
        decoration: BoxDecoration(
          color: AppColors.deleteRed,
          borderRadius: BorderRadius.circular(12),
        ),
        child: const Icon(Icons.delete_outline_rounded,
            color: Colors.white, size: 22),
      ),
      onDismissed: (_) => onDelete(),
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: notif.isRead ? Colors.white : const Color(0xFFF0F5FF),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: notif.isRead
                  ? AppColors.border
                  : AppColors.primary.withOpacity(0.3),
            ),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Icon
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: cfg.bg,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(cfg.icon, color: cfg.color, size: 20),
              ),
              const SizedBox(width: 12),

              // Content
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 7, vertical: 2),
                          decoration: BoxDecoration(
                            color: cfg.bg,
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            cfg.label,
                            style: TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w600,
                                color: cfg.color),
                          ),
                        ),
                        const Spacer(),
                        Text(
                          _timeAgo(notif.time),
                          style: const TextStyle(
                              fontSize: 11, color: AppColors.textGrey),
                        ),
                        if (!notif.isRead) ...[
                          const SizedBox(width: 6),
                          Container(
                            width: 8,
                            height: 8,
                            decoration: const BoxDecoration(
                              color: AppColors.primary,
                              shape: BoxShape.circle,
                            ),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      notif.title,
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: notif.isRead
                            ? FontWeight.w500
                            : FontWeight.w700,
                        color: AppColors.textDark,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      notif.body,
                      style: const TextStyle(
                          fontSize: 12,
                          color: AppColors.textGrey,
                          height: 1.4),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ── Empty state ───────────────────────────────────────────────────────────────
class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text('🔔', style: TextStyle(fontSize: 52)),
          SizedBox(height: 16),
          Text(
            'No notifications here',
            style: TextStyle(
                fontSize: 15,
                color: AppColors.textGrey,
                fontWeight: FontWeight.w500),
          ),
          SizedBox(height: 4),
          Text(
            'You\'re all caught up!',
            style: TextStyle(fontSize: 12, color: AppColors.textGrey),
          ),
        ],
      ),
    );
  }
}