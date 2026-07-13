// dashboard.dart
import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';
import 'colors.dart';
import 'side_panel.dart';
import '../main.dart' show appMenuItems;
import '../admin_mobile/services/dashboard_service.dart';

// ---------- MAIN SCREEN ----------
class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  bool _loading = true;
  String? _loadError;

  // ---- Stat cards ----
  int _totalInquiries = 0;
  int _todaysAppointments = 0;
  int _pendingApproval = 0;
  int _activePatients = 0;
  int _newPatientsThisMonth = 0;
  double _avgRating = 0;

  // ---- 1) Service Distribution ----
  List<Map<String, dynamic>> _serviceDistribution = [];

  // ---- 2) Weekly Patient Visits ----
  static const int _weekCount = 6;
  List<int> _weeklyVisits = List.filled(_weekCount, 0);
  List<String> _weekLabels = List.generate(
    _weekCount,
    (i) => i == _weekCount - 1 ? 'This wk' : 'W-${_weekCount - 1 - i}',
  );

  // ---- 3) Sentiment Analysis ----
  Map<String, double> _sentimentPercents = const {
    'Positive': 0,
    'Neutral': 0,
    'Negative': 0,
  };

  // ---- 4) Inquiry Volume per weekday ----
  List<int> _inquiryVolumeByDay = List.filled(7, 0);

  static const List<String> _weekdayLabels = [
    'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',
  ];

  static const List<Color> _serviceColors = [
    Color(0xFF0E62AA),
    Color(0xFF3969A8),
    Color(0xFF5C8BC0),
    Color(0xFF79A8CB),
    Color(0xFF9DBCD4),
    Color(0xFF00897B),
    Color(0xFF43A047),
    Color(0xFFFB8C00),
  ];

  @override
  void initState() {
    super.initState();
    _loadDashboardData();
  }

  List<Map<String, dynamic>>? _parseServiceDistribution(dynamic raw) {
    if (raw is! List || raw.isEmpty) return null;
    final result = <Map<String, dynamic>>[];
    for (final item in raw) {
      if (item is! Map) continue;
      final type = item['service_type'] ?? 'Unknown';
      final total = num.tryParse('${item['total']}') ?? 0;
      result.add({'service_type': type.toString(), 'total': total});
    }
    return result.isEmpty ? null : result;
  }

  List<int> _parseIntList(dynamic raw, int fallbackLength) {
    if (raw is! List || raw.isEmpty) return List.filled(fallbackLength, 0);
    return raw.map((e) => num.tryParse('$e')?.toInt() ?? 0).toList();
  }

  Future<void> _loadDashboardData() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });

    try {
      final data = await DashboardService.getDashboardData();

      final services = _parseServiceDistribution(data['serviceDistribution']);
      final weekly = _parseIntList(data['weeklyVisits'], _weekCount);
      final inquiryVolume = _parseIntList(data['inquiryVolumeByDay'], 7);
      final rawWeekLabels = data['weekLabels'];

      setState(() {
        _totalInquiries = num.tryParse('${data['totalInquiries']}')?.toInt() ?? 0;
        _todaysAppointments = num.tryParse('${data['todaysAppointments']}')?.toInt() ?? 0;
        _pendingApproval = num.tryParse('${data['pendingApproval']}')?.toInt() ?? 0;
        _activePatients = num.tryParse('${data['activePatients']}')?.toInt() ?? 0;
        _newPatientsThisMonth = num.tryParse('${data['newPatientsThisMonth']}')?.toInt() ?? 0;
        _avgRating = num.tryParse('${data['avgRating']}')?.toDouble() ?? 0;

        if (services != null) _serviceDistribution = services;
        _weeklyVisits = weekly;
        if (rawWeekLabels is List) {
          _weekLabels = rawWeekLabels.map((e) => e.toString()).toList();
        }
        _inquiryVolumeByDay = inquiryVolume;

        final positive = num.tryParse('${data['positivePercent']}')?.toDouble() ?? 0;
        final neutral = num.tryParse('${data['neutralPercent']}')?.toDouble() ?? 0;
        final negative = num.tryParse('${data['negativePercent']}')?.toDouble() ?? 0;
        _sentimentPercents = {
          'Positive': positive,
          'Neutral': neutral,
          'Negative': negative,
        };

        _loading = false;
      });
    } catch (e) {
      setState(() {
        _loadError = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  void _navigateTo(String route) {
    if (route != '/dashboard') {
      Navigator.pushNamed(context, route);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/dashboard',
        onItemTap: _navigateTo,
      ),
      body: SafeArea(
        child: _loading
            ? const Center(child: CircularProgressIndicator())
            : _loadError != null
                ? Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 24),
                          child: Text(_loadError!,
                              textAlign: TextAlign.center,
                              style: const TextStyle(color: Colors.red)),
                        ),
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
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const _TopBar(),
                          const SizedBox(height: 16),
                          const _WelcomeCard(),
                          const SizedBox(height: 16),
                          _StatGrid(
                            totalInquiries: _totalInquiries,
                            todaysAppointments: _todaysAppointments,
                            pendingApproval: _pendingApproval,
                            activePatients: _activePatients,
                            newPatientsThisMonth: _newPatientsThisMonth,
                            avgRating: _avgRating,
                          ),
                          const SizedBox(height: 16),
                          _ServiceDistributionCard(
                            services: _serviceDistribution,
                            colors: _serviceColors,
                          ),
                          const SizedBox(height: 16),
                          _WeeklyVisitsCard(
                            weeklyVisits: _weeklyVisits,
                            weekLabels: _weekLabels,
                          ),
                          const SizedBox(height: 16),
                          _InquiryVolumeCard(inquiryVolumeByDay: _inquiryVolumeByDay),
                          const SizedBox(height: 16),
                          _SentimentCard(sentimentPercents: _sentimentPercents),
                          const SizedBox(height: 16),
                          const _RecentActivityCard(),
                          const SizedBox(height: 16),
                          const _ActionButtonsGrid(),
                          const SizedBox(height: 16),
                          const Center(
                            child: Padding(
                              padding: EdgeInsets.only(bottom: 12),
                              child: Text('Polyclinic Admin v2.0',
                                  style: TextStyle(color: AppColors.textGrey, fontSize: 12)),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
      ),
    );
  }
}

// ---------- TOP BAR ----------
class _TopBar extends StatelessWidget {
  const _TopBar();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: Row(
        children: [
          Builder(
            builder: (context) => IconButton(
              icon: const Icon(Icons.menu, color: AppColors.textDark),
              onPressed: () => Scaffold.of(context).openDrawer(),
            ),
          ),
          CircleAvatar(
            radius: 24,
            backgroundColor: const Color(0xFFE3F2FD),
            child: Image.asset('assets/polyclinic_logo.png', fit: BoxFit.cover),
          ),
          const SizedBox(width: 8),
          const Text('Polyclinic',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const Spacer(),
          Stack(
            clipBehavior: Clip.none,
            children: [
              IconButton(
                icon: const Icon(Icons.notifications_none_rounded, color: AppColors.textDark),
                onPressed: () => Navigator.pushNamed(context, '/notifications'),
              ),
              Positioned(
                right: 10,
                top: 10,
                child: Container(
                  width: 8,
                  height: 8,
                  decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                ),
              ),
            ],
          ),
          const CircleAvatar(
            radius: 14,
            backgroundColor: AppColors.darkNavy,
            child: Icon(Icons.person, color: Colors.white, size: 16),
          ),
        ],
      ),
    );
  }
}

