// lib/models/user_model.dart
class AppUser {
  final int userId;
  final String name;
  final String email;
  final String? googleId;
  final String userRole;
  final DateTime? birthday;
  final String? profilePhoto;
  final String status;
  final DateTime? lastLoginAt;

  AppUser({
    required this.userId,
    required this.name,
    required this.email,
    this.googleId,
    required this.userRole,
    this.birthday,
    this.profilePhoto,
    required this.status,
    this.lastLoginAt,
  });

  factory AppUser.fromJson(Map<String, dynamic> json) {
    return AppUser(
      userId: json['user_id'],
      name: json['name'],
      email: json['email'],
      googleId: json['google_id'],
      userRole: json['user_role'],
      birthday: json['birthday'] != null ? DateTime.parse(json['birthday']) : null,
      profilePhoto: json['profile_photo'],
      status: json['status'] ?? 'active',
      lastLoginAt: json['last_login_at'] != null ? DateTime.parse(json['last_login_at']) : null,
    );
  }

  Map<String, dynamic> toJson() => {
        'user_id': userId,
        'name': name,
        'email': email,
        'google_id': googleId,
        'user_role': userRole,
        'birthday': birthday?.toIso8601String(),
        'profile_photo': profilePhoto,
        'status': status,
        'last_login_at': lastLoginAt?.toIso8601String(),
      };
}