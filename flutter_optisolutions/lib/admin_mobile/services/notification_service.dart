import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_optisolutions/config/api_config.dart';
import 'package:flutter_optisolutions/auth/auth_service.dart';

class NotificationService {
  static Future<Map<String, String>> _headers() async {
    final token = await AuthService.getToken();
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  // Returns {'notifications': [...], 'unread_count': int}
  static Future<Map<String, dynamic>> fetchAll() async {
    final res = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/admin/notifications'),
      headers: await _headers(),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode != 200) {
      throw Exception(data['message'] ?? 'Failed to load notifications.');
    }
    return {
      'notifications': List<Map<String, dynamic>>.from(data['notifications']),
      'unread_count': data['unread_count'] ?? 0,
    };
  }

  static Future<void> markRead(int id) async {
    final res = await http.patch(
      Uri.parse('${ApiConfig.baseUrl}/admin/notifications/$id/read'),
      headers: await _headers(),
    );
    if (res.statusCode != 200) {
      throw Exception('Failed to mark notification as read.');
    }
  }

  static Future<void> markAllRead() async {
    final res = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/admin/notifications/mark-all-read'),
      headers: await _headers(),
    );
    if (res.statusCode != 200) {
      throw Exception('Failed to mark all as read.');
    }
  }
}