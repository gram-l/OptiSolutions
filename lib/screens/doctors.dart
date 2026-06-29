import 'package:flutter/material.dart';
import 'cliniclogin.dart';
import 'dashboard.dart';
import 'inquiries.dart';
import 'appointments.dart';
import 'patients.dart';
import 'profile.dart';
import 'notifications.dart';
import 'settings.dart';
import '../widgets/notification_badge.dart';

class DoctorsPage extends StatefulWidget {
  const DoctorsPage({super.key});

  @override
  State<DoctorsPage> createState() => _DoctorsPageState();
}

class _DoctorsPageState extends State<DoctorsPage> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  String _selectedDepartment = 'All Departments';

  // Sample doctors data
  final List<Map<String, dynamic>> _allDoctors = [
    {
      'name': 'Dr. Kent Lee',
      'specialty': 'Cardiology',
      'status': 'Available',
      'schedule': 'Mon - Thurs',
      'time': '9:00am - 3:00pm',
      'avatar': 'KL',
      'color': Colors.blue,
    },
    {
      'name': 'Dr. Ben Miller',
      'specialty': 'Neurologist',
      'status': 'Unavailable',
      'schedule': 'Tues - Thurs',
      'time': '8:00am - 1:00pm',
      'avatar': 'BM',
      'color': Colors.red,
    },
    {
      'name': 'Dr. Klain Smith',
      'specialty': 'Ophthalmology',
      'status': 'Unavailable',
      'schedule': 'Thurs - Sun',
      'time': '10:00am - 4:00pm',
      'avatar': 'KS',
      'color': Colors.orange,
    },
    {
      'name': 'Dr. Khey Mendoza',
      'specialty': 'OB-Gyne',
      'status': 'Available',
      'schedule': 'Mon - Fri',
      'time': '8:00am - 3:00pm',
      'avatar': 'KM',
      'color': Colors.green,
    },
    {
      'name': 'Dr. Sarah Reyes',
      'specialty': 'Pediatrics',
      'status': 'Available',
      'schedule': 'Mon - Wed',
      'time': '9:00am - 2:00pm',
      'avatar': 'SR',
      'color': Colors.purple,
    },
    {
      'name': 'Dr. Mark Cruz',
      'specialty': 'Orthopedics',
      'status': 'Unavailable',
      'schedule': 'Fri - Sun',
      'time': '10:00am - 5:00pm',
      'avatar': 'MC',
      'color': Colors.teal,
    },
  ];

  // Get unique departments for filter
  List<String> get _departments {
    List<String> depts = _allDoctors
        .map((doc) => doc['specialty'] as String)
        .toSet()
        .toList();
    depts.sort();
    return ['All Departments', ...depts];
  }

  // Get filtered doctors by department and search
  List<Map<String, dynamic>> get _filteredDoctors {
    List<Map<String, dynamic>> result = List.from(_allDoctors);

    // Filter by department
    if (_selectedDepartment != 'All Departments') {
      result = result
          .where((doc) => doc['specialty'] == _selectedDepartment)
          .toList();
    }

    // Filter by search query
    if (_searchQuery.isNotEmpty) {
      final query = _searchQuery.toLowerCase();
      result = result.where((doc) {
        return doc['name'].toLowerCase().contains(query) ||
            doc['specialty'].toLowerCase().contains(query);
      }).toList();
    }

    return result;
  }

  // Toggle availability status
  void _toggleAvailability(String name) {
    setState(() {
      final index = _allDoctors.indexWhere((doc) => doc['name'] == name);
      if (index != -1) {
        final updatedDoctor = Map<String, dynamic>.from(_allDoctors[index]);
        updatedDoctor['status'] = updatedDoctor['status'] == 'Available'
            ? 'Unavailable'
            : 'Available';
        _allDoctors[index] = updatedDoctor;
      }
    });

    final doctor = _allDoctors.firstWhere((doc) => doc['name'] == name);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('${doctor['name']} is now ${doctor['status']}'),
        backgroundColor: doctor['status'] == 'Available'
            ? Colors.green
            : Colors.red,
      ),
    );
  }

  // Edit Schedule
  void _editSchedule(Map<String, dynamic> doctor) {
    TextEditingController daysController = TextEditingController(
      text: doctor['schedule'],
    );
    TextEditingController timeController = TextEditingController(
      text: doctor['time'],
    );

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setStateDialog) {
          return AlertDialog(
            title: Text('Edit Schedule - ${doctor['name']}'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: daysController,
                  decoration: const InputDecoration(
                    labelText: 'Days (e.g. Mon - Fri)',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.calendar_today),
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: timeController,
                  decoration: const InputDecoration(
                    labelText: 'Time (e.g. 8:00am - 3:00pm)',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.access_time),
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.info, size: 16, color: Colors.grey),
                      const SizedBox(width: 8),
                      Text(
                        'Current: ${doctor['schedule']} | ${doctor['time']}',
                        style: const TextStyle(
                          fontSize: 12,
                          color: Colors.grey,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: () {
                  setState(() {
                    final index = _allDoctors.indexWhere(
                      (d) => d['name'] == doctor['name'],
                    );
                    if (index != -1) {
                      final updatedDoctor = Map<String, dynamic>.from(
                        _allDoctors[index],
                      );
                      if (daysController.text.trim().isNotEmpty) {
                        updatedDoctor['schedule'] = daysController.text.trim();
                      }
                      if (timeController.text.trim().isNotEmpty) {
                        updatedDoctor['time'] = timeController.text.trim();
                      }
                      _allDoctors[index] = updatedDoctor;
                    }
                  });
                  Navigator.pop(context);
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Schedule updated successfully!'),
                      backgroundColor: Colors.green,
                    ),
                  );
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF1A237E),
                  foregroundColor: Colors.white,
                ),
                child: const Text('Save'),
              ),
            ],
          );
        },
      ),
    );
  }

  // Download data
  void _downloadData() {
    final doctorsToDownload = _filteredDoctors;

    if (doctorsToDownload.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No doctors to download'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.download_done, color: Colors.green),
            SizedBox(width: 8),
            Text('Download Successful'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.description, size: 50, color: Colors.blue),
            const SizedBox(height: 8),
            Text('Downloaded ${doctorsToDownload.length} doctor(s)'),
            Text(
              'Department: $_selectedDepartment',
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
            child: _filteredDoctors.isEmpty
                ? _buildEmptyState()
                : ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _filteredDoctors.length,
                    itemBuilder: (context, index) {
                      final doctor = _filteredDoctors[index];
                      return _buildDoctorCard(doctor);
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
          _buildDrawerItem(
            Icons.help,
            'Help',
            false,
            () => Navigator.pop(context),
          ),
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

  // Header with Download
  Widget _buildHeader() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      color: Colors.white,
      child: Row(
        children: [
          const Icon(
            Icons.medical_services,
            color: Color(0xFF1A237E),
            size: 28,
          ),
          const SizedBox(width: 12),
          const Text(
            'Doctors',
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
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
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
                  hintText: 'Search doctors...',
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

          // Department Filter - CHANGED from doctor names to departments
          Container(
            height: 40,
            padding: const EdgeInsets.symmetric(horizontal: 8),
            decoration: BoxDecoration(
              color: Colors.grey.shade100,
              borderRadius: BorderRadius.circular(8),
            ),
            child: DropdownButton<String>(
              value: _selectedDepartment,
              underline: const SizedBox(),
              icon: const Icon(Icons.arrow_drop_down, color: Color(0xFF1A237E)),
              style: const TextStyle(
                fontSize: 13,
                color: Color(0xFF1A237E),
                fontWeight: FontWeight.w500,
              ),
              onChanged: (String? newValue) {
                setState(() {
                  _selectedDepartment = newValue!;
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
          Icon(Icons.medical_services, size: 60, color: Colors.grey.shade400),
          const SizedBox(height: 12),
          Text(
            'No doctors found in this department',
            style: TextStyle(fontSize: 16, color: Colors.grey.shade500),
          ),
        ],
      ),
    );
  }

  // Doctor Card
  Widget _buildDoctorCard(Map<String, dynamic> doctor) {
    final bool isAvailable = doctor['status'] == 'Available';

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
          // Doctor Info Row
          Row(
            children: [
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: doctor['color'].withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    doctor['avatar'],
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: doctor['color'],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      doctor['name'],
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF1A237E),
                      ),
                    ),
                    // Department/Specialty badge
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1A237E).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        doctor['specialty'],
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                          color: Color(0xFF1A237E),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              // Status Badge
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: isAvailable
                      ? Colors.green.withValues(alpha: 0.1)
                      : Colors.red.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: isAvailable
                        ? Colors.green.withValues(alpha: 0.3)
                        : Colors.red.withValues(alpha: 0.3),
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 6,
                      height: 6,
                      decoration: BoxDecoration(
                        color: isAvailable ? Colors.green : Colors.red,
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      doctor['status'],
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: isAvailable ? Colors.green : Colors.red,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Schedule
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.grey.shade50,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: [
                const Icon(Icons.calendar_today, size: 14, color: Colors.grey),
                const SizedBox(width: 8),
                Text(
                  doctor['schedule'],
                  style: const TextStyle(fontSize: 13, color: Colors.black87),
                ),
                const SizedBox(width: 16),
                const Icon(Icons.access_time, size: 14, color: Colors.grey),
                const SizedBox(width: 8),
                Text(
                  doctor['time'],
                  style: const TextStyle(fontSize: 13, color: Colors.black87),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          // Buttons: Availability and Edit Schedule
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _toggleAvailability(doctor['name']),
                  label: const Text('Availability'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(
                      0xFF1A237E,
                    ).withValues(alpha: 0.05),
                    foregroundColor: const Color(0xFF1A237E),
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                      side: BorderSide(
                        color: const Color(0xFF1A237E).withValues(alpha: 0.2),
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              // Edit Schedule Button
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _editSchedule(doctor),
                  icon: const Icon(Icons.edit, size: 16),
                  label: const Text('Edit Schedule'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(
                      0xFF1A237E,
                    ).withValues(alpha: 0.05),
                    foregroundColor: const Color(0xFF1A237E),
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                      side: BorderSide(
                        color: const Color(0xFF1A237E).withValues(alpha: 0.2),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
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
        currentIndex: 3,
        onTap: (index) {
          _searchController.clear();
          setState(() {
            _searchQuery = '';
          });

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
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(
                  builder: (context) => const AppointmentsPage(),
                ),
                (route) => false,
              );
              break;
            case 3:
              // Doctors na (current page)
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
