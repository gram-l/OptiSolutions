import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_optisolutions/config/api_config.dart';
import 'package:flutter_optisolutions/auth/google_auth.dart';
import 'package:http_parser/http_parser.dart';

class AuthService {
  static Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/login'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      ).timeout(const Duration(seconds: 10));

      print('STATUS: ${response.statusCode}');
      print('BODY: ${response.body}');

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        await _saveSession(data['user'], data['token']);
        return data;
      } else {
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
      print('ERROR: SocketException');
      throw Exception('Could not connect to server. Check your internet connection.');
    } on FormatException {
      print('ERROR: FormatException');
      throw Exception('Unexpected response from server.');
    } catch (e) {
      print('ERROR: $e');
      if (e.toString().contains('TimeoutException')) {
        throw Exception('Connection timed out. Please try again.');
      }
      rethrow;
    }
  }

  /// NEW: Google Sign-In, mirrors login() so login.dart can call it the same way
  static Future<Map<String, dynamic>> loginWithGoogle() async {
    final googleAuthService = GoogleAuthService();
    final result = await googleAuthService.signInWithGoogle();

    if (result == null) {
      throw Exception('Google sign-in was cancelled.');
    }

    if (result['user'] != null) {
      await _saveSession(result['user'], result['token']);
    }

    return result;
  }

  static Future<void> _saveSession(Map<String, dynamic> user, String? token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('user_id', user['user_id'].toString());
    await prefs.setString('user_email', user['email'] ?? '');
    await prefs.setString('user_role', user['user_role'] ?? '');
    if (token != null) {
      await prefs.setString('auth_token', token);
    }
  }

  static Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('user_id');
    await prefs.remove('user_email');
    await prefs.remove('user_role');
    await prefs.remove('auth_token');
  }

    static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }

  /// Fetches the current user's profile fresh from the DB (name, email, role, photo)
static Future<Map<String, dynamic>> fetchProfile() async {
  final token = await getToken();
  if (token == null) throw Exception('Not logged in.');

  final response = await http.get(
    Uri.parse('${ApiConfig.baseUrl}/admin/me'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  ).timeout(const Duration(seconds: 10));

  final data = jsonDecode(response.body);

  if (response.statusCode == 200) {
    return data; // flat object now, no wrapper
  } else {
    throw Exception(data['message'] ?? 'Failed to load profile.');
  }
}

  /// Uploads a new profile photo. Returns the updated photo path.
  static Future<String> uploadProfilePhoto(File imageFile) async {
    final token = await getToken();
    if (token == null) throw Exception('Not logged in.');

    final request = http.MultipartRequest(
      'POST',
      Uri.parse('${ApiConfig.baseUrl}/admin/profile/photo'),
    );
    request.headers['Authorization'] = 'Bearer $token';
    request.headers['Accept'] = 'application/json';
    request.files.add(
      await http.MultipartFile.fromPath(
        'photo',
        imageFile.path,
        contentType: MediaType('image', 'jpeg'),
      ),
    );

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);
    final data = jsonDecode(response.body);

    if (response.statusCode == 200) {
      return data['profile_photo'];
    } else {
      throw Exception(data['message'] ?? 'Failed to upload photo.');
    }
  }
}