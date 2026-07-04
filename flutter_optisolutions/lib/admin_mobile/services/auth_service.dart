import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class AuthService {

  static const String baseUrl = 'http://10.145.123.34:8000/api';

  static Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      ).timeout(const Duration(seconds: 10));

      print('STATUS: ${response.statusCode}');
      print('BODY: ${response.body}');

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('user_id', data['user']['user_id'].toString());
        await prefs.setString('user_email', data['user']['email'] ?? '');
        await prefs.setString('user_role', data['user']['user_role'] ?? '');
        return data;
      } else {
        // NEW: distinguish common server error cases
        if (response.statusCode == 401) {
          throw Exception(data['message'] ?? 'Incorrect email or password.');
        } else if (response.statusCode == 422) {
          throw Exception(data['message'] ?? 'Please check your input and try again.');
        } else if (response.statusCode >= 500) {
          throw Exception('Server error. Please try again later.');
        } else {
          throw Exception(data['message'] ?? 'Login failed');
        }
      }
    } on SocketException {
      // NEW: no internet / server unreachable
      print('ERROR: SocketException');
      throw Exception('Could not connect to server. Check your internet connection.');
    } on FormatException {
      // NEW: response wasn't valid JSON (e.g. server returned HTML error page)
      print('ERROR: FormatException');
      throw Exception('Unexpected response from server.');
    } catch (e) {
      // existing fallback — still catches timeouts and anything else
      print('ERROR: $e');
      if (e.toString().contains('TimeoutException')) {
        throw Exception('Connection timed out. Please try again.');
      }
      rethrow;
    }
  }

  static Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('user_id');
    await prefs.remove('user_email');
    await prefs.remove('user_role');
  }
}