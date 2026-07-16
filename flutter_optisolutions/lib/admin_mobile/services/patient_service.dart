import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_optisolutions/config/api_config.dart';
import 'package:flutter_optisolutions/auth/auth_service.dart';

class PatientService {
  static Future<Map<String, String>> _headers() async {
    final token = await AuthService.getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  static Future<List<Map<String, dynamic>>> fetchAll() async {
    final res = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/admin/patients'),
      headers: await _headers(),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode != 200) {
      throw Exception(data['message'] ?? 'Failed to load patients.');
    }
    return List<Map<String, dynamic>>.from(data['patients']);
  }

  static Future<Map<String, dynamic>> create({
    required String fname,
    required String lname,
    String? birthdate,
    String? email,
    String? contact,
  }) async {
    final res = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/admin/patients'),
      headers: await _headers(),
      body: jsonEncode({
        'patient_fname': fname,
        'patient_lname': lname,
        'patient_birthdate': birthdate,
        'patient_email': email,
        'patient_contact': contact,
      }),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode != 201) {
      throw Exception(data['message'] ?? 'Failed to add patient.');
    }
    return data['patient'];
  }

  static Future<Map<String, dynamic>> update({
    required int id,
    required String fname,
    required String lname,
    String? birthdate,
    String? email,
    String? contact,
  }) async {
    final res = await http.put(
      Uri.parse('${ApiConfig.baseUrl}/admin/patients/$id'),
      headers: await _headers(),
      body: jsonEncode({
        'patient_fname': fname,
        'patient_lname': lname,
        'patient_birthdate': birthdate,
        'patient_email': email,
        'patient_contact': contact,
      }),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode != 200) {
      throw Exception(data['message'] ?? 'Failed to update patient.');
    }
    return data['patient'];
  }
}