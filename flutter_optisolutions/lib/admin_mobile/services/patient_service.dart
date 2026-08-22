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

  /// Optionally filter by doctor and/or service type — both filter through
  /// schedule_visit on the backend (no new columns/relationships needed).
  /// Leave both null to fetch every patient, same as before.
  static Future<List<Map<String, dynamic>>> fetchAll({
    int? doctorId,
    String? serviceType,
  }) async {
    final queryParams = <String, String>{
      if (doctorId != null) 'doctor_id': doctorId.toString(),
      if (serviceType != null && serviceType.isNotEmpty) 'service_type': serviceType,
    };

    final uri = Uri.parse('${ApiConfig.baseUrl}/admin/patients').replace(
      queryParameters: queryParams.isEmpty ? null : queryParams,
    );

    final res = await http.get(uri, headers: await _headers());
    final data = jsonDecode(res.body);
    if (res.statusCode != 200) {
      throw Exception(data['message'] ?? 'Failed to load patients.');
    }
    return List<Map<String, dynamic>>.from(data['patients']);
  }

  /// Distinct service types that have ever appeared in schedule_visit —
  /// used to populate the "Service" filter dropdown.
  static Future<List<String>> fetchServiceTypes() async {
    final res = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/admin/patients/service-types'),
      headers: await _headers(),
    );
    if (res.statusCode != 200) return [];
    final data = jsonDecode(res.body);
    return List<String>.from(data['service_types'] ?? []);
  }

  /// Visit/service history for one patient — service_type, visit_date,
  /// notes, and the doctor they saw. Used by the "View" sheet.
  static Future<List<Map<String, dynamic>>> fetchVisits(int patientId) async {
    final res = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/admin/patients/$patientId/visits'),
      headers: await _headers(),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode != 200) {
      throw Exception(data['message'] ?? 'Failed to load visit history.');
    }
    return List<Map<String, dynamic>>.from(data['visits'] ?? []);
  }

  /// Updates the notes field on a single schedule_visit row.
  /// NOTE: requires a backend route (e.g. PATCH /admin/visits/{id}/notes)
  /// that isn't wired up yet — see PatientListController.
  static Future<void> updateVisitNotes(int visitId, String notes) async {
    final res = await http.patch(
      Uri.parse('${ApiConfig.baseUrl}/admin/visits/$visitId/notes'),
      headers: await _headers(),
      body: jsonEncode({'notes': notes}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode != 200) {
      throw Exception(data['message'] ?? 'Failed to update note.');
    }
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