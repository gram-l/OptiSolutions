// lib/admin_mobile/services/complaint_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;

import 'package:flutter_optisolutions/config/api_config.dart';
import 'package:flutter_optisolutions/auth/auth_service.dart';

class ComplaintItem {
  final int id;
  final int patientId;
  final int? logId;
  final String complaintText;
  final String date;

  ComplaintItem({
    required this.id,
    required this.patientId,
    this.logId,
    required this.complaintText,
    required this.date,
  });

  factory ComplaintItem.fromJson(Map<String, dynamic> json) {
    return ComplaintItem(
      id: json['complaint_id'],
      patientId: json['patient_id'],
      logId: json['log_id'],
      complaintText: json['complaint_text'] ?? '',
      date: json['date'] ?? '',
    );
  }
}

class ComplaintService {
  static Future<Map<String, String>> _headers() async {
    final token = await AuthService.getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Future<List<ComplaintItem>> getComplaints() async {
    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/admin/complaints'),
      headers: await _headers(),
    );

    if (response.statusCode == 200) {
      final Map<String, dynamic> data = jsonDecode(response.body);
      final List<dynamic> raw = data['complaints'] ?? [];
      return raw.map((json) => ComplaintItem.fromJson(json)).toList();
    }
    throw Exception(
      'Failed to load complaints (${response.statusCode}): ${response.body}');
  }
}