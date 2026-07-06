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
import '../services/api_service.dart';

class AppointmentsPage extends StatefulWidget {
  const AppointmentsPage({super.key});

  @override
  State<AppointmentsPage> createState() => _AppointmentsPageState();
}

class _AppointmentsPageState extends State<AppointmentsPage> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  List<Map<String, dynamic>> _allAppointments = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchAppointments();
  }

  Future<void> _fetchAppointments() async {
    try {
      setState(() {
        _isLoading = true;
        _error = null;
      });
      final data = await ApiService.get('/appointments');

      // Handle both list and paginated response
      final List list = data is List ? data : (data['data'] ?? []);

      setState(() {
        _allAppointments = list
            .map(
              (apt) => {
                // appointment_id ang primary key sa DB, hindi 'id'
                'id': 'APP-${apt['appointment_id']}',
                // 'patient' at 'doctor' ay plain String na sa response,
                // hindi na sila nested Map kaya walang ?['patient_name']
                'patientName': apt['patient'] ?? 'N/A',
                'doctor': apt['doctor'] ?? 'N/A',
                // walang department/service_type column sa DB, ginamit
                // na lang ang 'status' bilang filterable field
                'status': apt['status'] ?? 'N/A',
                'date': _formatDate(apt['date']),
              },
            )
            .toList();
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  String _formatDate(dynamic rawDate) {
    if (rawDate == null) return 'N/A';
    final str = rawDate.toString();
    // Kunin lang ang date part (YYYY-MM-DD), tanggalin ang time kung meron
    return str.split('T').first;
  }

  List<Map<String, dynamic>> get _filteredAppointments {
    List<Map<String, dynamic>> result = List.from(_allAppointments);

    if (_searchQuery.isNotEmpty) {
      final query = _searchQuery.toLowerCase();
      result = result.where((app) {
        return app['patientName'].toLowerCase().contains(query) ||
            app['doctor'].toLowerCase().contains(query) ||
            app['status'].toLowerCase().contains(query) ||
            app['id'].toLowerCase().contains(query);
      }).toList();
    }

    return result;
  }

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
            Text('Status: ${appointment['status']}'),
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      drawer: _buildDrawer(),
      appBar: _buildAppBar(),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.error, color: Colors.red, size: 60),
                  const SizedBox(height: 12),
                  Text(_error!, textAlign: TextAlign.center),
                  const SizedBox(height: 12),
                  ElevatedButton(
                    onPressed: _fetchAppointments,
                    child: const Text('Retry'),
                  ),
                ],
              ),
            )
          : Column(
              children: [
                _buildHeader(),
                _buildSearchAndFilter(),
                Expanded(
                  child: _filteredAppointments.isEmpty
                      ? _buildEmptyState()
                      : RefreshIndicator(
                          onRefresh: _fetchAppointments,
                          child: ListView.builder(
                            padding: const EdgeInsets.all(16),
                            itemCount: _filteredAppointments.length,
                            itemBuilder: (context, index) {
                              return _buildAppointmentCard(
                                _filteredAppointments[index],
                              );
                            },
                          ),
                        ),
                ),
              ],
            ),
      bottomNavigationBar: _buildBottomNav(),
    );
  }

  PreferredSizeWidget _buildAppBar() {
    return AppBar(
      title: Row(
        children: [
          Image.asset('assets/PCLOGO.png', width: 35, height: 35),
          const SizedBox(width: 12),
          const Text(
            'Polyclinic',
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 22,
              color: Color(0xFF1A237E),
            ),
          ),
        ],
      ),
      backgroundColor: Colors.white,
      foregroundColor: const Color(0xFF1A237E),
      elevation: 2,
      actions: [
        NotificationBadge(
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const NotificationsPage()),
          ),
        ),
        IconButton(
          icon: const Icon(Icons.logout, color: Color(0xFF1A237E)),
          onPressed: () => _showLogoutDialog(context),
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
              MaterialPageRoute(builder: (_) => const ProfilePage()),
            );
          }),
          _buildDrawerItem(Icons.settings, 'Settings', false, () {
            Navigator.pop(context);
            Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const SettingsPage()),
            );
          }),
          _buildDrawerItem(Icons.help, 'Help', false, () {
            Navigator.pop(context);
            Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const HelpPage()),
            );
          }),
          const Divider(),
          _buildDrawerItem(Icons.logout, 'Logout', false, () {
            Navigator.pop(context);
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (_) => const PCLogin()),
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
      onTap: onTap,
    );
  }

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
        ],
      ),
    );
  }

  Widget _buildSearchAndFilter() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      color: Colors.white,
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
            hintText: 'Search appointments...',
            hintStyle: const TextStyle(fontSize: 13, color: Colors.grey),
            prefixIcon: const Icon(Icons.search, size: 18, color: Colors.grey),
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
            'No appointments found',
            style: TextStyle(fontSize: 16, color: Colors.grey.shade500),
          ),
          const SizedBox(height: 12),
          ElevatedButton(
            onPressed: _fetchAppointments,
            child: const Text('Refresh'),
          ),
        ],
      ),
    );
  }

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
          Text(
            appointment['patientName'],
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1A237E),
            ),
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
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () => _viewAppointment(appointment),
              icon: const Icon(Icons.visibility, size: 16),
              label: const Text(
                'View Details',
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF1A237E),
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(vertical: 10),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomNav() {
    return BottomNavigationBar(
      type: BottomNavigationBarType.fixed,
      backgroundColor: Colors.white,
      selectedItemColor: const Color(0xFF1A237E),
      unselectedItemColor: Colors.grey.shade400,
      currentIndex: 2,
      onTap: (index) {
        switch (index) {
          case 0:
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(builder: (_) => const Dashboard()),
              (r) => false,
            );
            break;
          case 1:
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(builder: (_) => const InquiriesPage()),
              (r) => false,
            );
            break;
          case 2:
            break;
          case 3:
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(builder: (_) => const DoctorsPage()),
              (r) => false,
            );
            break;
          case 4:
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(builder: (_) => const PatientsPage()),
              (r) => false,
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
                MaterialPageRoute(builder: (_) => const PCLogin()),
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
