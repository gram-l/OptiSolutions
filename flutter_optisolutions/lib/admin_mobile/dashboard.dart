// dashboard.dart
import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import '../main.dart' show appMenuItems;

// ---------- MAIN SCREEN ----------
class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
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
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const _TopBar(),
              const SizedBox(height: 16),
              const _WelcomeCard(),
              const SizedBox(height: 16),
              const _StatGrid(),
              const SizedBox(height: 16),
              const _InquiryVolumeCard(),
              const SizedBox(height: 16),
              const _SentimentCard(),
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
            child: Image.asset('assets/polyclinic_logo.png', fit: BoxFit.cover,
            ),
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
  const _StatGrid();

  @override
  Widget build(BuildContext context) {
    return Row(
      children: const [
        Expanded(
          child: _StatCard(
            label: 'TOTAL INQUIRIES',
            value: '342',
            trend: '↑ 12% from last week',
            trendUp: true,
          ),
        ),
        SizedBox(width: 12),
        Expanded(
          child: _StatCard(
            label: "TODAY'S APPTS",
            value: '24',
            trend: '6 pending approval',
          ),
        ),
      ],
    ).withBottomRow(const [
      _StatCard(
        label: 'ACTIVE PATIENTS',
        value: '1,284',
        trend: '18 new this month',
      ),
      _StatCard(
        label: 'SATISFACTION',
        value: '4.8',
        trend: 'Average rating',
        showStar: true,
      ),
    ]);
  }
}

extension on Row {
  Widget withBottomRow(List<Widget> bottomChildren) {
    return Column(
      children: [
        this,
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(child: bottomChildren[0]),
            const SizedBox(width: 12),
            Expanded(child: bottomChildren[1]),
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

// ---------- INQUIRY VOLUME (BAR CHART) ----------
class _InquiryVolumeCard extends StatelessWidget {
  const _InquiryVolumeCard();

  static const List<double> heights = [0.45, 0.5, 0.85, 0.4, 0.55, 0.95, 0.3];
  static const List<String> days = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
  static const int highlightIndex = 5;

  @override
  Widget build(BuildContext context) {
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
          const SizedBox(height: 18),
          SizedBox(
            height: 110,
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: List.generate(heights.length, (i) {
                final isHighlight = i == highlightIndex || i == 2;
                return Column(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    Container(
                      width: 26,
                      height: 90 * heights[i],
                      decoration: BoxDecoration(
                        color: isHighlight ? AppColors.darkNavy : AppColors.lightBlue,
                        borderRadius: BorderRadius.circular(6),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(days[i],
                        style: const TextStyle(fontSize: 11, color: AppColors.textGrey)),
                  ],
                );
              }),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------- SENTIMENT ANALYSIS ----------
class _SentimentCard extends StatelessWidget {
  const _SentimentCard();

  @override
  Widget build(BuildContext context) {
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
          const _SentimentRow(label: 'Positive', percent: 0.75, percentText: '75%', color: AppColors.positive),
          const SizedBox(height: 10),
          const _SentimentRow(label: 'Neutral',  percent: 0.18, percentText: '18%', color: AppColors.neutral),
          const SizedBox(height: 10),
          const _SentimentRow(label: 'Negative', percent: 0.07, percentText: '7%',  color: AppColors.negative),
          const SizedBox(height: 14),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: const Color(0xFFF6F8FC),
              borderRadius: BorderRadius.circular(10),
            ),
            child: RichText(
              text: const TextSpan(
                style: TextStyle(fontSize: 12, color: AppColors.textDark),
                children: [
                  WidgetSpan(
                    child: Icon(Icons.warning_amber_rounded, size: 14, color: Colors.orange),
                    alignment: PlaceholderAlignment.middle,
                  ),
                  TextSpan(
                      text: '  Top complaint: ',
                      style: TextStyle(fontWeight: FontWeight.w600)),
                  TextSpan(text: 'Long waiting times at reception'),
                ],
              ),
            ),
          ),
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
          child: Text(label,
              style: const TextStyle(fontSize: 12, color: AppColors.textGrey)),
        ),
        Expanded(
          child: ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: LinearProgressIndicator(
              value: percent,
              minHeight: 8,
              backgroundColor: const Color(0xFFEDEFF5),
              valueColor: AlwaysStoppedAnimation<Color>(color),
            ),
          ),
        ),
        const SizedBox(width: 8),
        SizedBox(
          width: 32,
          child: Text(percentText,
              textAlign: TextAlign.right,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
        ),
      ],
    );
  }
}

// ---------- RECENT ACTIVITY ----------
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
                              style: const TextStyle(
                                  fontSize: 13, fontWeight: FontWeight.w500)),
                          const SizedBox(height: 2),
                          Text(item.subtitle,
                              style: const TextStyle(
                                  fontSize: 11.5, color: AppColors.textGrey)),
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
                  style: TextStyle(
                      color: textColor,
                      fontWeight: FontWeight.w600,
                      fontSize: 13)),
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