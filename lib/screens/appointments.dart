import 'package:flutter/material.dart';
import 'cliniclogin.dart';
import 'dashboard.dart';
import 'inquiries.dart';
import 'doctors.dart';
import 'patients.dart';
import 'profile.dart';
import 'notifications.dart';
import 'settings.dart';
import 'help.dart';
import '../widgets/notification_badge.dart';

class AppointmentsPage extends StatefulWidget {
  const AppointmentsPage({super.key});

  @override
  State<AppointmentsPage> createState() => _AppointmentsPageState();
}

class _AppointmentsPageState extends State<AppointmentsPage> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  String _selectedFilter = 'All Departments';

  // Get unique departments for filter
  List<String> get _departments {
    List<String> depts = _allAppointments
        .map((app) => app['department'] as String)
        .toSet()
        .toList();
    depts.sort();
    return ['All Departments', ...depts];
  }

  // Sample appointments data - REMOVED status and time
  final List<Map<String, dynamic>> _allAppointments = [
    {
      'id': 'APP-001',
      'patientName': 'Juan Dela Cruz',
      'doctor': 'Dr. Lee',
      'department': 'Cardiology',
      'date': '2026-06-25',
    },
    {
      'id': 'APP-002',
      'patientName': 'Jasmin Davis',
      'doctor': 'Dr. Mendoza',
      'department': 'OB-Gyne',
      'date': '2026-06-26',
    },
    {
      'id': 'APP-003',
      'patientName': 'Maria Oh',
      'doctor': 'Dr. Chu',
      'department': 'Pulmonology',
      'date': '2026-06-27',
    },
    {
      'id': 'APP-004',
      'patientName': 'Shinne Cruz',
      'doctor': 'Dr. Smith',
      'department': 'Ophthalmology',
      'date': '2026-06-28',
    },
    {
      'id': 'APP-005',
      'patientName': 'Ann Santos',
      'doctor': 'Dr. Miller',
      'department': 'Neurology',
      'date': '2026-06-29',
    },
    {
      'id': 'APP-006',
      'patientName': 'Robert Tan',
      'doctor': 'Dr. Reyes',
      'department': 'Orthopedics',
      'date': '2026-06-24',
    },
    {
      'id': 'APP-007',
      'patientName': 'Sarah Lim',
      'doctor': 'Dr. Garcia',
      'department': 'Pediatrics',
      'date': '2026-06-30',
    },
  ];

  // Get filtered appointments by department
  List<Map<String, dynamic>> get _filteredAppointments {
    List<Map<String, dynamic>> result = List.from(_allAppointments);

    if (_selectedFilter != 'All Departments') {
      result = result
          .where((app) => app['department'] == _selectedFilter)
          .toList();
    }

    if (_searchQuery.isNotEmpty) {
      final query = _searchQuery.toLowerCase();
      result = result.where((app) {
        return app['patientName'].toLowerCase().contains(query) ||
            app['doctor'].toLowerCase().contains(query) ||
            app['department'].toLowerCase().contains(query) ||
            app['id'].toLowerCase().contains(query);
      }).toList();
    }

    return result;
  }

  // View appointment details - REMOVED time and status
  void _viewAppointment(Map<String, dynamic> appointment) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(appointment['patientName']),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('ID: ${appointment['id']}'),
            Text('Doctor: ${appointment['doctor']}'),
            Text('Department: ${appointment['department']}'),
            Text('Date: ${appointment['date']}'),
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

  // Download filtered data
  void _downloadData() {
    final filtered = _filteredAppointments;
    if (filtered.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No data to download'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Download Successful'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.download_done, color: Colors.green, size: 60),
            const SizedBox(height: 10),
            Text('Downloaded ${filtered.length} appointment(s)'),
            Text(
              'Department: $_selectedFilter',
              style: const TextStyle(fontSize: 12, color: Colors.grey),
            ),
            if (_searchQuery.isNotEmpty)
              Text(
                'Search: "$_searchQuery"',
                style: const TextStyle(fontSize: 12, color: Colors.grey),
              ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      drawer: _buildDrawer(),
      appBar: _buildAppBar(),
      body: Column(
        children: [
          _buildHeader(),
          _buildSearchAndFilter(),
          Expanded(
            child: _filteredAppointments.isEmpty
                ? _buildEmptyState()
                : ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _filteredAppointments.length,
                    itemBuilder: (context, index) {
                      final appointment = _filteredAppointments[index];
                      return _buildAppointmentCard(appointment);
                    },
                  ),
          ),
        ],
      ),
      bottomNavigationBar: _buildBottomNav(),
    );
  }

  // ✅ UPDATED AppBar with NotificationBadge
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
        // ✅ NOTIFICATION BADGE
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
        // ✅ LOGOUT BUTTON
        IconButton(
          icon: const Icon(Icons.logout, color: Color(0xFF1A237E)),
          onPressed: () {
            _showLogoutDialog(context);
          },
        ),
      ],
    );
  }

  // ✅ UPDATED Drawer - REMOVED Notifications
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
          // ❌ REMOVED Notifications from drawer
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

  // Header - CHANGED to "Schedule Visits"
  Widget _buildHeader() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      color: Colors.white,
      child: Row(
        children: [
          const Icon(Icons.calendar_today, color: Color(0xFF1A237E), size: 28),
          const SizedBox(width: 12),
          const Text(
            'Schedule Visits',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1A237E),
            ),
          ),
          const Spacer(),
          ElevatedButton.icon(
            onPressed: _downloadData,
            icon: const Icon(Icons.download, size: 18),
            label: const Text('Download'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF1A237E),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // Search and Filter - CHANGED to department filter
  Widget _buildSearchAndFilter() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      color: Colors.white,
      child: Row(
        children: [
          // Search
          Expanded(
            child: Container(
              height: 40,
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(8),
              ),
              child: TextField(
                controller: _searchController,
                onChanged: (value) => setState(() => _searchQuery = value),
                decoration: InputDecoration(
                  hintText: 'Search schedule visits...',
                  hintStyle: const TextStyle(fontSize: 13, color: Colors.grey),
                  prefixIcon: const Icon(
                    Icons.search,
                    size: 18,
                    color: Colors.grey,
                  ),
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(vertical: 8),
                  suffixIcon: _searchQuery.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear, size: 18),
                          onPressed: () => setState(() {
                            _searchController.clear();
                            _searchQuery = '';
                          }),
                        )
                      : null,
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),

          // Department Filter
          Container(
            height: 40,
            padding: const EdgeInsets.symmetric(horizontal: 8),
            decoration: BoxDecoration(
              color: Colors.grey.shade100,
              borderRadius: BorderRadius.circular(8),
            ),
            child: DropdownButton<String>(
              value: _selectedFilter,
              underline: const SizedBox(),
              icon: const Icon(Icons.arrow_drop_down, color: Color(0xFF1A237E)),
              style: const TextStyle(
                fontSize: 13,
                color: Color(0xFF1A237E),
                fontWeight: FontWeight.w500,
              ),
              onChanged: (String? newValue) {
                setState(() {
                  _selectedFilter = newValue!;
                });
              },
              items: _departments.map<DropdownMenuItem<String>>((String value) {
                return DropdownMenuItem<String>(
                  value: value,
                  child: Text(value, style: const TextStyle(fontSize: 13)),
                );
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.calendar_today, size: 60, color: Colors.grey.shade400),
          const SizedBox(height: 12),
          Text(
            'No schedule visits found',
            style: TextStyle(fontSize: 16, color: Colors.grey.shade500),
          ),
        ],
      ),
    );
  }

  // Appointment Card
  Widget _buildAppointmentCard(Map<String, dynamic> appointment) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
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
          Row(
            children: [
              Expanded(
                child: Text(
                  appointment['patientName'],
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1A237E),
                  ),
                ),
              ),
              // Department badge
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xFF1A237E).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  appointment['department'],
                  style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1A237E),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              const Icon(Icons.medical_services, size: 16, color: Colors.grey),
              const SizedBox(width: 4),
              Text(
                appointment['doctor'],
                style: const TextStyle(fontSize: 14, color: Colors.black87),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Row(
            children: [
              const Icon(Icons.calendar_today, size: 14, color: Colors.grey),
              const SizedBox(width: 4),
              Text(
                appointment['date'],
                style: const TextStyle(fontSize: 13, color: Colors.grey),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: ElevatedButton(
                  onPressed: () => _viewAppointment(appointment),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF1A237E),
                    foregroundColor: Colors.white,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                    minimumSize: const Size(0, 36),
                  ),
                  child: const Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.visibility, size: 16),
                      SizedBox(width: 6),
                      Text(
                        'View Details',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // Bottom Navigation
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
        currentIndex: 2,
        onTap: (index) {
          switch (index) {
            case 0:
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (context) => const Dashboard()),
                (route) => false,
              );
              break;
            case 1:
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (context) => const InquiriesPage()),
                (route) => false,
              );
              break;
            case 2:
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

  // ✅ ADD Logout Dialog
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
