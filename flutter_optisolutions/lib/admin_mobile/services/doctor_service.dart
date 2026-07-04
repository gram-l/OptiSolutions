import 'dart:convert';
import 'package:http/http.dart' as http;

class DoctorService {
  static const String baseUrl = 'http://10.145.123.34:8000/api';

  static Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

  // GET /api/doctors
  static Future<Map<String, dynamic>> fetchDoctors() async {
    try {
      final res = await http
          .get(Uri.parse('$baseUrl/doctors'), headers: _headers)
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // POST /api/doctors
  static Future<Map<String, dynamic>> createDoctor({
    required String name,
    required String specialty,
    required String schedule,
    String? description,
    String? phone,
  }) async {
    try {
      final res = await http
          .post(
            Uri.parse('$baseUrl/doctors'),
            headers: _headers,
            body: jsonEncode({
              'doctor_name': name,
              'specialty': specialty,
              'schedule': schedule,
              'description': description,
              'contact_number': phone,
            }),
          )
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // PUT /api/doctors/{id}
  static Future<Map<String, dynamic>> updateDoctor({
    required int id,
    required String name,
    required String specialty,
    required String schedule,
    String? description,
    String? phone,
  }) async {
    try {
      final res = await http
          .put(
            Uri.parse('$baseUrl/doctors/$id'),
            headers: _headers,
            body: jsonEncode({
              'doctor_name': name,
              'specialty': specialty,
              'schedule': schedule,
              'description': description,
              'contact_number': phone,
            }),
          )
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // PATCH /api/doctors/{id}/toggle
  static Future<Map<String, dynamic>> toggleDoctorStatus(int id) async {
    try {
      final res = await http
          .patch(Uri.parse('$baseUrl/doctors/$id/toggle'), headers: _headers)
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // DELETE /api/doctors/{id}
  static Future<Map<String, dynamic>> deleteDoctor(int id) async {
    try {
      final res = await http
          .delete(Uri.parse('$baseUrl/doctors/$id'), headers: _headers)
          .timeout(const Duration(seconds: 10));
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }
} 