// ---------- WELCOME CARD ----------
class _WelcomeCard extends StatelessWidget {
  const _WelcomeCard();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppColors.primary, Color(0xFF1976D2)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Welcome back, Dr. Lara',
              style: TextStyle(
                  color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 4),
          Text("Here's what's happening with your clinic today.",
              style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 13)),
        ],
      ),
    );
  }
}

// ---------- STAT GRID ----------
class _StatGrid extends StatelessWidget {
  final int totalInquiries;
  final int todaysAppointments;
  final int pendingApproval;
  final int activePatients;
  final int newPatientsThisMonth;
  final double avgRating;

  const _StatGrid({
    required this.totalInquiries,
    required this.todaysAppointments,
    required this.pendingApproval,
    required this.activePatients,
    required this.newPatientsThisMonth,
    required this.avgRating,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _StatCard(
                label: 'TOTAL INQUIRIES',
                value: '$totalInquiries',
                trend: 'Live count',
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _StatCard(
                label: "TODAY'S APPTS",
                value: '$todaysAppointments',
                trend: '$pendingApproval pending approval',
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _StatCard(
                label: 'ACTIVE PATIENTS',
                value: '$activePatients',
                trend: '$newPatientsThisMonth new this month',
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _StatCard(
                label: 'SATISFACTION',
                value: avgRating.toStringAsFixed(1),
                trend: 'Average rating',
                showStar: true,
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final String trend;
  final bool? trendUp;
  final bool showStar;

  const _StatCard({
    required this.label,
    required this.value,
    required this.trend,
    this.trendUp,
    this.showStar = false,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 8,
              offset: const Offset(0, 2)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label,
              style: const TextStyle(
                  fontSize: 10.5,
                  color: AppColors.textGrey,
                  fontWeight: FontWeight.w600,
                  letterSpacing: 0.3)),
          const SizedBox(height: 6),
          Row(
            children: [
              Text(value,
                  style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textDark)),
              if (showStar) ...[
                const SizedBox(width: 4),
                const Icon(Icons.star_rounded, color: Colors.amber, size: 18),
              ],
            ],
          ),
          const SizedBox(height: 4),
          Text(
            trend,
            style: TextStyle(
              fontSize: 11,
              color: trendUp == true ? Colors.green : AppColors.textGrey,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------- 1) SERVICE DISTRIBUTION (doughnut) ----------
class _ServiceDistributionCard extends StatelessWidget {
  final List<Map<String, dynamic>> services;
  final List<Color> colors;

  const _ServiceDistributionCard({required this.services, required this.colors});

  @override
  Widget build(BuildContext context) {
    return _Card(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.pie_chart_rounded, size: 18, color: AppColors.primary),
              SizedBox(width: 6),
              Text('Service Distribution',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
            ],
          ),
          const SizedBox(height: 4),
          Text('Distribution of patient visits by service',
              style: TextStyle(fontSize: 11.5, color: Colors.grey.shade600)),
          const SizedBox(height: 16),
          if (services.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: Text('No service data available yet.',
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
            )
          else ...[
            SizedBox(
              height: 160,
              child: PieChart(
                PieChartData(
                  sectionsSpace: 2,
                  centerSpaceRadius: 32,
                  sections: List.generate(services.length, (i) {
                    final entry = services[i];
                    return PieChartSectionData(
                      value: (entry['total'] as num).toDouble(),
                      color: colors[i % colors.length],
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
              children: List.generate(services.length, (i) {
                final entry = services[i];
                return Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 10,
                      height: 10,
                      decoration: BoxDecoration(
                        color: colors[i % colors.length],
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text('${entry['service_type']} (${entry['total']})',
                        style: const TextStyle(fontSize: 11, color: AppColors.textGrey)),
                  ],
                );
              }),
            ),
          ],
        ],
      ),
    );
  }
}

// ---------- 2) WEEKLY PATIENT VISITS (line) ----------
class _WeeklyVisitsCard extends StatelessWidget {
  final List<int> weeklyVisits;
  final List<String> weekLabels;

  const _WeeklyVisitsCard({required this.weeklyVisits, required this.weekLabels});

  @override
  Widget build(BuildContext context) {
    final maxVal = weeklyVisits.isEmpty ? 0 : weeklyVisits.reduce((a, b) => a > b ? a : b);
    final maxY = maxVal == 0 ? 5.0 : (maxVal * 1.25).ceilToDouble();
    final interval = maxY <= 5 ? 1.0 : (maxY / 5).ceilToDouble();

    return _Card(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.show_chart_rounded, size: 18, color: AppColors.primary),
              SizedBox(width: 6),
              Text('Weekly Patient Visits',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
            ],
          ),
          const SizedBox(height: 4),
          Text('Visits over the last ${weekLabels.length} weeks',
              style: TextStyle(fontSize: 11.5, color: Colors.grey.shade600)),
          const SizedBox(height: 16),
          SizedBox(
            height: 160,
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
                        style: const TextStyle(fontSize: 10, color: Colors.grey),
                      ),
                    ),
                  ),
                  rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (value, meta) {
                        final i = value.toInt();
                        if (i < 0 || i >= weekLabels.length) return const SizedBox.shrink();
                        return Padding(
                          padding: const EdgeInsets.only(top: 6),
                          child: Text(weekLabels[i],
                              style: const TextStyle(fontSize: 9, color: Colors.grey)),
                        );
                      },
                    ),
                  ),
                ),
                lineBarsData: [
                  LineChartBarData(
                    spots: List.generate(
                      weeklyVisits.length,
                      (i) => FlSpot(i.toDouble(), weeklyVisits[i].toDouble()),
                    ),
                    isCurved: true,
                    color: AppColors.darkNavy,
                    barWidth: 3,
                    dotData: const FlDotData(show: true),
                    belowBarData: BarAreaData(
                      show: true,
                      color: AppColors.darkNavy.withValues(alpha: 0.08),
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
}

// ---------- 3) INQUIRY VOLUME (bar) ----------
class _InquiryVolumeCard extends StatelessWidget {
  final List<int> inquiryVolumeByDay;

  const _InquiryVolumeCard({required this.inquiryVolumeByDay});

  static const List<String> days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

  @override
  Widget build(BuildContext context) {
    final maxCount = inquiryVolumeByDay.isEmpty
        ? 0
        : inquiryVolumeByDay.reduce((a, b) => a > b ? a : b);
    final maxY = maxCount == 0 ? 5.0 : (maxCount * 1.25).ceilToDouble();
    final interval = maxY <= 5 ? 1.0 : (maxY / 5).ceilToDouble();

    return _Card(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.bar_chart_rounded, size: 18, color: AppColors.primary),
              const SizedBox(width: 6),
              const Text('Inquiry Volume (last 7 days)',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
              const Spacer(),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFFF0F2F8),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Text('This week',
                    style: TextStyle(fontSize: 11, color: AppColors.textGrey)),
              ),
            ],
          ),
          const SizedBox(height: 16),
          SizedBox(
            height: 160,
            child: BarChart(
              BarChartData(
                alignment: BarChartAlignment.spaceAround,
                maxY: maxY,
                barTouchData: BarTouchData(
                  touchTooltipData: BarTouchTooltipData(
                    getTooltipItem: (group, groupIndex, rod, rodIndex) {
                      return BarTooltipItem(
                        '${days[group.x]}\n${rod.toY.toInt()} inquiries',
                        const TextStyle(
                            color: Colors.white, fontWeight: FontWeight.bold, fontSize: 11),
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
                        style: const TextStyle(fontSize: 10, color: Colors.grey),
                      ),
                    ),
                  ),
                  rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (value, meta) {
                        final i = value.toInt();
                        if (i < 0 || i >= days.length) return const SizedBox.shrink();
                        return Padding(
                          padding: const EdgeInsets.only(top: 6),
                          child: Text(days[i],
                              style: const TextStyle(fontSize: 10, color: Colors.grey)),
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
                        toY: i < inquiryVolumeByDay.length
                            ? inquiryVolumeByDay[i].toDouble()
                            : 0,
                        color: AppColors.darkNavy,
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
}

// ---------- 4) SENTIMENT ANALYSIS ----------
class _SentimentCard extends StatelessWidget {
  final Map<String, double> sentimentPercents;

  const _SentimentCard({required this.sentimentPercents});

  @override
  Widget build(BuildContext context) {
    final positive = sentimentPercents['Positive'] ?? 0;
    final neutral = sentimentPercents['Neutral'] ?? 0;
    final negative = sentimentPercents['Negative'] ?? 0;
    final hasData = (positive + neutral + negative) > 0;

    return _Card(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.bubble_chart_outlined, size: 18, color: AppColors.primary),
              SizedBox(width: 6),
              Text('Sentiment Analysis',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
            ],
          ),
          const SizedBox(height: 14),
          if (!hasData)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Text('No feedback data available yet.',
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
            )
          else ...[
            _SentimentRow(
                label: 'Positive',
                percent: positive / 100,
                percentText: '${positive.toStringAsFixed(0)}%',
                color: AppColors.positive),
            const SizedBox(height: 10),
            _SentimentRow(
                label: 'Neutral',
                percent: neutral / 100,
                percentText: '${neutral.toStringAsFixed(0)}%',
                color: AppColors.neutral),
            const SizedBox(height: 10),
            _SentimentRow(
                label: 'Negative',
                percent: negative / 100,
                percentText: '${negative.toStringAsFixed(0)}%',
                color: AppColors.negative),
          ],
        ],
      ),
    );
  }
}

class _SentimentRow extends StatelessWidget {
  final String label;
  final double percent;
  final String percentText;
  final Color color;

  const _SentimentRow({
    required this.label,
    required this.percent,
    required this.percentText,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        SizedBox(
          width: 60,
          child: Text(label, style: const TextStyle(fontSize: 12, color: AppColors.textGrey)),
        ),
        Expanded(
          child: ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: LinearProgressIndicator(
              value: percent.clamp(0, 1),
              minHeight: 8,
              backgroundColor: const Color(0xFFEDEFF5),
              valueColor: AlwaysStoppedAnimation<Color>(color),
            ),
          ),
        ),
        const SizedBox(width: 8),
        SizedBox(
          width: 36,
          child: Text(percentText,
              textAlign: TextAlign.right,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
        ),
      ],
    );
  }
}

// ---------- RECENT ACTIVITY (static placeholder, unchanged) ----------
class _ActivityItem {
  final IconData icon;
  final Color iconBg;
  final Color iconColor;
  final String title;
  final String subtitle;
  const _ActivityItem(this.icon, this.iconBg, this.iconColor, this.title, this.subtitle);
}

class _RecentActivityCard extends StatelessWidget {
  const _RecentActivityCard();

  static const items = [
    _ActivityItem(Icons.chat_bubble_rounded, Color(0xFFE3F2FD), AppColors.primary,
        'New inquiry from Maria Santos', '2 min ago · Ophthalmology'),
    _ActivityItem(Icons.event_note_rounded, Color(0xFFE8EAF6), AppColors.darkNavy,
        'Appointment scheduled with Dr. Cruz', '15 min ago · May 23, 10:00 AM'),
    _ActivityItem(Icons.star_rounded, Color(0xFFFFF8E1), Colors.amber,
        'New 5-star feedback received', '1 hour ago · ENT Dept'),
    _ActivityItem(Icons.person_add_alt_1_rounded, Color(0xFFE3F2FD), AppColors.primary,
        'New patient registration', '2 hours ago · Pediatrics'),
  ];

  @override
  Widget build(BuildContext context) {
    return _Card(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.access_time_rounded, size: 18, color: AppColors.primary),
              SizedBox(width: 6),
              Text('Recent Activity',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
            ],
          ),
          const SizedBox(height: 12),
          ...items.map((item) => Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    CircleAvatar(
                      radius: 16,
                      backgroundColor: item.iconBg,
                      child: Icon(item.icon, size: 16, color: item.iconColor),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(item.title,
                              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500)),
                          const SizedBox(height: 2),
                          Text(item.subtitle,
                              style: const TextStyle(fontSize: 11.5, color: AppColors.textGrey)),
                        ],
                      ),
                    ),
                  ],
                ),
              )),
        ],
      ),
    );
  }
}

