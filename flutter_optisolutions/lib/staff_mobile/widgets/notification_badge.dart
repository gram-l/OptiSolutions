import 'dart:async';
import 'package:flutter/material.dart';
import '../screens/notifications.dart';

/// ✅ Ngayon self-fetching na ito: kinukuha nito mismo ang unread count
/// pagka-mount (hindi na umaasa na na-visit na ang NotificationsPage), at
/// nag-po-poll bawat ilang segundo para real-time na lumalabas ang badge
/// pag may bagong inquiry o schedule visit na dumating.
class NotificationBadge extends StatefulWidget {
  final VoidCallback onTap;

  /// Gaano kadalas mag-che-check ng bagong notification. Default 15s.
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
      // huwag hayaang mag-crash ang dashboard kapag walang internet/API error;
      // panatilihin na lang yung huling nakuhang count
    }
  }

  /// Tawagin ito (hal. sa onTap papunta sa NotificationsPage) kapag gusto
  /// mong agad mag-refresh ang badge pagbalik mula sa notifications screen.
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
            // pagbalik mula sa Notifications page, i-refresh agad ang badge
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
