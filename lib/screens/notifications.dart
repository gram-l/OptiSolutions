import 'package:flutter/material.dart';
import 'cliniclogin.dart';

// ✅ SEPARATE CLASS para sa notification data (hindi State)
class NotificationData {
  static List<Map<String, dynamic>> _notifications = [
    {
      'icon': Icons.question_answer,
      'title': 'New Inquiry Received',
      'message':
          'Juan Dela Cruz has sent a new inquiry about eye consultation.',
      'time': '2 minutes ago',
      'isRead': false,
      'color': Colors.blue,
    },
    {
      'icon': Icons.calendar_today,
      'title': 'Appointment Confirmed',
      'message':
          'Appointment with Dr. Lee has been confirmed for June 25, 2026 at 10:00 AM.',
      'time': '15 minutes ago',
      'isRead': false,
      'color': Colors.green,
    },
    {
      'icon': Icons.medical_services,
      'title': 'Doctor Availability Updated',
      'message': 'Dr. Marquez has updated their availability schedule.',
      'time': '1 hour ago',
      'isRead': false,
      'color': Colors.purple,
    },
    {
      'icon': Icons.person,
      'title': 'Patient Record Updated',
      'message': 'Michael Chu\'s medical records have been updated.',
      'time': '2 hours ago',
      'isRead': true,
      'color': Colors.orange,
    },
    {
      'icon': Icons.schedule,
      'title': 'Appointment Reminder',
      'message': 'Reminder: You have 3 pending appointments to review.',
      'time': '3 hours ago',
      'isRead': true,
      'color': Colors.red,
    },
    {
      'icon': Icons.check_circle,
      'title': 'Inquiry Resolved',
      'message': 'Inquiry from Maria Santos has been resolved successfully.',
      'time': '5 hours ago',
      'isRead': true,
      'color': Colors.teal,
    },
    {
      'icon': Icons.warning,
      'title': 'Low Stock Alert',
      'message': 'Some medications are running low in inventory.',
      'time': '1 day ago',
      'isRead': true,
      'color': Colors.amber,
    },
  ];

  // ✅ STATIC METHOD - accessible kahit saan
  static int getUnreadCount() {
    return _notifications.where((n) => !n['isRead']).length;
  }

  // ✅ STATIC METHOD - para makuha ang list
  static List<Map<String, dynamic>> getNotifications() {
    return _notifications;
  }

  // ✅ STATIC METHOD - para mag-mark ng read
  static void markAsRead(int index) {
    _notifications[index]['isRead'] = true;
  }

  // ✅ STATIC METHOD - para mark all as read
  static void markAllAsRead() {
    for (var notification in _notifications) {
      notification['isRead'] = true;
    }
  }
}

class NotificationsPage extends StatefulWidget {
  const NotificationsPage({super.key});

  @override
  State<NotificationsPage> createState() => _NotificationsPageState();
}

class _NotificationsPageState extends State<NotificationsPage> {
  void _viewNotification(int index) {
    // ✅ Mark as read using static method
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
            onPressed: () {
              // ✅ Mark all as read using static method
              NotificationData.markAllAsRead();
              setState(() {});
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
            onPressed: () {
              Navigator.pop(context);
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (context) => const PCLogin()),
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
