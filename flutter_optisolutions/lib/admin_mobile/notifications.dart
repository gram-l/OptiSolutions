// notifications.dart
import 'dart:async';
import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import '../main.dart' show appMenuItems;
import 'services/notification_service.dart';

// ── Data model ────────────────────────────────────────────────────────────────
enum NotifType { inquiry, feedback, system }

NotifType _typeFromString(String? type) {
  switch (type) {
    case 'chat_inquiry':
      return NotifType.inquiry;
    case 'feedback':
      return NotifType.feedback;
    default:
      return NotifType.system;
  }
}

class AppNotification {
  final int id;
  final NotifType type;
  final String title;
  final String body;
  final DateTime time;
  final bool isRead;

  AppNotification({
    required this.id,
    required this.type,
    required this.title,
    required this.body,
    required this.time,
    required this.isRead,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['notification_id'],
      type: _typeFromString(json['type']),
      title: json['title'] ?? '',
      body: json['message'] ?? '',
      time: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
      isRead: (json['is_read'] == 1 || json['is_read'] == true),
    );
  }
}

// ── Screen ────────────────────────────────────────────────────────────────────
class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  List<AppNotification> _notifications = [];
  bool _loading = true;
  String? _error;
  String _filter = 'All';
  Timer? _pollTimer;

  final _filters = ['All', 'Unread', 'Inquiry', 'Feedback', 'System'];

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
    // Poll every 30s so new chatbot inquiries / feedback show up without
    // needing a manual refresh — there's no push notification service wired
    // up yet, so this is a simple periodic check instead.
    _pollTimer = Timer.periodic(const Duration(seconds: 30), (_) => _fetchNotifications(silent: true));
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    super.dispose();
  }

  Future<void> _fetchNotifications({bool silent = false}) async {
    if (!silent) setState(() { _loading = true; _error = null; });
    try {
      final result = await NotificationService.fetchAll();
      final list = (result['notifications'] as List<Map<String, dynamic>>)
          .map((j) => AppNotification.fromJson(j))
          .toList();
      if (!mounted) return;
      setState(() { _notifications = list; _loading = false; });
    } catch (e) {
      if (!mounted) return;
      if (!silent) {
        setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
      }
    }
  }

  int get _unreadCount => _notifications.where((n) => !n.isRead).length;

  List<AppNotification> get _filtered {
    return _notifications.where((n) {
      if (_filter == 'Unread') return !n.isRead;
      if (_filter == 'Inquiry') return n.type == NotifType.inquiry;
      if (_filter == 'Feedback') return n.type == NotifType.feedback;
      if (_filter == 'System') return n.type == NotifType.system;
      return true;
    }).toList();
  }

  Future<void> _markAllRead() async {
    try {
      await NotificationService.markAllRead();
      await _fetchNotifications();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    }
  }

