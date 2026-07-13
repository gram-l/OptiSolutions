// lib/admin_mobile/services/feedback_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;

import 'package:flutter_optisolutions/config/api_config.dart';

class FeedbackItem {
  final int id;
  final int? patientId;
  final int rating;
  final String comment;
  final String sentiment;
  final double? confidence;
  final String date;

  FeedbackItem({
    required this.id,
    this.patientId,
    required this.rating,
    required this.comment,
    required this.sentiment,
    this.confidence,
    required this.date,
  });

  factory FeedbackItem.fromJson(Map<String, dynamic> json) {
    return FeedbackItem(
      id: json['feedback_id'],
      patientId: json['patient_id'],
      rating: json['rating'] ?? 0,
      comment: json['comment'] ?? '',
      sentiment: json['sentiment'] ?? 'pending',
      confidence: json['confidence'] != null
          ? (json['confidence'] as num).toDouble()
          : null,
      date: json['date'] ?? '',
    );
  }
}

class FeedbackService {
  Future<List<FeedbackItem>> getFeedback() async {
    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/admin/feedback'),
    );

    if (response.statusCode == 200) {
      final List data = jsonDecode(response.body);
      return data.map((json) => FeedbackItem.fromJson(json)).toList();
    }
    throw Exception(
      'Failed to load feedback (${response.statusCode}): ${response.body}');
  }
}