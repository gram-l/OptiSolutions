import 'dart:async';
import 'package:flutter/material.dart';
import 'staffcolor.dart';
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
  List<Map<String, dynamic>> _recentActivities = [];
  bool _loading = true;
  String? _loadError;

  // Logged-in staff name
  String _staffName = 'Staff';
  String _staffEmail = '';

  int _appointmentsCount = 0;
  int _pendingInquiriesCount = 0;
  int _activeDoctorsCount = 0;
  int _patientsCount = 0;

  // ---- Chart data ----
  List<Map<String, dynamic>> _serviceDistribution = [];
  String _serviceDistributionSource = '';

  static const int _weekCount = 6;
  List<int> _weeklyVisits = List.filled(_weekCount, 0);
  bool _weeklyVisitsFromApi = false;
  String _weeklyVisitsSource = '';
  List<String> _weekLabels = List.generate(
    _weekCount,
    (i) => i == _weekCount - 1 ? 'This wk' : 'W-${_weekCount - 1 - i}',
  );

  Map<String, double> _sentimentPercents = {};
  bool _sentimentFromApi = false;

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
    StaffColors.primary,
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
    _loadStaffName();
    _loadDashboardData();
  }

  Future<void> _loadStaffName() async {
    try {
      final user = await ApiService.getCurrentUser();
      if (user != null && mounted) {
        setState(() {
          if (user['name'] != null) _staffName = user['name'].toString();
          if (user['email'] != null) _staffEmail = user['email'].toString();
        });
      }
    } catch (e) {
      print('Error loading staff name: $e');
    }
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


  Future<Map<String, dynamic>?> _tryGetMap(String path) async {
    try {
      final data = await ApiService.get(path);
      if (data is Map<String, dynamic>) return data;
      if (data is Map) return Map<String, dynamic>.from(data);
      print('Unexpected response from $path: $data');
      return null;
    } catch (e) {
      print('Error fetching $path: $e');
      return null;
    }
  }

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
    final counts = List<int>.filled(7, 0);
    var matched = false;
    for (final item in inquiries) {
      if (item is! Map) continue;
      final date = _parseDate(item, ['created_at', 'date', 'createdAt']);
      if (date == null) continue;
      counts[date.weekday - 1]++;
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
        'appointment_date',
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

  List<Map<String, dynamic>>? _parseServiceDistribution(dynamic raw) {
    if (raw is! List || raw.isEmpty) return null;
    final result = <Map<String, dynamic>>[];
    for (final item in raw) {
      if (item is! Map) continue;
      final type = item['service_type'] ?? item['serviceType'] ?? 'Unknown';
      final total = num.tryParse('${item['total']}') ?? 0;
      result.add({'service_type': type.toString(), 'total': total});
    }
    return result.isEmpty ? null : result;
  }

  List<int>? _parseIntList(dynamic raw) {
    if (raw is! List || raw.isEmpty) return null;
    final parsed = raw.map((e) => num.tryParse('$e')?.toInt() ?? 0).toList();
    return parsed.any((v) => v > 0) ? parsed : null;
  }

  Map<String, double>? _parseSentimentMap(Map<String, dynamic>? data) {
    if (data == null) return null;
    final positive = num.tryParse('${data['positivePercent']}')?.toDouble();
    final neutral = num.tryParse('${data['neutralPercent']}')?.toDouble();
    final negative = num.tryParse('${data['negativePercent']}')?.toDouble();
    if (positive == null && neutral == null && negative == null) return null;
    final p = positive ?? 0;
    final n = neutral ?? 0;
    final neg = negative ?? 0;
    if (p == 0 && n == 0 && neg == 0) return null;
    return {'Positive': p, 'Neutral': n, 'Negative': neg};
  }

  List<Map<String, dynamic>>? _deriveServiceDistributionFromAppointments(
    List? appointments,
  ) {
    if (appointments == null || appointments.isEmpty) return null;
    final counts = <String, int>{};
    for (final item in appointments) {
      if (item is! Map) continue;
      final type =
          (item['service_type'] ??
                  item['serviceType'] ??
                  item['service'] ??
                  item['type'])
              ?.toString();
      if (type == null || type.trim().isEmpty) continue;
      counts[type] = (counts[type] ?? 0) + 1;
    }
    if (counts.isEmpty) return null;
    return counts.entries
        .map((e) => {'service_type': e.key, 'total': e.value})
        .toList();
  }

  Future<void> _preloadNotifications() async {
    try {
      await NotificationData.load();
      if (mounted) setState(() {});
    } catch (_) {
      // non-critical
    }
  }

  Future<void> _loadDashboardData() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });

    try {
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

      final dashboardData = await _tryGetMap('/dashboard-data');

      final sentiment =
          _parseSentimentMap(dashboardData) ??
          _computeSentiment(await _safeGetList('/feedback'));

      var services = _parseServiceDistribution(
        dashboardData?['serviceDistribution'],
      );
      var serviceSource = '/dashboard-data';
      if (services == null) {
        services = _deriveServiceDistributionFromAppointments(appointments);
        serviceSource = 'derived from /appointments';
      }

      var weeklyVisits = _parseIntList(dashboardData?['weeklyVisits']);
      var weeklySource = '/dashboard-data';
      if (weeklyVisits == null) {
        weeklyVisits = _computeWeeklyVisits(appointments);
        weeklySource = 'derived from /appointments';
      }

      final weekLabelsRaw = dashboardData?['weekLabels'];
      if (weekLabelsRaw is List && weekLabelsRaw.isNotEmpty) {
        _weekLabels = weekLabelsRaw.map((e) => e.toString()).toList();
      }

      var inquiryVolume = _parseIntList(dashboardData?['inquiryVolumeByDay']);
      inquiryVolume ??= _computeInquiryVolume(inquiries);

      setState(() {
        _appointmentsCount = appointments.length;
        _pendingInquiriesCount = inquiries
            .where((i) => i['isNew'] == true)
            .length;
        _activeDoctorsCount = doctors
            .where((d) => d['status'] == 'Available')
            .length;
        _patientsCount = patients.length;

        // Sentiment - kahit walang data, may display pa rin
        if (sentiment != null && sentiment.isNotEmpty) {
          _sentimentPercents = sentiment;
          _sentimentFromApi = true;
        } else {
          _sentimentPercents = {
            'Positive': 0.0,
            'Neutral': 0.0,
            'Negative': 0.0,
          };
          _sentimentFromApi = false;
        }

        // Service Distribution 
        if (services != null && services.isNotEmpty) {
          _serviceDistribution = services;
          _serviceDistributionSource = serviceSource;
        } else {
          _serviceDistribution = [
            {'service_type': 'No Data', 'total': 1},
          ];
          _serviceDistributionSource = 'No data available';
        }

        // Weekly Visits
        if (weeklyVisits != null && weeklyVisits.any((v) => v > 0)) {
          _weeklyVisits = weeklyVisits;
          _weeklyVisitsFromApi = true;
          _weeklyVisitsSource = weeklySource;
        } else {
          _weeklyVisits = List.filled(_weekCount, 0);
          _weeklyVisitsFromApi = true; 
          _weeklyVisitsSource = 'No data available';
        }

        // Inquiry Volume - kahit walang data, may display
        if (inquiryVolume != null && inquiryVolume.any((v) => v > 0)) {
          _inquiryVolumeByDay = inquiryVolume;
          _inquiryVolumeFromApi = true;
        } else {
          _inquiryVolumeByDay = List.filled(7, 0);
          _inquiryVolumeFromApi =
              true; 
        }

        _recentActivities = notifications.take(5).map<Map<String, dynamic>>((
          n,
        ) {
          return {
            'icon': _iconFromName(n['icon']),
            'title': n['title'] ?? '',
            'description': n['message'] ?? '',
            'time': n['time'] ?? '',
            'color': _colorFromHex(n['color']),
          };
        }).toList();

        _loading = false;
      });

      unawaited(_preloadNotifications());
    } catch (e) {
      setState(() {
        _loadError = e.toString().replaceFirst('Exception: ', '');
        _loading = false;

        _serviceDistribution = [
          {'service_type': 'No Data', 'total': 1},
        ];
        _weeklyVisits = List.filled(_weekCount, 0);
        _weeklyVisitsFromApi = true;
        _sentimentPercents = {'Positive': 0.0, 'Neutral': 0.0, 'Negative': 0.0};
        _sentimentFromApi = false;
        _inquiryVolumeByDay = List.filled(7, 0);
        _inquiryVolumeFromApi = true;
      });
    }
  }

  Future<List?> _safeGetList(String path) async {
    try {
      final data = await ApiService.get(path);
      if (data is List) return data;
      if (data is Map && data['data'] is List) return data['data'] as List;
      return null;
    } catch (e) {
      return null;
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
                  Icon(
                    Icons.warning_amber_rounded,
                    color: Colors.orange,
                    size: 48,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'May issue sa pag-load ng data',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Narito ang dashboard kahit walang data',
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _loadDashboardData,
                    child: const Text('I-retry'),
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
        Icon(icon, color: StaffColors.primary, size: 20),
        const SizedBox(width: 8),
        Text(
          title,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: Colors.black87,
          ),
        ),
      ],
    );
  }

  // Empty state message 
  Widget _emptyChartState(String message) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 24),
      child: Center(
        child: Column(
          children: [
            Icon(
              Icons.insert_chart_outlined,
              color: Colors.grey.shade300,
              size: 32,
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildGreetingCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [StaffColors.primary, Color(0xFF283593)],
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: StaffColors.primary.withValues(alpha: 0.3),
            spreadRadius: 2,
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Welcome back, $_staffName!',
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 4),
          const Text(
            "Here's what's happening at the clinic today",
            style: TextStyle(fontSize: 14, color: Colors.white70),
          ),
        ],
      ),
    );
  }

  Widget _buildStatsGrid() {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                '$_appointmentsCount',
                'Schedule Visits',
                'Scheduled visits',
                Icons.calendar_today,
                Colors.blue,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildStatCard(
                '$_pendingInquiriesCount',
                'Pending Inquiries',
                'Awaiting reply',
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
                'Currently practicing',
                Icons.medical_services,
                Colors.green,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildStatCard(
                '$_patientsCount',
                'Registered Patients',
                'Total records',
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
    String subLabel,
    IconData icon,
    Color color,
  ) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [

          Row(
            children: [
              Icon(icon, color: color, size: 14),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  label.toUpperCase(),
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                    color: Colors.grey.shade600,
                    letterSpacing: 0.4,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          
          Text(
            number,
            style: const TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          const SizedBox(height: 4),
         
          Text(
            subLabel,
            style: TextStyle(fontSize: 11, color: Colors.grey.shade400),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  // ---------------- 1) Service Distribution — doughnut (PieChart) ----------------
  Widget _buildServiceDistribution() {
    // Siguraduhing may data palagi
    final displayData = _serviceDistribution.isEmpty
        ? [
            {'service_type': 'No Data', 'total': 1},
          ]
        : _serviceDistribution;


    final isNoData =
        _serviceDistribution.isEmpty ||
        (_serviceDistribution.length == 1 &&
            _serviceDistribution[0]['service_type'] == 'No Data');

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
          SizedBox(
            height: 160,
            child: PieChart(
              PieChartData(
                sectionsSpace: 2,
                centerSpaceRadius: 32,
                sections: List.generate(displayData.length, (i) {
                  final entry = displayData[i];
                  final isNoDataItem = entry['service_type'] == 'No Data';
                  return PieChartSectionData(
                    value: (entry['total'] as num).toDouble(),
                    color: isNoDataItem
                        ? Colors.grey.shade300
                        : _serviceColors[i % _serviceColors.length],
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
            children: List.generate(displayData.length, (i) {
              final entry = displayData[i];
              final isNoDataItem = entry['service_type'] == 'No Data';
              return Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 10,
                    height: 10,
                    decoration: BoxDecoration(
                      color: isNoDataItem
                          ? Colors.grey.shade300
                          : _serviceColors[i % _serviceColors.length],
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    isNoDataItem
                        ? 'No data available'
                        : '${entry['service_type']} (${entry['total']})',
                    style: TextStyle(
                      fontSize: 11,
                      color: isNoDataItem ? Colors.grey.shade500 : Colors.grey,
                    ),
                  ),
                ],
              );
            }),
          ),
        ],
      ),
    );
  }

  // ---------------- 2) Weekly Patient Visits — line chart ----------------
  Widget _buildWeeklyPatientVisits() {
    // Ensure may data
    final displayData = _weeklyVisits.every((v) => v == 0)
        ? List.filled(_weekCount, 0)
        : _weeklyVisits;

    final maxVal = displayData.isEmpty
        ? 0
        : displayData.reduce((a, b) => a > b ? a : b);
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
                      displayData.length,
                      (i) => FlSpot(i.toDouble(), displayData[i].toDouble()),
                    ),
                    isCurved: true,
                    color: displayData.every((v) => v == 0)
                        ? Colors.grey.shade400
                        : StaffColors.primary,
                    barWidth: 3,
                    dotData: const FlDotData(show: true),
                    belowBarData: BarAreaData(
                      show: true,
                      color: displayData.every((v) => v == 0)
                          ? Colors.grey.shade200
                          : StaffColors.primary.withValues(alpha: 0.08),
                    ),
                  ),
                ],
              ),
            ),
          ),
          if (displayData.every((v) => v == 0))
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                'No data available',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
              ),
            ),
        ],
      ),
    );
  }

  // ---------------- 3) Sentiment Analysis — custom bars ----------------
  Widget _buildSentimentAnalysis() {
    final displayData =
        _sentimentPercents.isEmpty ||
            (_sentimentPercents['Positive'] == 0 &&
                _sentimentPercents['Neutral'] == 0 &&
                _sentimentPercents['Negative'] == 0)
        ? {'Positive': 0.0, 'Neutral': 0.0, 'Negative': 0.0}
        : _sentimentPercents;

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
                : 'No feedback available yet',
            style: TextStyle(
              fontSize: 12,
              color: _sentimentFromApi
                  ? Colors.grey.shade600
                  : Colors.grey.shade500,
            ),
          ),
          const SizedBox(height: 16),
          ...displayData.entries.map(
            (entry) => _buildSentimentBar(
              entry.key,
              entry.value,
              entry.value == 0 && !_sentimentFromApi,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSentimentBar(String label, double percent, bool isNoData) {
    final color = isNoData
        ? Colors.grey.shade300
        : _sentimentColors[label] ?? Colors.blueGrey;
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
                  color: isNoData ? Colors.grey.shade500 : Colors.grey.shade700,
                ),
              ),
              Text(
                isNoData ? '0%' : '${percent.toStringAsFixed(0)}%',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: isNoData ? Colors.grey.shade500 : color,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: LinearProgressIndicator(
              value: isNoData ? 0 : (percent / 100).clamp(0, 1),
              minHeight: 10,
              backgroundColor: isNoData
                  ? Colors.grey.shade200
                  : color.withValues(alpha: 0.12),
              valueColor: AlwaysStoppedAnimation<Color>(
                isNoData ? Colors.grey.shade300 : color,
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ---------------- 4) Inquiry Volume per week — bar chart ----------------
  Widget _buildInquiryVolume() {
    final displayData = _inquiryVolumeByDay.every((v) => v == 0)
        ? List.filled(7, 0)
        : _inquiryVolumeByDay;

    final maxCount = displayData.isEmpty
        ? 0
        : displayData.reduce((a, b) => a > b ? a : b);
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
            'Inquiries received per day this week',
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
                  final isNoData =
                      displayData[i] == 0 && displayData.every((v) => v == 0);
                  return BarChartGroupData(
                    x: i,
                    barRods: [
                      BarChartRodData(
                        toY: displayData[i].toDouble(),
                        color: isNoData
                            ? Colors.grey.shade300
                            : StaffColors.primary,
                        width: 18,
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ],
                  );
                }),
              ),
            ),
          ),
          if (displayData.every((v) => v == 0))
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                'No data available',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
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
          if (_recentActivities.isEmpty)
            _emptyChartState('No recent activity yet.')
          else
            ..._recentActivities.map(
              (activity) => _buildActivityItem(activity),
            ),
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
                  activity['title'] ?? '',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: Colors.black87,
                  ),
                ),
                Text(
                  activity['description'] ?? '',
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          Text(
            activity['time'] ?? '',
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
              color: Colors.black87,
            ),
          ),
        ],
      ),
      backgroundColor: Colors.white,
      foregroundColor: StaffColors.primary,
      elevation: 2,
      centerTitle: false,
      iconTheme: const IconThemeData(color: StaffColors.primary),
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
          icon: const Icon(Icons.logout, color: StaffColors.primary),
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
            color: StaffColors.primary,
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
                Text(
                  _staffName,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(
                  _staffEmail.isNotEmpty ? _staffEmail : 'staff@polyclinic.com',
                  style: const TextStyle(color: Colors.white70, fontSize: 13),
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
            _showLogoutDialog(context);
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
        color: isActive ? StaffColors.primary : Colors.grey.shade600,
      ),
      title: Text(
        title,
        style: TextStyle(
          fontWeight: isActive ? FontWeight.bold : FontWeight.normal,
          color: isActive ? StaffColors.primary : Colors.grey.shade800,
        ),
      ),
      trailing: isActive
          ? Container(width: 4, height: 24, color: StaffColors.primary)
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
        selectedItemColor: StaffColors.primary,
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
            label: 'Chatbot Inquiries',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.calendar_today),
            label: 'Schedule Visits',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.medical_services),
            label: 'Mange Doctors',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.people),
            label: 'Patient Records',
          ),
        ],
      ),
    );
  }

  void _showLogoutDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Logout'),
        content: const Text('Are you sure you want to logout?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(dialogContext);
              try {
                await ApiService.logout();
              } catch (e) {
                print('Logout error: $e');
              }
              if (!context.mounted) return;
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (context) => const LoginScreen()),
                (route) => false,
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
