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
    contact = result['phone_number']?.toString() ?? 'N/A';
    staffId = result['user_id']?.toString() ?? 'N/A';

    // Work Details: wala pa itong mga column sa database,
    // kaya iwan muna as N/A hanggang meron nang backend support
    department = 'N/A';
    shift = 'N/A';
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
      'phone_number': contact,
      // department / shift: hindi pa ito ipapadala hangga't wala pang
      // column sa users table para dito
    });

    ProfileData.name = result['name'] ?? name;
    ProfileData.email = result['email'] ?? email;
    ProfileData.contact = result['phone_number']?.toString() ?? contact;
    // department at shift: hindi pa nagbabago, laging N/A
  }
}
