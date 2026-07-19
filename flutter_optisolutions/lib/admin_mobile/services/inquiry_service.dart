import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_optisolutions/config/api_config.dart';
import 'package:flutter_optisolutions/auth/auth_service.dart';

/// Handles the "Chatbot Inquiries" feature for the Admin app — patient
/// messages the bot couldn't answer directly, forwarded here so an Admin
/// (or Staff, via the same backend endpoints) can reply.
class InquiryService {
  static Future<Map<String, String>> _headers() async {
    final token = await AuthService.getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  static Future<List<Map<String, dynamic>>> fetchAll() async {
    final res = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/admin/inquiries'),
      headers: await _headers(),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode != 200) {
      throw Exception(data['message'] ?? 'Failed to load inquiries.');
    }
    return List<Map<String, dynamic>>.from(data);
  }

  /// Returns the reply thread (Admin/Staff + any follow-up Patient
  /// messages) for one inquiry. The very first patient message is NOT
  /// included here — it's already available from the inquiry's
  /// `message` field (see fetchAll) and shown by the caller directly.
  static Future<List<Map<String, dynamic>>> fetchMessages(String dbId) async {
    final res = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/admin/inquiries/$dbId/messages'),
      headers: await _headers(),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode != 200) {
      throw Exception(data['message'] ?? 'Failed to load messages.');
    }
    return List<Map<String, dynamic>>.from(data);
  }

  static Future<Map<String, dynamic>> sendReply(
    String dbId,
    String message,
  ) async {
    final res = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/admin/inquiries/$dbId/messages'),
      headers: await _headers(),
      body: jsonEncode({'message': message}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode != 200 && res.statusCode != 201) {
      throw Exception(data['message'] ?? 'Failed to send reply.');
    }
    return data;
  }

  static Future<void> resolve(String dbId) async {
    final res = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/admin/inquiries/$dbId/resolve'),
      headers: await _headers(),
    );
    if (res.statusCode != 200) {
      final data = jsonDecode(res.body);
      throw Exception(data['message'] ?? 'Failed to resolve inquiry.');
    }
  }
}