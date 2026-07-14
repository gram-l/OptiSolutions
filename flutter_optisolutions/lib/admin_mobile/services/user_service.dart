import 'dart:convert';
import 'package:http/http.dart' as http;

class UserService {
  // Same base URL as auth_service.dart — keep these in sync.
  static const String baseUrl = 'http://192.168.197.115:8000/api';

  static Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  // GET- retrieves data from the server. /api/users
  static Future<Map<String, dynamic>> fetchUsers() async {
    try {
      final res = await http
          .get(Uri.parse('$baseUrl/users'), headers: _headers)
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
    String status, // 'active' or 'inactive'
  ) async {
    try {
      final res = await http
          .post(
            Uri.parse('$baseUrl/users'),
            headers: _headers,
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
            Uri.parse('$baseUrl/users/$id'),
            headers: _headers,
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
          .patch(Uri.parse('$baseUrl/users/$id/toggle'), headers: _headers)
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
          .delete(Uri.parse('$baseUrl/users/$id'), headers: _headers)
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }
}
