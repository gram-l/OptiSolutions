// lib/admin_mobile/services/root_cause_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_optisolutions/config/api_config.dart';

class RootCauseData {
  final Map<String, int> feedbackCategoryCounts;
  final Map<String, int> complaintCategoryCounts;

  RootCauseData({
    required this.feedbackCategoryCounts,
    required this.complaintCategoryCounts,
  });

  factory RootCauseData.fromJson(Map<String, dynamic> json) {
    return RootCauseData(
      feedbackCategoryCounts: Map<String, int>.from(
        json['feedback_category_counts'] ?? {},
      ),
      complaintCategoryCounts: Map<String, int>.from(
        json['complaint_category_counts'] ?? {},
      ),
    );
  }

  List<MapEntry<String, int>> get combined {
    final merged = <String, int>{};
    feedbackCategoryCounts.forEach((k, v) => merged[k] = (merged[k] ?? 0) + v);
    complaintCategoryCounts.forEach((k, v) => merged[k] = (merged[k] ?? 0) + v);
    final entries = merged.entries.where((e) => e.value > 0).toList();
    entries.sort((a, b) => b.value.compareTo(a.value));
    return entries;
  }
}

class RootCauseService {
  Future<RootCauseData> getRootCauses() async {
    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/admin/feedback/diagnose'),
    );

    if (response.statusCode == 200) {
      return RootCauseData.fromJson(jsonDecode(response.body));
    }
    throw Exception(
        'Failed to load root causes (${response.statusCode}): ${response.body}');
  }
}