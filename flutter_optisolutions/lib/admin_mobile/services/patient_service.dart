import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_optisolutions/config/api_config.dart';
class PatientService {
  static const String baseUrl = ApiConfig.baseUrl;

  static Future<List<Map<String, dynamic>>> getPatients() async {
    final response = await http.get(
      Uri.parse('$baseUrl/admin/get_patients.php'),
    ).timeout(const Duration(seconds: 10));

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      return List<Map<String, dynamic>>.from(data['patients']);
    } else {
      throw Exception('Failed to load patients');
    }
  }
}