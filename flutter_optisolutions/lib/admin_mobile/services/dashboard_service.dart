import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;

class DashboardService {
  static const String baseUrl = 'http://192.168.197.115:8000/api';

  static Future<Map<String, dynamic>> getDashboardData() async {
    try {
      final response = await http
          .get(
            Uri.parse('$baseUrl/admin/dashboard-data'),
            headers: {'Content-Type': 'application/json'},
          )
          .timeout(const Duration(seconds: 10));

      print('STATUS: ${response.statusCode}');
      print('BODY: ${response.body}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data as Map<String, dynamic>;
      } else if (response.statusCode >= 500) {
        throw Exception('Server error. Please try again later.');
      } else {
        throw Exception('Failed to load dashboard data.');
      }
    } on SocketException {
      print('ERROR: SocketException');
      throw Exception(
        'Could not connect to server. Check your internet connection.',
      );
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
}
