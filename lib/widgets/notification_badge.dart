// lib/widgets/notification_badge.dart
import 'package:flutter/material.dart';
import '../screens/notifications.dart';

class NotificationBadge extends StatelessWidget {
  final VoidCallback onTap;

  const NotificationBadge({super.key, required this.onTap});

  @override
  Widget build(BuildContext context) {
    // ✅ Tawag sa STATIC METHOD ng NotificationData class
    final unreadCount = NotificationData.getUnreadCount();

    return Stack(
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
              padding: const EdgeInsets.all(4),
              decoration: const BoxDecoration(
                color: Colors.red,
                shape: BoxShape.circle,
              ),
              constraints: const BoxConstraints(minWidth: 18, minHeight: 18),
              child: Text(
                unreadCount > 99 ? '99+' : '$unreadCount',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 10,
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