Future<void> _handleTap(AppNotification notif) async {
    try {
      await NotificationService.markRead(notif.id);
      await _fetchNotifications(silent: true);
    } catch (_) {
      // non-critical — proceed to navigate even if marking read failed
    }

    if (!mounted) return;

    switch (notif.type) {
      case NotifType.inquiry:
        Navigator.pushNamed(context, '/chatbot');
        break;
      case NotifType.feedback:
        Navigator.pushNamed(context, '/feedback');
        break;
      case NotifType.system:
        Navigator.pushNamed(context, '/dashboard');
        break;
    }
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
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                decoration: BoxDecoration(
                                  color: AppColors.primary,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  '$_unreadCount unread',
                                  style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600),
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
                                child: const Text('Mark all read', style: TextStyle(fontSize: 12, color: AppColors.primary)),
                              ),
                          ],
                        ),
                        const SizedBox(height: 2),
                        const Text('Stay updated on system activity',
                            style: TextStyle(fontSize: 13, color: AppColors.textGrey)),
                        const SizedBox(height: 14),

                        SizedBox(
                          height: 34,
                          child: ListView.separated(
                            scrollDirection: Axis.horizontal,
                            itemCount: _filters.length,
                            separatorBuilder: (_, _) => const SizedBox(width: 8),
                            itemBuilder: (context, i) {
                              final f = _filters[i];
                              final selected = _filter == f;
                              return GestureDetector(
                                onTap: () => setState(() => _filter = f),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                                  decoration: BoxDecoration(
                                    color: selected ? AppColors.primary : Colors.white,
                                    borderRadius: BorderRadius.circular(20),
                                    border: Border.all(color: selected ? AppColors.primary : AppColors.border),
                                  ),
                                  child: Text(
                                    f,
                                    style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w500,
                                      color: selected ? Colors.white : AppColors.textDark,
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

                  Expanded(
                    child: _loading
                        ? const Center(child: CircularProgressIndicator())
                        : _error != null
                            ? Center(
                                child: Column(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Text(_error!, textAlign: TextAlign.center),
                                    const SizedBox(height: 12),
                                    ElevatedButton(onPressed: _fetchNotifications, child: const Text('Retry')),
                                  ],
                                ),
                              )
                            : _filtered.isEmpty
                                ? const _EmptyState()
                                : RefreshIndicator(
                                    onRefresh: _fetchNotifications,
                                    child: ListView.separated(
                                      padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                                      itemCount: _filtered.length,
                                      separatorBuilder: (_, _) => const SizedBox(height: 8),
                                      itemBuilder: (context, i) {
                                        final notif = _filtered[i];
                                        return _NotifCard(
                                          notif: notif,
                                          onTap: () => _handleTap(notif),
                                        );
                                      },
                                    ),
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
              errorBuilder: (_, _, _) => Container(
                width: 32,
                height: 32,
                decoration: BoxDecoration(color: const Color(0xFFE3F2FD), borderRadius: BorderRadius.circular(8)),
                child: const Icon(Icons.local_hospital, color: AppColors.primary, size: 18),
              ),
            ),
          ),
          const SizedBox(width: 8),
          const Text('Polyclinic', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.textDark)),
          const Spacer(),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
            decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(20)),
            child: const Text('Notifications', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w500)),
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

  const _NotifCard({required this.notif, required this.onTap});

  static const _typeConfig = {
    NotifType.inquiry: (
      icon: Icons.chat_bubble_outline_rounded,
      color: Color(0xFF1565C0),
      bg: Color(0xFFE3F2FD),
      label: 'Inquiry',
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

    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: notif.isRead ? Colors.white : const Color(0xFFF0F5FF),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: notif.isRead ? AppColors.border : AppColors.primary.withValues(alpha: 0.3)),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(color: cfg.bg, borderRadius: BorderRadius.circular(10)),
              child: Icon(cfg.icon, color: cfg.color, size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                        decoration: BoxDecoration(color: cfg.bg, borderRadius: BorderRadius.circular(6)),
                        child: Text(cfg.label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: cfg.color)),
                      ),
                      const Spacer(),
                      Text(_timeAgo(notif.time), style: const TextStyle(fontSize: 11, color: AppColors.textGrey)),
                      if (!notif.isRead) ...[
                        const SizedBox(width: 6),
                        Container(
                          width: 8, height: 8,
                          decoration: const BoxDecoration(color: AppColors.primary, shape: BoxShape.circle),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    notif.title,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: notif.isRead ? FontWeight.w500 : FontWeight.w700,
                      color: AppColors.textDark,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(notif.body, style: const TextStyle(fontSize: 12, color: AppColors.textGrey, height: 1.4)),
                ],
              ),
            ),
          ],
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
          Text('No notifications here', style: TextStyle(fontSize: 15, color: AppColors.textGrey, fontWeight: FontWeight.w500)),
          SizedBox(height: 4),
          Text('You\'re all caught up!', style: TextStyle(fontSize: 12, color: AppColors.textGrey)),
        ],
      ),
    );
  }
}