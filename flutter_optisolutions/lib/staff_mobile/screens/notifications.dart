import 'package:flutter/material.dart';
import 'package:flutter_optisolutions/login.dart';
import '../services/api_service.dart';

class NotificationData {
  static List<Map<String, dynamic>> _notifications = [];

  static IconData _iconFromName(String? name) {
    switch (name) {
      case 'question_answer':
        return Icons.question_answer;
      case 'calendar_today':
        return Icons.calendar_today;
      case 'medical_services':
        return Icons.medical_services;
      case 'person':
        return Icons.person;
      case 'schedule':
        return Icons.schedule;
      case 'check_circle':
        return Icons.check_circle;
      case 'warning':
        return Icons.warning;
      default:
        return Icons.notifications;
    }
  }

  static Color _colorFromHex(String? hex) {
    if (hex == null || hex.isEmpty) return Colors.blueGrey;
    final cleanHex = hex.replaceFirst('#', '');
    return Color(int.parse('FF$cleanHex', radix: 16));
  }

  // ✅ Fetches the real list from Laravel. Returns the populated list too,
  // so callers can `await` it directly if they want.
  static Future<List<Map<String, dynamic>>> load() async {
    final result = await ApiService.get('/notifications');
    _notifications = (result as List).map<Map<String, dynamic>>((n) {
      return {
        'dbId': n['id'], // real database id, needed for markAsRead calls
        'icon': _iconFromName(n['icon']),
        'title': n['title'],
        'message': n['message'],
        'time': n['time'],
        'isRead': n['isRead'],
        'color': _colorFromHex(n['color']),
      };
    }).toList();
    return _notifications;
  }

  static int getUnreadCount() {
    return _notifications.where((n) => !n['isRead']).length;
  }

  static List<Map<String, dynamic>> getNotifications() {
    return _notifications;
  }

  static Future<void> markAsRead(int index) async {
    final dbId = _notifications[index]['dbId'];
    _notifications[index]['isRead'] = true; // update locally immediately
    try {
      await ApiService.patch('/notifications/$dbId/read', {});
    } catch (_) {
      // local UI already shows it as read; a failed sync here isn't critical,
      // next load() call will reconcile with the real server state
    }
  }

  static Future<void> markAllAsRead() async {
    for (var notification in _notifications) {
      notification['isRead'] = true;
    }
    try {
      await ApiService.post('/notifications/mark-all-read', {});
    } catch (_) {
      // same as above — local state already updated
    }
  }
}

class NotificationsPage extends StatefulWidget {
  const NotificationsPage({super.key});

  @override
  State<NotificationsPage> createState() => _NotificationsPageState();
}

class _NotificationsPageState extends State<NotificationsPage> {
  bool _loading = true;
  String? _loadError;

  @override
  void initState() {
    super.initState();
    _loadNotifications();
  }

  Future<void> _loadNotifications() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    try {
      await NotificationData.load();
      setState(() => _loading = false);
    } catch (e) {
      setState(() {
        _loadError = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  void _viewNotification(int index) {
    // ✅ Mark as read using static method (now syncs to the database)
    NotificationData.markAsRead(index);

    final notification = NotificationData.getNotifications()[index];

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            Icon(notification['icon'], color: notification['color']),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                notification['title'],
                style: const TextStyle(fontSize: 16),
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(notification['message'], style: const TextStyle(fontSize: 14)),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.access_time, size: 14, color: Colors.grey.shade400),
                const SizedBox(width: 4),
                Text(
                  notification['time'],
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade400),
                ),
              ],
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final notifications = NotificationData.getNotifications();

    return Scaffold(
      backgroundColor: Colors.grey.shade50,

      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1A237E)),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'Notifications',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 20,
            color: Color(0xFF1A237E),
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 1,
        centerTitle: false,
        actions: [
          TextButton(
            onPressed: () async {
              // ✅ Mark all as read using static method (syncs to database)
              await NotificationData.markAllAsRead();
              setState(() {});
              if (!context.mounted) return;
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('All notifications marked as read'),
                  backgroundColor: Colors.green,
                ),
              );
            },
            child: const Text(
              'Mark All Read',
              style: TextStyle(
                color: Color(0xFF1A237E),
                fontWeight: FontWeight.w500,
                fontSize: 13,
              ),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.logout, color: Color(0xFF1A237E)),
            onPressed: () {
              _showLogoutDialog(context);
            },
            tooltip: 'Logout',
          ),
        ],
      ),

      body: Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
            color: Colors.white,
            child: Row(
              children: [
                const Icon(
                  Icons.notifications_active,
                  color: Color(0xFF1A237E),
                  size: 20,
                ),
                const SizedBox(width: 8),
                Text(
                  '${notifications.length} Notifications',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    color: Color(0xFF1A237E),
                  ),
                ),
                const Spacer(),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.red,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '${NotificationData.getUnreadCount()} new',
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _loadError != null
                ? Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          _loadError!,
                          style: const TextStyle(color: Colors.red),
                        ),
                        const SizedBox(height: 12),
                        ElevatedButton(
                          onPressed: _loadNotifications,
                          child: const Text('Retry'),
                        ),
                      ],
                    ),
                  )
                : RefreshIndicator(
                    onRefresh: _loadNotifications,
                    child: ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: notifications.length,
                      itemBuilder: (context, index) {
                        final notification = notifications[index];
                        return _buildNotificationItem(
                          context,
                          index,
                          notification['icon'],
                          notification['title'],
                          notification['message'],
                          notification['time'],
                          notification['isRead'],
                          notification['color'],
                        );
                      },
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationItem(
    BuildContext context,
    int index,
    IconData icon,
    String title,
    String message,
    String time,
    bool isRead,
    Color color,
  ) {
    return GestureDetector(
      onTap: () {
        _viewNotification(index);
        setState(() {});
      },
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: isRead
              ? Colors.white
              : const Color(0xFF1A237E).withValues(alpha: 0.03),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isRead
                ? Colors.grey.shade200
                : const Color(0xFF1A237E).withValues(alpha: 0.1),
            width: 1,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withValues(alpha: 0.05),
              spreadRadius: 1,
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: color, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          title,
                          style: TextStyle(
                            fontWeight: isRead
                                ? FontWeight.w500
                                : FontWeight.bold,
                            fontSize: 14,
                            color: isRead
                                ? Colors.grey.shade700
                                : const Color(0xFF1A237E),
                          ),
                        ),
                      ),
                      if (!isRead)
                        Container(
                          width: 8,
                          height: 8,
                          decoration: const BoxDecoration(
                            color: Colors.blue,
                            shape: BoxShape.circle,
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    message,
                    style: TextStyle(
                      fontSize: 13,
                      color: isRead ? Colors.grey.shade600 : Colors.black87,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Icon(
                        Icons.access_time,
                        size: 12,
                        color: Colors.grey.shade400,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        time,
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.grey.shade400,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showLogoutDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Logout'),
        content: const Text('Are you sure you want to logout?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(context);
              await ApiService.logout();
              if (!context.mounted) return;
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (context) => const LoginScreen()),
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              foregroundColor: Colors.white,
            ),
            child: const Text('Logout'),
          ),
        ],
      ),
    );
  }
}
