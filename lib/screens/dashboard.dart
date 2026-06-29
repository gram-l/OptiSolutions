import 'package:flutter/material.dart';
import 'cliniclogin.dart';
import 'inquiries.dart';
import 'appointments.dart';
import 'doctors.dart';
import 'patients.dart';
import 'profile.dart';
import 'settings.dart';
import 'help.dart';
import 'notifications.dart';
import '../widgets/notification_badge.dart';

class Dashboard extends StatefulWidget {
  const Dashboard({super.key});

  @override
  State<Dashboard> createState() => _DashboardState();
}

class _DashboardState extends State<Dashboard> {
  // Sample recent activities data
  final List<Map<String, dynamic>> _recentActivities = [
    {
      'icon': Icons.calendar_today,
      'title': 'New Schedule Visit',
      'description': 'Juan Dela Cruz booked an appointment with Dr. Lee',
      'time': '2 minutes ago',
      'color': Colors.blue,
    },
    {
      'icon': Icons.question_answer,
      'title': 'New Inquiry',
      'description': 'Maria Santos sent a new inquiry about eye consultation',
      'time': '15 minutes ago',
      'color': Colors.green,
    },
    {
      'icon': Icons.medical_services,
      'title': 'Doctor Status Updated',
      'description': 'Dr. Mendoza is now available for appointments',
      'time': '1 hour ago',
      'color': Colors.purple,
    },
    {
      'icon': Icons.person_add,
      'title': 'New Patient Registered',
      'description': 'Robert Lim has been registered as a new patient',
      'time': '2 hours ago',
      'color': Colors.orange,
    },
    {
      'icon': Icons.check_circle,
      'title': 'Inquiry Resolved',
      'description': 'Inquiry from Anna Rivera has been resolved',
      'time': '3 hours ago',
      'color': Colors.teal,
    },
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: _buildAppBar(),
      drawer: _buildDrawer(),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ✅ Greeting with colored background - NO CIRCLE
            _buildGreetingCard(),
            const SizedBox(height: 20),
            // ✅ Stats Cards - 4 cards in 2 rows
            _buildStatsGrid(),
            const SizedBox(height: 20),
            // Inquiry Volume Chart
            _buildInquiryVolume(),
            const SizedBox(height: 20),
            // ✅ Sentiment Analysis - renamed to "Patient Feedback Overview"
            _buildSentimentAnalysis(),
            const SizedBox(height: 20),
            // ✅ Recent Activities
            _buildRecentActivities(),
          ],
        ),
      ),
      bottomNavigationBar: _buildBottomNav(),
    );
  }

  // ✅ Greeting Card with colored background - NO CIRCLE
  Widget _buildGreetingCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF1A237E), Color(0xFF283593)],
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF1A237E).withValues(alpha: 0.3),
            spreadRadius: 2,
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: const Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Welcome back, Staff!',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
          SizedBox(height: 4),
          Text(
            "Here's what's happening at the clinic today",
            style: TextStyle(fontSize: 14, color: Colors.white70),
          ),
        ],
      ),
    );
  }

  // ✅ 4 Stats Cards (2 rows of 2)
  Widget _buildStatsGrid() {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                '15',
                'Schedule Visits',
                Icons.calendar_today,
                Colors.blue,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildStatCard(
                '8',
                'Pending Inquiries',
                Icons.question_answer,
                Colors.orange,
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                '6',
                'Active Doctors',
                Icons.medical_services,
                Colors.green,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildStatCard(
                '10',
                'Registered Patients',
                Icons.people,
                Colors.purple,
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildStatCard(
    String number,
    String label,
    IconData icon,
    Color color,
  ) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(height: 8),
          Text(
            number,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1A237E),
            ),
          ),
          Text(
            label,
            style: const TextStyle(fontSize: 11, color: Colors.grey),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  Widget _buildInquiryVolume() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.bar_chart, color: Color(0xFF1A237E), size: 20),
              SizedBox(width: 8),
              Text(
                'Inquiry Volume',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1A237E),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              'Mon',
              'Tue',
              'Wed',
              'Thu',
              'Fri',
              'Sat',
              'Sun',
            ].map((day) => _buildDayBar(day)).toList(),
          ),
        ],
      ),
    );
  }

  Widget _buildDayBar(String day) {
    return Column(
      children: [
        Container(
          width: 20,
          height:
              40 +
              [5, 15, 10, 25, 20, 8, 12][[
                    'Mon',
                    'Tue',
                    'Wed',
                    'Thu',
                    'Fri',
                    'Sat',
                    'Sun',
                  ].indexOf(day)]
                  .toDouble(),
          decoration: BoxDecoration(
            color: const Color(0xFF1A237E),
            borderRadius: BorderRadius.circular(4),
          ),
        ),
        const SizedBox(height: 4),
        Text(day, style: const TextStyle(fontSize: 10, color: Colors.grey)),
      ],
    );
  }

  // ✅ CHANGED: "Sentiment Analysis" to "Patient Feedback Overview"
  Widget _buildSentimentAnalysis() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.feedback, color: Color(0xFF1A237E), size: 20),
              SizedBox(width: 8),
              Text(
                'Patient Feedback Overview',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1A237E),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          const Text(
            'Based on recent patient interactions',
            style: TextStyle(fontSize: 12, color: Colors.grey),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              _buildSentimentCard('Positive', 65, Colors.green),
              const SizedBox(width: 8),
              _buildSentimentCard('Neutral', 25, Colors.orange),
              const SizedBox(width: 8),
              _buildSentimentCard('Negative', 10, Colors.red),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSentimentCard(String label, int percentage, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: color.withValues(alpha: 0.3)),
        ),
        child: Column(
          children: [
            Text(
              '$percentage%',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            Text(
              label,
              style: const TextStyle(fontSize: 11, color: Colors.grey),
            ),
          ],
        ),
      ),
    );
  }

  // ✅ Recent Activities
  Widget _buildRecentActivities() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.access_time, color: Color(0xFF1A237E), size: 20),
              SizedBox(width: 8),
              Text(
                'Recent Activities',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1A237E),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          ..._recentActivities.map((activity) => _buildActivityItem(activity)),
        ],
      ),
    );
  }

  Widget _buildActivityItem(Map<String, dynamic> activity) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: BoxDecoration(
        border: Border(
          bottom: BorderSide(color: Colors.grey.shade200, width: 1),
        ),
      ),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: activity['color'].withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(activity['icon'], color: activity['color'], size: 18),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  activity['title'],
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF1A237E),
                  ),
                ),
                Text(
                  activity['description'],
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          Text(
            activity['time'],
            style: TextStyle(fontSize: 10, color: Colors.grey.shade400),
          ),
        ],
      ),
    );
  }

  PreferredSizeWidget _buildAppBar() {
    return AppBar(
      title: Row(
        children: [
          Image.asset(
            'assets/PCLOGO.png',
            width: 35,
            height: 35,
            fit: BoxFit.contain,
          ),
          const SizedBox(width: 12),
          const Text(
            'Polyclinic',
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 22,
              letterSpacing: 0.5,
              color: Color(0xFF1A237E),
            ),
          ),
        ],
      ),
      backgroundColor: Colors.white,
      foregroundColor: const Color(0xFF1A237E),
      elevation: 2,
      centerTitle: false,
      iconTheme: const IconThemeData(color: Color(0xFF1A237E)),
      actions: [
        NotificationBadge(
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => const NotificationsPage(),
              ),
            );
          },
        ),
        IconButton(
          icon: const Icon(Icons.logout, color: Color(0xFF1A237E)),
          onPressed: () {
            _showLogoutDialog(context);
          },
        ),
      ],
    );
  }

  Widget _buildDrawer() {
    return Drawer(
      child: Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            color: const Color(0xFF1A237E),
            child: Column(
              children: [
                const SizedBox(height: 30),
                Container(
                  width: 90,
                  height: 90,
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                  ),
                  child: ClipOval(
                    child: Image.asset(
                      'assets/PCLOGO.png',
                      width: 80,
                      height: 80,
                      fit: BoxFit.contain,
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                const Text(
                  'Staff Name',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const Text(
                  'staff@polyclinic.com',
                  style: TextStyle(color: Colors.white70, fontSize: 13),
                ),
              ],
            ),
          ),
          _buildDrawerItem(Icons.person, 'Profile', false, () {
            Navigator.pop(context);
            Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => const ProfilePage()),
            );
          }),
          _buildDrawerItem(Icons.settings, 'Settings', false, () {
            Navigator.pop(context);
            Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => const SettingsPage()),
            );
          }),
          _buildDrawerItem(Icons.help, 'Help', false, () {
            Navigator.pop(context);
            Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => const HelpPage()),
            );
          }),
          const Divider(),
          _buildDrawerItem(Icons.logout, 'Logout', false, () {
            Navigator.pop(context);
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => const PCLogin()),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildDrawerItem(
    IconData icon,
    String title,
    bool isActive,
    VoidCallback onTap,
  ) {
    return ListTile(
      leading: Icon(
        icon,
        color: isActive ? const Color(0xFF1A237E) : Colors.grey.shade600,
      ),
      title: Text(
        title,
        style: TextStyle(
          fontWeight: isActive ? FontWeight.bold : FontWeight.normal,
          color: isActive ? const Color(0xFF1A237E) : Colors.grey.shade800,
        ),
      ),
      trailing: isActive
          ? Container(width: 4, height: 24, color: const Color(0xFF1A237E))
          : null,
      onTap: onTap,
    );
  }

  Widget _buildBottomNav() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.2),
            spreadRadius: 1,
            blurRadius: 8,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: BottomNavigationBar(
        type: BottomNavigationBarType.fixed,
        backgroundColor: Colors.white,
        selectedItemColor: const Color(0xFF1A237E),
        unselectedItemColor: Colors.grey.shade400,
        selectedFontSize: 11,
        unselectedFontSize: 11,
        currentIndex: 0,
        onTap: (index) {
          switch (index) {
            case 0:
              break;
            case 1:
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (context) => const InquiriesPage()),
                (route) => false,
              );
              break;
            case 2:
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(
                  builder: (context) => const AppointmentsPage(),
                ),
                (route) => false,
              );
              break;
            case 3:
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (context) => const DoctorsPage()),
                (route) => false,
              );
              break;
            case 4:
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (context) => const PatientsPage()),
                (route) => false,
              );
              break;
          }
        },
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.dashboard),
            label: 'Dashboard',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.question_answer),
            label: 'Inquiries',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.calendar_today),
            label: 'Schedule Visits',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.medical_services),
            label: 'Doctors',
          ),
          BottomNavigationBarItem(icon: Icon(Icons.people), label: 'Patients'),
        ],
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
