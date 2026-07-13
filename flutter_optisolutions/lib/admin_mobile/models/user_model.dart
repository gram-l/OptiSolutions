
import 'package:flutter_optisolutions/config/api_config.dart';
// lib/admin_mobile/models/user_model.dart
class AppUser {
  final int userId;
  final String name;
  final String email;
  final String userRole;
  final String? profilePhoto;

  AppUser({
    required this.userId,
    required this.name,
    required this.email,
    required this.userRole,
    this.profilePhoto,
  });

  factory AppUser.fromJson(Map<String, dynamic> json) {
    return AppUser(
      userId: json['user_id'],
      name: json['name'],
      email: json['email'],
      userRole: json['user_role'],
      profilePhoto: json['profile_photo'],
    );
  }

  String? get profilePhotoUrl =>
      profilePhoto != null ? '${ApiConfig.baseUrl}/admin/$profilePhoto' : null;
}