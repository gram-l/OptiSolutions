// lib/models/profile_data.dart
class ProfileData {
  static String name = 'Clinic Staff';
  static String email = 'staff@polyclinic.com';
  static String contact = '09123456789';
  static String staffId = 'STF-001';
  static String department = 'Administration';
  static String shift = '8:00 AM - 5:00 PM';

  static void updateProfile({
    String? name,
    String? email,
    String? contact,
    String? department,
    String? shift,
  }) {
    if (name != null && name.isNotEmpty) ProfileData.name = name;
    if (email != null && email.isNotEmpty) ProfileData.email = email;
    if (contact != null && contact.isNotEmpty) ProfileData.contact = contact;
    if (department != null && department.isNotEmpty)
      ProfileData.department = department;
    if (shift != null && shift.isNotEmpty) ProfileData.shift = shift;
  }
}