// ---------- ACTION BUTTONS GRID ----------
class _ActionButtonsGrid extends StatelessWidget {
  const _ActionButtonsGrid();

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _ActionButton(
                icon: Icons.chat_bubble_outline_rounded,
                label: 'Inquiries',
                bgColor: AppColors.primary,
                textColor: Colors.white,
                onTap: () => Navigator.pushNamed(context, '/chatbot'),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _ActionButton(
                icon: Icons.person_add_alt_1_outlined,
                label: 'Add Doctor',
                bgColor: const Color(0xFFE9EDF5),
                textColor: AppColors.textDark,
                onTap: () => Navigator.pushNamed(context, '/doctors'),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _ActionButton(
                icon: Icons.bar_chart_rounded,
                label: 'Reports',
                bgColor: AppColors.darkNavy,
                textColor: Colors.white,
                onTap: () {},
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _ActionButton(
                icon: Icons.groups_outlined,
                label: 'Staff',
                bgColor: AppColors.primary,
                textColor: Colors.white,
                onTap: () => Navigator.pushNamed(context, '/user_management'),
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _ActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color bgColor;
  final Color textColor;
  final VoidCallback? onTap;

  const _ActionButton({
    required this.icon,
    required this.label,
    required this.bgColor,
    required this.textColor,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: bgColor,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap ?? () {},
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 14),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 16, color: textColor),
              const SizedBox(width: 6),
              Text(label,
                  style: TextStyle(color: textColor, fontWeight: FontWeight.w600, fontSize: 13)),
            ],
          ),
        ),
      ),
    );
  }
}

// ---------- GENERIC CARD WRAPPER ----------
class _Card extends StatelessWidget {
  final Widget child;
  const _Card({required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 8,
              offset: const Offset(0, 2)),
        ],
      ),
      child: child,
    );
  }
}