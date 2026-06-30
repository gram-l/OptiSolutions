import 'dart:convert';
import 'package:http/http.dart' as http;

class PatientService {
  static const String baseUrl = 'http://10.232.147.34/polyclinic';

  static Future<List<Map<String, dynamic>>> getPatients() async {
    final response = await http.get(
      Uri.parse('$baseUrl/get_patients.php'),
    ).timeout(const Duration(seconds: 10));

    final data = jsonDecode(response.body);

    if (response.statusCode == 200 && data['success'] == true) {
      return List<Map<String, dynamic>>.from(data['patients']);
    } else {
      throw Exception('Failed to load patients');
    }
  }
}