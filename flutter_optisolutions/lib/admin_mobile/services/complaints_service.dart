// lib/admin_mobile/services/complaint_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;

import 'package:flutter_optisolutions/config/api_config.dart';

class ComplaintItem {
  final int id;
  final int patientId;
  final int? logId;
  final String complaintText;
  final String status; // pending | in_progress | resolved
  final String date;

  ComplaintItem({
    required this.id,
    required this.patientId,
    this.logId,
    required this.complaintText,
    required this.status,
    required this.date,
  });

  factory ComplaintItem.fromJson(Map<String, dynamic> json) {
    return ComplaintItem(
      id: json['complaint_id'],
      patientId: json['patient_id'],
      logId: json['log_id'],
      complaintText: json['complaint_text'] ?? '',
      status: json['status'] ?? 'pending',
      date: json['date'] ?? '',
    );
  }
}

class ComplaintService {
  Future<List<ComplaintItem>> getComplaints() async {
    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/admin/complaints'),
    );

    if (response.statusCode == 200) {
      final List data = jsonDecode(response.body);
      return data.map((json) => ComplaintItem.fromJson(json)).toList();
    }
    throw Exception(
      'Failed to load complaints (${response.statusCode}): ${response.body}');
  }
}