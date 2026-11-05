import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_optisolutions/config/api_config.dart';
import 'package:flutter_optisolutions/auth/auth_service.dart';

class UserService {
  // Same base URL as auth_service.dart — keep these in sync.
  static const String baseUrl = ApiConfig.baseUrl;

  static Future<Map<String, String>> get _headers async => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': 'Bearer ${await AuthService.getToken() ?? ''}',
  };

  static Future<Map<String, dynamic>> verificationStatus() async {
    try {
      final response = await http
          .get(
            Uri.parse('$baseUrl/admin/users/verification-status'),
            headers: await _headers,
          )
          .timeout(const Duration(seconds: 20));
      return jsonDecode(response.body);
    } catch (_) {
      return {
        'success': false,
        'message': 'Could not check verification. Please try again.',
      };
    }
  }

  static Future<Map<String, dynamic>> sendVerificationCode() async {
    try {
      final response = await http
          .post(
            Uri.parse('$baseUrl/admin/users/verification-code'),
            headers: await _headers,
          )
          .timeout(const Duration(seconds: 20));
      return jsonDecode(response.body);
    } catch (_) {
      return {
        'success': false,
        'message': 'Could not send verification code. Please try again.',
      };
    }
  }

  static Future<Map<String, dynamic>> verifyAccess(
    String challenge,
    String code,
  ) async {
    try {
      final response = await http
          .post(
            Uri.parse('$baseUrl/admin/users/verify-access'),
            headers: await _headers,
            body: jsonEncode({'challenge_id': challenge, 'otp': code}),
          )
          .timeout(const Duration(seconds: 20));
      return jsonDecode(response.body);
    } catch (_) {
      return {
        'success': false,
        'message': 'Could not verify the code. Please try again.',
      };
    }
  }

  // GET- retrieves data from the server. /api/users
  static Future<Map<String, dynamic>> fetchUsers() async {
    try {
      final res = await http
          .get(Uri.parse('$baseUrl/admin/users'), headers: await _headers)
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // POST - creates a new resource. /api/users
  static Future<Map<String, dynamic>> createUser(
    String name,
    String email,
    String role, // 'Admin' or 'Staff'
    String password,
    String status,
  ) async {
    try {
      final res = await http
          .post(
            Uri.parse('$baseUrl/admin/users'),
            headers: await _headers,
            body: jsonEncode({
              'name': name,
              'email': email,
              'user_role': role,
              'password': password,
              'status': status,
            }),
          )
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // PUT - replaces/updates an existing resource, /api/users/{id}
  static Future<Map<String, dynamic>> updateUser(
    int id,
    String name,
    String email,
    String role,
    String status, {
    String? password,
  }) async {
    try {
      final body = {
        'name': name,
        'email': email,
        'user_role': role,
        'status': status,
      };
      if (password != null && password.isNotEmpty) {
        body['password'] = password;
      }

      final res = await http
          .put(
            Uri.parse('$baseUrl/admin/users/$id'),
            headers: await _headers,
            body: jsonEncode(body),
          )
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // PATCH- partially updates a resource (only the specific field(s) being changed). Used in toggleStatus() to flip a user's active/inactive status without touching the rest of their data./api/users/{id}/toggle
  static Future<Map<String, dynamic>> toggleStatus(int id) async {
    try {
      final res = await http
          .patch(
            Uri.parse('$baseUrl/admin/users/$id/toggle'),
            headers: await _headers,
          )
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      //error messages
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // DELETE /api/users/{id}
  static Future<Map<String, dynamic>> deleteUser(int id) async {
    try {
      final res = await http
          .delete(
            Uri.parse('$baseUrl/admin/users/$id'),
            headers: await _headers,
          )
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }
}
