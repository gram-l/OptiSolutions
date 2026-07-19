// lib/staff_mobile/screens/settings.dart
import 'package:flutter/material.dart';
import 'package:flutter_optisolutions/login.dart';
import '../models/profile_data.dart';
import '../services/api_service.dart';

const String _clinicInfoPath = '/clinic';

class SettingsPage extends StatefulWidget {
  const SettingsPage({super.key});

  @override
  State<SettingsPage> createState() => _SettingsPageState();
}

class _SettingsPageState extends State<SettingsPage> {

  Future<Map<String, dynamic>> _fetchClinicInfo() async {
    final data = await ApiService.get(_clinicInfoPath);

    if (data is Map<String, dynamic> && data.containsKey('data')) {
      return Map<String, dynamic>.from(data['data']);
    }
    return Map<String, dynamic>.from(data);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,

      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1A237E)),
          onPressed: () {
          
            Navigator.pop(context, true);
          },
        ),
        title: const Text(
          'Settings',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 20,
            color: Color(0xFF1A237E),
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 1,
        centerTitle: false,
      ),

      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Manage your account preferences',
              style: TextStyle(fontSize: 13, color: Colors.grey),
            ),
            const SizedBox(height: 20),

            // PERSONAL INFORMATION
            _buildSectionHeader(Icons.person_outline, 'PERSONAL INFORMATION'),
            const SizedBox(height: 8),
            Container(
              decoration: _cardDecoration(),
              child: Material(
                color: Colors.transparent,
                borderRadius: BorderRadius.circular(12),
                child: InkWell(
                  borderRadius: BorderRadius.circular(12),
                  onTap: () {
                    _showProfileDialog(context);
                  },
                  child: ListTile(
                    leading: _buildIconBox(
                      Icons.badge_outlined,
                      Colors.blue.shade50,
                      Colors.blue.shade700,
                    ),
                    title: const Text(
                      'My Profile',
                      style: TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 15,
                        color: Color(0xFF1A237E),
                      ),
                    ),
                    subtitle: const Text(
                      'View your personal details',
                      style: TextStyle(fontSize: 12, color: Colors.grey),
                    ),
                    trailing: const Icon(
                      Icons.arrow_forward_ios,
                      size: 16,
                      color: Colors.grey,
                    ),
                  ),
                ),
              ),
            ),

            const SizedBox(height: 20),

            // CLINIC INFORMATION 
            _buildSectionHeader(
              Icons.local_hospital_outlined,
              'CLINIC INFORMATION',
            ),
            const SizedBox(height: 8),
            Container(
              decoration: _cardDecoration(),
              child: Material(
                color: Colors.transparent,
                borderRadius: BorderRadius.circular(12),
                child: InkWell(
                  borderRadius: BorderRadius.circular(12),
                  onTap: () {
                    _showClinicInfoDialog(context);
                  },
                  child: ListTile(
                    leading: _buildIconBox(
                      Icons.local_hospital_outlined,
                      Colors.deepPurple.shade50,
                      Colors.deepPurple.shade400,
                    ),
                    title: const Text(
                      'Clinic Information',
                      style: TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 15,
                        color: Color(0xFF1A237E),
                      ),
                    ),
                    subtitle: const Text(
                      'View clinic details',
                      style: TextStyle(fontSize: 12, color: Colors.grey),
                    ),
                    trailing: const Icon(
                      Icons.arrow_forward_ios,
                      size: 16,
                      color: Colors.grey,
                    ),
                  ),
                ),
              ),
            ),

            const SizedBox(height: 20),

            // SUPPORT
            _buildSectionHeader(Icons.help_outline, 'SUPPORT'),
            const SizedBox(height: 8),
            Container(
              decoration: _cardDecoration(),
              child: Material(
                color: Colors.transparent,
                borderRadius: BorderRadius.circular(12),
                child: InkWell(
                  borderRadius: BorderRadius.circular(12),
                  onTap: () {
                    _showDataPrivacyDialog(context);
                  },
                  child: ListTile(
                    leading: _buildIconBox(
                      Icons.shield_outlined,
                      Colors.grey.shade200,
                      Colors.grey.shade700,
                    ),
                    title: const Text(
                      'Data & Privacy',
                      style: TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 15,
                        color: Color(0xFF1A237E),
                      ),
                    ),
                    subtitle: const Text(
                      'How patient data is handled',
                      style: TextStyle(fontSize: 12, color: Colors.grey),
                    ),
                    trailing: const Icon(
                      Icons.arrow_forward_ios,
                      size: 16,
                      color: Colors.grey,
                    ),
                  ),
                ),
              ),
            ),

            const SizedBox(height: 12),

            Container(
              decoration: _cardDecoration(),
              child: Material(
                color: Colors.transparent,
                borderRadius: BorderRadius.circular(12),
                child: InkWell(
                  borderRadius: BorderRadius.circular(12),
                  onTap: () {
                    _showLogoutDialog(context);
                  },
                  child: ListTile(
                    leading: _buildIconBox(
                      Icons.logout,
                      Colors.red.shade50,
                      Colors.red,
                    ),
                    title: const Text(
                      'Log Out',
                      style: TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 15,
                        color: Colors.red,
                      ),
                    ),
                    subtitle: const Text(
                      'Sign out of your account',
                      style: TextStyle(fontSize: 12, color: Colors.grey),
                    ),
                    trailing: const Icon(
                      Icons.arrow_forward_ios,
                      size: 16,
                      color: Colors.grey,
                    ),
                  ),
                ),
              ),
            ),

            const SizedBox(height: 24),

            const Center(
              child: Text(
                'Polyclinic Staff v2.0',
                style: TextStyle(fontSize: 12, color: Colors.grey),
              ),
            ),
          ],
        ),
      ),
    );
  }

  BoxDecoration _cardDecoration() {
    return BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      boxShadow: [
        BoxShadow(
          color: Colors.grey.withAlpha(25),
          spreadRadius: 1,
          blurRadius: 4,
          offset: const Offset(0, 2),
        ),
      ],
    );
  }

  Widget _buildIconBox(IconData icon, Color bgColor, Color iconColor) {
    return Container(
      width: 40,
      height: 40,
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Icon(icon, color: iconColor, size: 20),
    );
  }

  Widget _buildSectionHeader(IconData icon, String title) {
    return Row(
      children: [
        Icon(icon, size: 16, color: Colors.grey.shade600),
        const SizedBox(width: 6),
        Text(
          title,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.bold,
            color: Colors.grey.shade600,
            letterSpacing: 0.5,
          ),
        ),
      ],
    );
  }


  void _showProfileDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            const Icon(Icons.badge_outlined, color: Color(0xFF1A237E)),
            const SizedBox(width: 8),
            const Expanded(child: Text('My Profile')),
            IconButton(
              icon: const Icon(Icons.close, size: 20, color: Colors.grey),
              onPressed: () => Navigator.pop(context),
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(),
            ),
          ],
        ),
        content: SizedBox(
          width: double.maxFinite,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: const Color(0xFF1A237E).withAlpha(25),
                      border: Border.all(
                        color: const Color(0xFF1A237E),
                        width: 2,
                      ),
                    ),
                    child: const Center(
                      child: Icon(
                        Icons.person,
                        size: 40,
                        color: Color(0xFF1A237E),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                _buildClinicInfoRow(
                  Icons.person_outline,
                  'Full Name',
                  ProfileData.name,
                ),
                const SizedBox(height: 12),
                _buildClinicInfoRow(
                  Icons.email_outlined,
                  'Email',
                  ProfileData.email.isNotEmpty ? ProfileData.email : 'N/A',
                ),
                const SizedBox(height: 12),
                _buildClinicInfoRow(
                  Icons.phone_outlined,
                  'Contact',
                  ProfileData.contact,
                ),
                const SizedBox(height: 12),
                _buildClinicInfoRow(
                  Icons.badge_outlined,
                  'Staff ID',
                  ProfileData.staffId,
                ),
                const SizedBox(height: 12),
                _buildClinicInfoRow(
                  Icons.business_outlined,
                  'Role',
                  ProfileData.department,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _showClinicInfoDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            const Icon(Icons.local_hospital_outlined, color: Color(0xFF1A237E)),
            const SizedBox(width: 8),
            const Expanded(child: Text('Clinic Information')),
            IconButton(
              icon: const Icon(Icons.close, size: 20, color: Colors.grey),
              onPressed: () => Navigator.pop(context),
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(),
            ),
          ],
        ),
        content: SizedBox(
          width: double.maxFinite,
          child: FutureBuilder<Map<String, dynamic>>(
            future: _fetchClinicInfo(),
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const Padding(
                  padding: EdgeInsets.symmetric(vertical: 24),
                  child: Center(child: CircularProgressIndicator()),
                );
              }

              if (snapshot.hasError) {
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  child: Text(
                    'Unable to load clinic information.\n${snapshot.error}',
                    style: const TextStyle(color: Colors.red, fontSize: 13),
                  ),
                );
              }

              final data = snapshot.data ?? {};
              final clinicName = (data['clinic_name'] ?? '-').toString();
              final address = (data['address'] ?? '-').toString();
              final contactNo = (data['contact_no'] ?? '-').toString();
              final operatingHours = (data['operating_hours'] ?? '-')
                  .toString();

              return SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildClinicInfoRow(
                      Icons.local_hospital,
                      'Clinic Name',
                      clinicName,
                    ),
                    const SizedBox(height: 12),
                    _buildClinicInfoRow(
                      Icons.location_on_outlined,
                      'Address',
                      address,
                    ),
                    const SizedBox(height: 12),
                    _buildClinicInfoRow(
                      Icons.call_outlined,
                      'Contact No.',
                      contactNo,
                    ),
                    const SizedBox(height: 12),
                    _buildClinicInfoRow(
                      Icons.access_time,
                      'Operating Hours',
                      operatingHours,
                    ),
                  ],
                ),
              );
            },
          ),
        ),
      ),
    );
  }

  Widget _buildClinicInfoRow(IconData icon, String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: Colors.grey.shade600),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey.shade600,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                value,
                style: const TextStyle(fontSize: 14, color: Color(0xFF1A237E)),
              ),
            ],
          ),
        ),
      ],
    );
  }

  void _showDataPrivacyDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            const Icon(Icons.shield_outlined, color: Color(0xFF1A237E)),
            const SizedBox(width: 8),
            const Expanded(child: Text('Data & Privacy')),
            IconButton(
              icon: const Icon(Icons.close, size: 20, color: Colors.grey),
              onPressed: () => Navigator.pop(context),
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(),
            ),
          ],
        ),
        content: const SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'How patient data is handled:',
                style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
              ),
              SizedBox(height: 8),
              Text(
                '• Patient records are encrypted and stored securely.',
                style: TextStyle(fontSize: 13),
              ),
              SizedBox(height: 6),
              Text(
                '• Only authorized staff can access patient information.',
                style: TextStyle(fontSize: 13),
              ),
              SizedBox(height: 6),
              Text(
                '• Data is used strictly for clinical and administrative purposes.',
                style: TextStyle(fontSize: 13),
              ),
              SizedBox(height: 6),
              Text(
                '• All access to patient data is logged for accountability.',
                style: TextStyle(fontSize: 13),
              ),
            ],
          ),
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
