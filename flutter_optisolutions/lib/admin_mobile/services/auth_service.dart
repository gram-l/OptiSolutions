import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class AuthService {
  // Replace with your actual IPv4 address
  static const String baseUrl = 'http://10.145.123.34/polyclinic';

  static Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      ).timeout(const Duration(seconds: 10));

      print('STATUS: ${response.statusCode}');
      print('BODY: ${response.body}');

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('user_id',    data['user']['id'].toString());
        await prefs.setString('user_email', data['user']['email']);
        await prefs.setString('user_role',  data['user']['role']);
        return data;
      } else {
        throw Exception(data['message'] ?? 'Login failed');
      }
    } catch (e) {
      print('ERROR: $e');          // ← check VS Code terminal for this
      rethrow;
    }
  }
}