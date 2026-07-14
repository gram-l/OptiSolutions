import 'dart:async';
import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:flutter_optisolutions/login.dart';
import 'inquiries.dart';
import 'appointments.dart';
import 'doctors.dart';
import 'patients.dart';
import 'profile.dart';
import 'settings.dart';
import 'help.dart';
import 'notifications.dart';
import '../widgets/notification_badge.dart';
import '../services/api_service.dart';

class Dashboard extends StatefulWidget {
  const Dashboard({super.key});

  @override
  State<Dashboard> createState() => _DashboardState();
}

class _DashboardState extends State<Dashboard> {
  // Now loaded from the database via API instead of hardcoded
  List<Map<String, dynamic>> _recentActivities = [];
  bool _loading = true;
  String? _loadError;

  // Stats card counts — pulled from the same endpoints used by the other pages
  int _appointmentsCount = 0;
  int _pendingInquiriesCount = 0;
  int _activeDoctorsCount = 0;
  int _patientsCount = 0;

  // ---- Chart data ----

  // 1) Service Distribution — [{ 'service_type': 'General Checkup', 'total': 12 }, ...]
  List<Map<String, dynamic>> _serviceDistribution = [];

  // 2) Weekly Patient Visits — count of schedule_visit records per week (last 6 weeks)
  static const int _weekCount = 6;
  List<int> _weeklyVisits = List.filled(_weekCount, 0);
  List<String> _weekLabels = List.generate(
    _weekCount,
    (i) => i == _weekCount - 1 ? 'This wk' : 'W-${_weekCount - 1 - i}',
  );

  // 3) Sentiment Analysis (custom bars) — from /feedback star_rating
  Map<String, double> _sentimentPercents = const {
    'Positive': 65,
    'Neutral': 25,
    'Negative': 10,
  };
  bool _sentimentFromApi = false;

  // 4) Inquiry Volume per week — count of inquiries per weekday (Mon..Sun)
  List<int> _inquiryVolumeByDay = List.filled(7, 0);
  bool _inquiryVolumeFromApi = false;

  static const List<String> _weekdayLabels = [
    'Mon',
    'Tue',
    'Wed',
    'Thu',
    'Fri',
    'Sat',
    'Sun',
  ];

  static const Map<String, Color> _sentimentColors = {
    'Positive': Colors.green,
    'Neutral': Colors.orange,
    'Negative': Colors.red,
  };

  static const List<Color> _serviceColors = [
    Color(0xFF1A237E),
    Color(0xFF3949AB),
    Color(0xFF5C6BC0),
    Color(0xFF7986CB),
    Color(0xFF9FA8DA),
    Color(0xFF00897B),
    Color(0xFF43A047),
    Color(0xFFFB8C00),
  ];

  @override
  void initState() {
    super.initState();
    _loadDashboardData();
  }

  IconData _iconFromName(String? name) {
    switch (name) {
      case 'question_answer':
        return Icons.question_answer;
      case 'calendar_today':
        return Icons.calendar_today;
      case 'medical_services':
        return Icons.medical_services;
      case 'person':
      case 'person_add':
        return Icons.person_add;
      case 'check_circle':
        return Icons.check_circle;
      default:
        return Icons.notifications;
    }
  }

  Color _colorFromHex(String? hex) {
    if (hex == null || hex.isEmpty) return Colors.blueGrey;
    final cleanHex = hex.replaceFirst('#', '');
    return Color(int.parse('FF$cleanHex', radix: 16));
  }

  // ---------- Data fetch helpers (each fails quietly so one missing
  // endpoint never breaks the rest of the dashboard) ----------

  Future<List?> _tryGet(String path) async {
    try {
      final data = await ApiService.get(path);
      return data as List;
    } catch (_) {
      return null;
    }
  }

  // Tries a few common key names since exact field names weren't confirmed
  // for every table — adjust here if your API uses different keys.
  DateTime? _parseDate(Map item, List<String> keys) {
    for (final key in keys) {
      final raw = item[key];
      if (raw != null) {
        final parsed = DateTime.tryParse(raw.toString());
        if (parsed != null) return parsed;
      }
    }
    return null;
  }

