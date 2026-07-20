import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  // Android emulator: http://10.0.2.2:8000/api
  // iOS simulator: http://localhost:8000/api
  // Physical phone: http://YOUR_COMPUTER_LOCAL_IP:8000/api (same WiFi)
  static const String baseUrl = 'http://192.168.1.7:8000/api';

  static Future<String?> _token() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }

  static Future<Map<String, String>> _headers() async {
    final token = await _token();
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  static Future<Map<String, dynamic>> login(
    String email,
    String password,
  ) async {
    final res = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({'email': email, 'password': password}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode != 200) {
      throw Exception(data['message'] ?? 'Login failed');
    }

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', data['token']);
    await prefs.setString('auth_user', jsonEncode(data['user']));
    return data['user'];
  }

  static Future<Map<String, dynamic>?> getCurrentUser() async {
    final prefs = await SharedPreferences.getInstance();

    final name = prefs.getString('user_name');
    if (name == null || name.isEmpty) return null;

    return {
      'user_id': prefs.getString('user_id'),
      'name': name,
      'email': prefs.getString('user_email'),
      'user_role': prefs.getString('user_role'),
    };
  }

  static Future<void> logout() async {
    try {
      await http
          .post(Uri.parse('$baseUrl/logout'), headers: await _headers())
          .timeout(const Duration(seconds: 5));
    } catch (e) {
      print('Logout API call failed or timed out: $e');
      // even if the request fails or hangs, still clear the local session below
    }
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('auth_user');
    await prefs.remove('user_id');
    await prefs.remove('user_name');
    await prefs.remove('user_email');
    await prefs.remove('user_role');
  }

  static Future<dynamic> get(String path) async {
    final res = await http.get(
      Uri.parse('$baseUrl$path'),
      headers: await _headers(),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode != 200) {
      throw Exception(data['message'] ?? 'Request failed');
    }
    return data;
  }

  static Future<dynamic> patch(String path, Map<String, dynamic> body) async {
    final res = await http.patch(
      Uri.parse('$baseUrl$path'),
      headers: await _headers(),
      body: jsonEncode(body),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode != 200) {
      throw Exception(data['message'] ?? 'Request failed');
    }
    return data;
  }

  static Future<dynamic> post(String path, Map<String, dynamic> body) async {
    final res = await http.post(
      Uri.parse('$baseUrl$path'),
      headers: await _headers(),
      body: jsonEncode(body),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode != 200 && res.statusCode != 201) {
      throw Exception(data['message'] ?? 'Request failed');
    }
    return data;
  }

  static Future<Map<String, dynamic>> uploadPhoto(File imageFile) async {
    final token = await _token();
    final request = http.MultipartRequest(
      'POST',
      Uri.parse('$baseUrl/profile/photo'),
    );
    request.headers['Authorization'] = 'Bearer $token';
    request.headers['Accept'] = 'application/json';
    request.files.add(
      await http.MultipartFile.fromPath('photo', imageFile.path),
    );

    final streamed = await request.send();
    final res = await http.Response.fromStream(streamed);
    final data = jsonDecode(res.body);

    if (res.statusCode != 200) {
      throw Exception(data['message'] ?? 'Failed to upload photo');
    }
    return data;
  }
}
