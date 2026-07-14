// lib/admin_mobile/services/visit_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_optisolutions/config/api_config.dart';
import 'package:flutter_optisolutions/auth/auth_service.dart';

class VisitService {
  static Future<List<Map<String, dynamic>>> fetchWeek(DateTime weekStart, DateTime weekEnd) async {
    final token = await AuthService.getToken();
    final ws = weekStart.toIso8601String().split('T').first;
    final we = weekEnd.toIso8601String().split('T').first;

    final res = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/admin/appointments?week_start=$ws&week_end=$we'),
      headers: {
        'Accept': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      },
    );

    final data = jsonDecode(res.body);
    if (res.statusCode != 200) {
      throw Exception(data['message'] ?? 'Failed to load appointments.');
    }
    return List<Map<String, dynamic>>.from(data['visits']);
  }
}