  List<int>? _computeInquiryVolume(List? inquiries) {
    if (inquiries == null || inquiries.isEmpty) return null;
    final counts = List<int>.filled(7, 0); // index 0 = Monday
    var matched = false;
    for (final item in inquiries) {
      if (item is! Map) continue;
      final date = _parseDate(item, ['created_at', 'date', 'createdAt']);
      if (date == null) continue;
      counts[date.weekday - 1]++; // DateTime.weekday: Mon=1 .. Sun=7
      matched = true;
    }
    return matched ? counts : null;
  }

  List<int>? _computeWeeklyVisits(List? visits) {
    if (visits == null || visits.isEmpty) return null;
    final counts = List<int>.filled(_weekCount, 0);
    final now = DateTime.now();
    final startOfThisWeek = DateTime(
      now.year,
      now.month,
      now.day,
    ).subtract(Duration(days: now.weekday - 1));

    var matched = false;
    for (final item in visits) {
      if (item is! Map) continue;
      final date = _parseDate(item, [
        'visit_date',
        'scheduled_date',
        'schedule_date',
        'date',
        'created_at',
      ]);
      if (date == null) continue;
      final weekStart = DateTime(
        date.year,
        date.month,
        date.day,
      ).subtract(Duration(days: date.weekday - 1));
      final weeksAgo = startOfThisWeek.difference(weekStart).inDays ~/ 7;
      final index = _weekCount - 1 - weeksAgo;
      if (index >= 0 && index < _weekCount) {
        counts[index]++;
        matched = true;
      }
    }
    return matched ? counts : null;
  }

  Map<String, double>? _computeSentiment(List? feedback) {
    if (feedback == null || feedback.isEmpty) return null;
    int positive = 0, neutral = 0, negative = 0;
    for (final item in feedback) {
      if (item is! Map) continue;
      final rating = num.tryParse('${item['star_rating']}') ?? 0;
      if (rating >= 4) {
        positive++;
      } else if (rating == 3) {
        neutral++;
      } else if (rating > 0) {
        negative++;
      }
    }
    final total = positive + neutral + negative;
    if (total == 0) return null;
    return {
      'Positive': positive / total * 100,
      'Neutral': neutral / total * 100,
      'Negative': negative / total * 100,
    };
  }

  List<Map<String, dynamic>>? _parseServiceDistribution(List? raw) {
    if (raw == null || raw.isEmpty) return null;
    final result = <Map<String, dynamic>>[];
    for (final item in raw) {
      if (item is! Map) continue;
      final type = item['service_type'] ?? item['serviceType'] ?? 'Unknown';
      final total = num.tryParse('${item['total']}') ?? 0;
      result.add({'service_type': type.toString(), 'total': total});
    }
    return result.isEmpty ? null : result;
  }

  Future<void> _preloadNotifications() async {
    try {
      await NotificationData.load();
      if (mounted) setState(() {});
    } catch (_) {
      // non-critical — badge will just show 0 until the Notifications page is visited
    }
  }

