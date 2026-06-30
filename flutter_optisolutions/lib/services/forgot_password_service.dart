import 'dart:convert';
import 'package:http/http.dart' as http;

class ForgotPasswordService {
  static const String baseUrl = 'http://192.168.254.147:8000/api';

  static Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

  static Future<Map<String, dynamic>> sendOtp(String email) async {
    try {
      final res = await http
          .post(
            Uri.parse('$baseUrl/forgot-password/send-otp'),
            headers: _headers,
            body: jsonEncode({'email': email}),
          )
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  static Future<Map<String, dynamic>> verifyOtp(String email, String otp) async {
    try {
      final res = await http
          .post(
            Uri.parse('$baseUrl/forgot-password/verify-otp'),
            headers: _headers,
            body: jsonEncode({'email': email, 'otp': otp}),
          )
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false,   'message': e.toString(),};
    }
  }

  static Future<Map<String, dynamic>> resendOtp(String email) async {
    try {
      final res = await http
          .post(
            Uri.parse('$baseUrl/forgot-password/resend-otp'),
            headers: _headers,
            body: jsonEncode({'email': email}),
          )
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  static Future<Map<String, dynamic>> resetPassword({
    required String email,
    required String resetToken,
    required String password,
    required String passwordConfirmation,
  }) async {
    try {
      final res = await http
          .post(
            Uri.parse('$baseUrl/forgot-password/reset'),
            headers: _headers,
            body: jsonEncode({
              'email': email,
              'reset_token': resetToken,
              'password': password,
              'password_confirmation': passwordConfirmation,
            }),
          )
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }
}