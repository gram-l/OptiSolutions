import 'dart:async';
import 'package:flutter/material.dart';
import '../screens/notifications.dart';

class NotificationBadge extends StatefulWidget {
  final VoidCallback onTap;

  final Duration refreshInterval;

  const NotificationBadge({
    super.key,
    required this.onTap,
    this.refreshInterval = const Duration(seconds: 15),
  });

  @override
  State<NotificationBadge> createState() => _NotificationBadgeState();
}

class _NotificationBadgeState extends State<NotificationBadge> {
  int _unreadCount = 0;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _refresh(); // agad kunin pagka-load ng dashboard
    _timer = Timer.periodic(widget.refreshInterval, (_) => _refresh());
  }

  Future<void> _refresh() async {
    try {
      await NotificationData.load();
      if (!mounted) return;
      setState(() {
        _unreadCount = NotificationData.getUnreadCount();
      });
    } catch (_) {

    }
  }

  Future<void> refreshNow() => _refresh();

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      alignment: Alignment.center,
      children: [
        IconButton(
          icon: const Icon(Icons.notifications, color: Color(0xFF1A237E)),
          onPressed: () async {
            widget.onTap();
          
            await _refresh();
          },
          tooltip: 'Notifications',
        ),
        if (_unreadCount > 0)
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
                _unreadCount > 9 ? '9+' : '$_unreadCount',
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
