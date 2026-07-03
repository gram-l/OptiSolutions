// widgets/notification_badge.dart
// A bell icon button with a small red unread-count badge, used in every
// page's AppBar. Reads the unread count from NotificationData, which is
// populated by calling NotificationData.load() (done in notifications.dart's
// initState). If you want the badge to be accurate before the Notifications
// page has ever been opened, call NotificationData.load() once on Dashboard's
// initState too — see the note in dashboard.dart.

import 'package:flutter/material.dart';
import '../screens/notifications.dart';

class NotificationBadge extends StatelessWidget {
  final VoidCallback onTap;

  const NotificationBadge({super.key, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final unreadCount = NotificationData.getUnreadCount();

    return Stack(
      alignment: Alignment.center,
      children: [
        IconButton(
          icon: const Icon(Icons.notifications, color: Color(0xFF1A237E)),
          onPressed: onTap,
          tooltip: 'Notifications',
        ),
        if (unreadCount > 0)
          Positioned(
            right: 6,
            top: 6,
            child: Container(
              padding: const EdgeInsets.all(3),
              decoration: const BoxDecoration(
                color: Colors.red,
                shape: BoxShape.circle,
              ),
              constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
              child: Text(
                unreadCount > 9 ? '9+' : '$unreadCount',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 9,
                  fontWeight: FontWeight.bold,
                ),
                textAlign: TextAlign.center,
              ),
            ),
          ),
      ],
    );
  }
}
