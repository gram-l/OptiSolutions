import '../services/api_service.dart';

class ProfileData {
  static String name = 'Staff';
  static String email = '';
  static String contact = 'N/A';
  static String staffId = 'N/A';
  static String department = 'N/A';
  static String shift = 'N/A';

  static Future<void> load() async {
    final result = await ApiService.get('/me');
    name = result['name'] ?? 'Staff';
    email = result['email'] ?? '';
    contact = result['contact'] ?? 'N/A';
    staffId = result['staff_id']?.toString() ?? 'N/A';
    department = result['department'] ?? 'N/A';
    shift = result['shift'] ?? 'N/A';
  }

  // Saves edits from the Settings page back to the database, then updates
  // the local copies so the Profile page reflects the change immediately.
  static Future<void> updateProfile({
    required String name,
    required String email,
    required String contact,
    required String department,
    required String shift,
  }) async {
    final result = await ApiService.patch('/me', {
      'name': name,
      'email': email,
      'contact': contact,
      'department': department,
      'shift': shift,
    });

    ProfileData.name = result['name'] ?? name;
    ProfileData.email = result['email'] ?? email;
    ProfileData.contact = result['contact'] ?? contact;
    ProfileData.department = result['department'] ?? department;
    ProfileData.shift = result['shift'] ?? shift;
  }
}