  Future<void> _loadDashboardData() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });

    try {
      // Core stats — required for the app to be usable, so these stay in
      // the main Future.wait and a failure here surfaces the retry screen.
      final results = await Future.wait([
        ApiService.get('/appointments'),
        ApiService.get('/inquiries'),
        ApiService.get('/doctors'),
        ApiService.get('/patients'),
        ApiService.get('/notifications'),
      ]);

      final appointments = results[0] as List;
      final inquiries = results[1] as List;
      final doctors = results[2] as List;
      final patients = results[3] as List;
      final notifications = results[4] as List;

      // Chart data — each endpoint may not exist yet on the backend, so
      // these are fetched independently and allowed to fail gracefully.
      final feedback = await _tryGet('/feedback');
      final serviceDistribution = await _tryGet('/service-distribution');
      final scheduleVisits = await _tryGet('/schedule-visits');

      final sentiment = _computeSentiment(feedback);
      final services = _parseServiceDistribution(serviceDistribution);
      final weeklyVisits = _computeWeeklyVisits(scheduleVisits);
      final inquiryVolume = _computeInquiryVolume(inquiries);

      setState(() {
        _appointmentsCount = appointments.length;
        _pendingInquiriesCount = inquiries
            .where((i) => i['isNew'] == true)
            .length;
        _activeDoctorsCount = doctors
            .where((d) => d['status'] == 'Available')
            .length;
        _patientsCount = patients.length;

        if (sentiment != null) {
          _sentimentPercents = sentiment;
          _sentimentFromApi = true;
        }
        if (services != null) {
          _serviceDistribution = services;
        }
        if (weeklyVisits != null) {
          _weeklyVisits = weeklyVisits;
        }
        if (inquiryVolume != null) {
          _inquiryVolumeByDay = inquiryVolume;
          _inquiryVolumeFromApi = true;
        }

        // Reuse the notifications feed as the "Recent Activities" list —
        // swap this for a dedicated /activities endpoint later if you add one.
        _recentActivities = notifications.take(5).map<Map<String, dynamic>>((
          n,
        ) {
          return {
            'icon': _iconFromName(n['icon']),
            'title': n['title'],
            'description': n['message'],
            'time': n['time'],
            'color': _colorFromHex(n['color']),
          };
        }).toList();

        _loading = false;
      });

      // Preload notifications separately so the bell badge count is accurate
      // from the moment the app opens, not just after visiting that page.
      unawaited(_preloadNotifications());
    } catch (e) {
      setState(() {
        _loadError = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: _buildAppBar(),
      drawer: _buildDrawer(),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _loadError != null
          ? Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(_loadError!, style: const TextStyle(color: Colors.red)),
                  const SizedBox(height: 12),
                  ElevatedButton(
                    onPressed: _loadDashboardData,
                    child: const Text('Retry'),
                  ),
                ],
              ),
            )
          : RefreshIndicator(
              onRefresh: _loadDashboardData,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildGreetingCard(),
                    const SizedBox(height: 20),
                    _buildStatsGrid(),
                    const SizedBox(height: 20),
                    _buildServiceDistribution(),
                    const SizedBox(height: 20),
                    _buildWeeklyPatientVisits(),
                    const SizedBox(height: 20),
                    _buildSentimentAnalysis(),
                    const SizedBox(height: 20),
                    _buildInquiryVolume(),
                    const SizedBox(height: 20),
                    _buildRecentActivities(),
                  ],
                ),
              ),
            ),
      bottomNavigationBar: _buildBottomNav(),
    );
  }

  BoxDecoration _cardDecoration() {
    return BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      boxShadow: [
        BoxShadow(
          color: Colors.grey.withValues(alpha: 0.1),
          spreadRadius: 1,
          blurRadius: 4,
          offset: const Offset(0, 2),
        ),
      ],
    );
  }

  Widget _cardHeader(IconData icon, String title) {
    return Row(
      children: [
        Icon(icon, color: const Color(0xFF1A237E), size: 20),
        const SizedBox(width: 8),
        Text(
          title,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: Color(0xFF1A237E),
          ),
        ),
      ],
    );
  }

  // Greeting Card with colored background - NO CIRCLE
  Widget _buildGreetingCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF1A237E), Color(0xFF283593)],
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF1A237E).withValues(alpha: 0.3),
            spreadRadius: 2,
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: const Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Welcome back, Staff!',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
          SizedBox(height: 4),
          Text(
            "Here's what's happening at the clinic today",
            style: TextStyle(fontSize: 14, color: Colors.white70),
          ),
        ],
      ),
    );
  }

  // 4 Stats Cards (2 rows of 2) — now using real counts from the API
  Widget _buildStatsGrid() {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                '$_appointmentsCount',
                'Schedule Visits',
                Icons.calendar_today,
                Colors.blue,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildStatCard(
                '$_pendingInquiriesCount',
                'Pending Inquiries',
                Icons.question_answer,
                Colors.orange,
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                '$_activeDoctorsCount',
                'Active Doctors',
                Icons.medical_services,
                Colors.green,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildStatCard(
                '$_patientsCount',
                'Registered Patients',
                Icons.people,
                Colors.purple,
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildStatCard(
    String number,
    String label,
    IconData icon,
    Color color,
  ) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(height: 8),
          Text(
            number,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1A237E),
            ),
          ),
          Text(
            label,
            style: const TextStyle(fontSize: 11, color: Colors.grey),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  // ---------------- 1) Service Distribution — doughnut (PieChart) ----------------
  Widget _buildServiceDistribution() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHeader(Icons.pie_chart, 'Service Distribution'),
          const SizedBox(height: 4),
          Text(
            'Distribution of patient visits by services',
            style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
          ),
          const SizedBox(height: 16),
          if (_serviceDistribution.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: Text(
                'No service data available yet.',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
              ),
            )
          else ...[
            SizedBox(
              height: 160,
              child: PieChart(
                PieChartData(
                  sectionsSpace: 2,
                  centerSpaceRadius: 32,
                  sections: List.generate(_serviceDistribution.length, (i) {
                    final entry = _serviceDistribution[i];
                    return PieChartSectionData(
                      value: (entry['total'] as num).toDouble(),
                      color: _serviceColors[i % _serviceColors.length],
                      radius: 42,
                      showTitle: false,
                    );
                  }),
                ),
              ),
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 12,
              runSpacing: 6,
              children: List.generate(_serviceDistribution.length, (i) {
                final entry = _serviceDistribution[i];
                return Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 10,
                      height: 10,
                      decoration: BoxDecoration(
                        color: _serviceColors[i % _serviceColors.length],
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      '${entry['service_type']} (${entry['total']})',
                      style: const TextStyle(fontSize: 11, color: Colors.grey),
                    ),
                  ],
                );
              }),
            ),
          ],
        ],
      ),
    );
  }

  // ---------------- 2) Weekly Patient Visits — line chart ----------------
  Widget _buildWeeklyPatientVisits() {
    final maxVal = _weeklyVisits.isEmpty
        ? 0
        : _weeklyVisits.reduce((a, b) => a > b ? a : b);
    final maxY = maxVal == 0 ? 5.0 : (maxVal * 1.25).ceilToDouble();
    final interval = maxY <= 5 ? 1.0 : (maxY / 5).ceilToDouble();

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHeader(Icons.show_chart, 'Weekly Patient Visits'),
          const SizedBox(height: 4),
          Text(
            'Visits over the last $_weekCount weeks',
            style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
          ),
          const SizedBox(height: 16),
          SizedBox(
            height: 180,
            child: LineChart(
              LineChartData(
                minY: 0,
                maxY: maxY,
                gridData: FlGridData(
                  show: true,
                  drawVerticalLine: false,
                  horizontalInterval: interval,
                  getDrawingHorizontalLine: (value) =>
                      FlLine(color: Colors.grey.shade200, strokeWidth: 1),
                ),
                borderData: FlBorderData(show: false),
                titlesData: FlTitlesData(
                  leftTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      reservedSize: 26,
                      interval: interval,
                      getTitlesWidget: (value, meta) => Text(
                        value.toInt().toString(),
                        style: const TextStyle(
                          fontSize: 10,
                          color: Colors.grey,
                        ),
                      ),
                    ),
                  ),
                  rightTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  topTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (value, meta) {
                        final i = value.toInt();
                        if (i < 0 || i >= _weekLabels.length) {
                          return const SizedBox.shrink();
                        }
                        return Padding(
                          padding: const EdgeInsets.only(top: 6),
                          child: Text(
                            _weekLabels[i],
                            style: const TextStyle(
                              fontSize: 9,
                              color: Colors.grey,
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ),
                lineBarsData: [
                  LineChartBarData(
                    spots: List.generate(
                      _weeklyVisits.length,
                      (i) => FlSpot(i.toDouble(), _weeklyVisits[i].toDouble()),
                    ),
                    isCurved: true,
                    color: const Color(0xFF1A237E),
                    barWidth: 3,
                    dotData: const FlDotData(show: true),
                    belowBarData: BarAreaData(
                      show: true,
                      color: const Color(0xFF1A237E).withValues(alpha: 0.08),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ---------------- 3) Sentiment Analysis — custom bars ----------------
  Widget _buildSentimentAnalysis() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHeader(Icons.feedback, 'Sentiment Analysis'),
          const SizedBox(height: 4),
          Text(
            _sentimentFromApi
                ? 'Based on recent patient feedback'
                : 'Sample data — connect the /feedback endpoint for live figures',
            style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
          ),
          const SizedBox(height: 16),
          ..._sentimentPercents.entries.map(
            (entry) => _buildSentimentBar(entry.key, entry.value),
          ),
        ],
      ),
    );
  }

  Widget _buildSentimentBar(String label, double percent) {
    final color = _sentimentColors[label] ?? Colors.blueGrey;
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: Colors.grey.shade700,
                ),
              ),
              Text(
                '${percent.toStringAsFixed(0)}%',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: color,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: LinearProgressIndicator(
              value: (percent / 100).clamp(0, 1),
              minHeight: 10,
              backgroundColor: color.withValues(alpha: 0.12),
              valueColor: AlwaysStoppedAnimation<Color>(color),
            ),
          ),
        ],
      ),
    );
  }

  // ---------------- 4) Inquiry Volume per week — bar chart ----------------
  Widget _buildInquiryVolume() {
    final maxCount = _inquiryVolumeByDay.isEmpty
        ? 0
        : _inquiryVolumeByDay.reduce((a, b) => a > b ? a : b);
    final maxY = maxCount == 0 ? 5.0 : (maxCount * 1.25).ceilToDouble();
    final interval = maxY <= 5 ? 1.0 : (maxY / 5).ceilToDouble();

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHeader(Icons.bar_chart, 'Inquiry Volume per Week'),
          const SizedBox(height: 4),
          Text(
            _inquiryVolumeFromApi
                ? 'Inquiries received per day this week'
                : 'Sample data — inquiry dates not yet available from the API',
            style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
          ),
          const SizedBox(height: 16),
          SizedBox(
            height: 180,
            child: BarChart(
              BarChartData(
                alignment: BarChartAlignment.spaceAround,
                maxY: maxY,
                barTouchData: BarTouchData(
                  touchTooltipData: BarTouchTooltipData(
                    getTooltipItem: (group, groupIndex, rod, rodIndex) {
                      return BarTooltipItem(
                        '${_weekdayLabels[group.x]}\n${rod.toY.toInt()} inquiries',
                        const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 11,
                        ),
                      );
                    },
                  ),
                ),
                titlesData: FlTitlesData(
                  leftTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      reservedSize: 26,
                      interval: interval,
                      getTitlesWidget: (value, meta) => Text(
                        value.toInt().toString(),
                        style: const TextStyle(
                          fontSize: 10,
                          color: Colors.grey,
                        ),
                      ),
                    ),
                  ),
                  rightTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  topTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (value, meta) {
                        final i = value.toInt();
                        if (i < 0 || i >= _weekdayLabels.length) {
                          return const SizedBox.shrink();
                        }
                        return Padding(
                          padding: const EdgeInsets.only(top: 6),
                          child: Text(
                            _weekdayLabels[i],
                            style: const TextStyle(
                              fontSize: 10,
                              color: Colors.grey,
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ),
                gridData: FlGridData(
                  show: true,
                  drawVerticalLine: false,
                  horizontalInterval: interval,
                  getDrawingHorizontalLine: (value) =>
                      FlLine(color: Colors.grey.shade200, strokeWidth: 1),
                ),
                borderData: FlBorderData(show: false),
                barGroups: List.generate(7, (i) {
                  return BarChartGroupData(
                    x: i,
                    barRods: [
                      BarChartRodData(
                        toY: _inquiryVolumeByDay[i].toDouble(),
                        color: const Color(0xFF1A237E),
                        width: 18,
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ],
                  );
                }),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // Recent Activities
  Widget _buildRecentActivities() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHeader(Icons.access_time, 'Recent Activities'),
          const SizedBox(height: 12),
          ..._recentActivities.map((activity) => _buildActivityItem(activity)),
        ],
      ),
    );
  }

  Widget _buildActivityItem(Map<String, dynamic> activity) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: BoxDecoration(
        border: Border(
          bottom: BorderSide(color: Colors.grey.shade200, width: 1),
        ),
      ),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: activity['color'].withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(activity['icon'], color: activity['color'], size: 18),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  activity['title'],
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF1A237E),
                  ),
                ),
                Text(
                  activity['description'],
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          Text(
            activity['time'],
            style: TextStyle(fontSize: 10, color: Colors.grey.shade400),
          ),
        ],
      ),
    );
  }

  PreferredSizeWidget _buildAppBar() {
    return AppBar(
      title: Row(
        children: [
          Image.asset(
            'assets/PCLOGO.png',
            width: 35,
            height: 35,
            fit: BoxFit.contain,
          ),
          const SizedBox(width: 12),
          const Text(
            'Polyclinic',
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 22,
              letterSpacing: 0.5,
              color: Color(0xFF1A237E),
            ),
          ),
        ],
      ),
      backgroundColor: Colors.white,
      foregroundColor: const Color(0xFF1A237E),
      elevation: 2,
      centerTitle: false,
      iconTheme: const IconThemeData(color: Color(0xFF1A237E)),
      actions: [
        NotificationBadge(
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => const NotificationsPage(),
              ),
            );
          },
        ),
        IconButton(
          icon: const Icon(Icons.logout, color: Color(0xFF1A237E)),
          onPressed: () {
            _showLogoutDialog(context);
          },
        ),
      ],
    );
  }

  Widget _buildDrawer() {
    return Drawer(
      child: Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            color: const Color(0xFF1A237E),
            child: Column(
              children: [
                const SizedBox(height: 30),
                Container(
                  width: 90,
                  height: 90,
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                  ),
                  child: ClipOval(
                    child: Image.asset(
                      'assets/PCLOGO.png',
                      width: 80,
                      height: 80,
                      fit: BoxFit.contain,
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                const Text(
                  'Staff Name',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const Text(
                  'staff@polyclinic.com',
                  style: TextStyle(color: Colors.white70, fontSize: 13),
                ),
              ],
            ),
          ),
          _buildDrawerItem(Icons.person, 'Profile', false, () {
            Navigator.pop(context);
            Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => const ProfilePage()),
            );
          }),
          _buildDrawerItem(Icons.settings, 'Settings', false, () {
            Navigator.pop(context);
            Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => const SettingsPage()),
            );
          }),
          _buildDrawerItem(Icons.help, 'Help', false, () {
            Navigator.pop(context);
            Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => const HelpPage()),
            );
          }),
          const Divider(),
          _buildDrawerItem(Icons.logout, 'Logout', false, () {
            Navigator.pop(context);
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => const LoginScreen()),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildDrawerItem(
    IconData icon,
    String title,
    bool isActive,
    VoidCallback onTap,
  ) {
    return ListTile(
      leading: Icon(
        icon,
        color: isActive ? const Color(0xFF1A237E) : Colors.grey.shade600,
      ),
      title: Text(
        title,
        style: TextStyle(
          fontWeight: isActive ? FontWeight.bold : FontWeight.normal,
          color: isActive ? const Color(0xFF1A237E) : Colors.grey.shade800,
        ),
      ),
      trailing: isActive
          ? Container(width: 4, height: 24, color: const Color(0xFF1A237E))
          : null,
      onTap: onTap,
    );
  }

  Widget _buildBottomNav() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.2),
            spreadRadius: 1,
            blurRadius: 8,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: BottomNavigationBar(
        type: BottomNavigationBarType.fixed,
        backgroundColor: Colors.white,
        selectedItemColor: const Color(0xFF1A237E),
        unselectedItemColor: Colors.grey.shade400,
        selectedFontSize: 11,
        unselectedFontSize: 11,
        currentIndex: 0,
        onTap: (index) {
          switch (index) {
            case 0:
              break;
            case 1:
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (context) => const InquiriesPage()),
                (route) => false,
              );
              break;
            case 2:
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(
                  builder: (context) => const AppointmentsPage(),
                ),
                (route) => false,
              );
              break;
            case 3:
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (context) => const DoctorsPage()),
                (route) => false,
              );
              break;
            case 4:
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (context) => const PatientsPage()),
                (route) => false,
              );
              break;
          }
        },
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.dashboard),
            label: 'Dashboard',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.question_answer),
            label: 'Inquiries',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.calendar_today),
            label: 'Schedule Visits',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.medical_services),
            label: 'Doctors',
          ),
          BottomNavigationBarItem(icon: Icon(Icons.people), label: 'Patients'),
        ],
      ),
    );
  }

  void _showLogoutDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Logout'),
        content: const Text('Are you sure you want to logout?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(context);
              await ApiService.logout();
              if (!context.mounted) return;
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (context) => const LoginScreen()),
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              foregroundColor: Colors.white,
            ),
            child: const Text('Logout'),
          ),
        ],
      ),
    );
  }
}