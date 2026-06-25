import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import 'main.dart' show appMenuItems;

// ─────────────────────────────────────────────────────────────
//  DATA MODEL
// ─────────────────────────────────────────────────────────────
enum Sentiment { positive, neutral, negative }

class FeedbackModel {
  final String patientName;
  final String specialty;
  final String doctorName;
  final String date;
  final int rating; // 1–5
  final String comment;
  final Sentiment sentiment;

  const FeedbackModel({
    required this.patientName,
    required this.specialty,
    required this.doctorName,
    required this.date,
    required this.rating,
    required this.comment,
    required this.sentiment,
  });
}

const _feedbackList = <FeedbackModel>[
  FeedbackModel(
    patientName: 'Maria Santos',
    specialty: 'Ophthalmology',
    doctorName: 'Dr. Maria Reyes',
    date: 'May 20',
    rating: 5,
    comment:
        'Excellent service! Dr. Reyes was very thorough and explained everything clearly. The staff was friendly and the wait time was minimal.',
    sentiment: Sentiment.positive,
  ),
  FeedbackModel(
    patientName: 'John Dela Cruz',
    specialty: 'Pediatrics',
    doctorName: 'Dr. Jose Mendoza',
    date: 'May 19',
    rating: 4,
    comment:
        'Good experience overall. The doctor was great with my son. Only downside was the waiting area was crowded.',
    sentiment: Sentiment.positive,
  ),
  FeedbackModel(
    patientName: 'Anna Rivera',
    specialty: 'ENT',
    doctorName: 'Dr. Anna Garcia',
    date: 'May 18',
    rating: 2,
    comment:
        'Long waiting time at reception, waited over an hour just to be seen. The doctor was rushed and didn\'t fully address my concerns.',
    sentiment: Sentiment.negative,
  ),
  FeedbackModel(
    patientName: 'Carlos Gomez',
    specialty: 'Cardiology',
    doctorName: 'Dr. Carlos Santos',
    date: 'May 17',
    rating: 5,
    comment:
        'Dr. Santos is amazing! Very professional and caring. The clinic is well-organized and clean.',
    sentiment: Sentiment.positive,
  ),
  FeedbackModel(
    patientName: 'Elena Garcia',
    specialty: 'Dermatology',
    doctorName: 'Dr. Elena Lopez',
    date: 'May 16',
    rating: 3,
    comment:
        'The treatment was effective but scheduling was difficult. Had to wait 3 weeks for an appointment.',
    sentiment: Sentiment.neutral,
  ),
];

const _specialtyColors = <String, Color>{
  'Ophthalmology': Color(0xFF1565C0),
  'Pediatrics': Color(0xFF2E7D32),
  'ENT': Color(0xFF00838F),
  'Cardiology': Color(0xFFB71C1C),
  'Dermatology': Color(0xFF6A1B9A),
};

// ─────────────────────────────────────────────────────────────
//  SCREEN
// ─────────────────────────────────────────────────────────────
class FeedbackScreen extends StatefulWidget {
  const FeedbackScreen({super.key});

  @override
  State<FeedbackScreen> createState() => _FeedbackScreenState();
}

class _FeedbackScreenState extends State<FeedbackScreen> {
  String _search = '';
  String _sentimentFilter = 'All';
  String _deptFilter = 'All dept';

  static const _sentiments = ['All', 'Positive', 'Neutral', 'Negative'];
  static const _depts = [
    'All dept',
    'Ophthalmology',
    'Pediatrics',
    'ENT',
    'Cardiology',
    'Dermatology',
  ];

  List<FeedbackModel> get _filtered => _feedbackList.where((f) {
        final q = _search.toLowerCase();
        final matchSearch = f.patientName.toLowerCase().contains(q) ||
            f.comment.toLowerCase().contains(q);
        final matchSentiment = _sentimentFilter == 'All' ||
            f.sentiment.name.toLowerCase() ==
                _sentimentFilter.toLowerCase();
        final matchDept =
            _deptFilter == 'All dept' || f.specialty == _deptFilter;
        return matchSearch && matchSentiment && matchDept;
      }).toList();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F6FB),
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/feedback',
        onItemTap: (route) => Navigator.pushNamed(context, route),
      ),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildTopBar(),
            _buildHeader(),
            _buildFilterRow(),
            Expanded(
              child: _filtered.isEmpty
                  ? const Center(
                      child: Text('No feedback found.',
                          style: TextStyle(color: AppColors.textGrey)),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 8),
                      itemCount: _filtered.length,
                      itemBuilder: (_, i) =>
                          _FeedbackCard(feedback: _filtered[i]),
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTopBar() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      child: Row(
        children: [
          Builder(
            builder: (ctx) => IconButton(
              icon: const Icon(Icons.menu, color: AppColors.textDark),
              onPressed: () => Scaffold.of(ctx).openDrawer(),
            ),
          ),
          const CircleAvatar(
            radius: 13,
            backgroundColor: Color(0xFFE3F2FD),
            child: Icon(Icons.local_hospital,
                color: AppColors.primary, size: 14),
          ),
          const SizedBox(width: 6),
          const Text('Polyclinic',
              style:
                  TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          const Spacer(),
          IconButton(
            icon: const Icon(Icons.notifications_none_rounded, color: AppColors.textDark, size: 22),
            onPressed: () => Navigator.pushNamed(context, '/notifications'),
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
          ),
          const SizedBox(width: 8),
          const Icon(Icons.logout_outlined,
              color: AppColors.textGrey, size: 20),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          GestureDetector(
            onTap: () => Navigator.maybePop(context),
            child: Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.arrow_back_ios_new_rounded,
                      size: 12, color: Colors.white),
                  SizedBox(width: 4),
                  Text('Dashboard',
                      style:
                          TextStyle(color: Colors.white, fontSize: 12)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 10),
          const Row(
            children: [
              Icon(Icons.star_border_rounded,
                  color: AppColors.primary, size: 22),
              SizedBox(width: 8),
              Text('Feedback',
                  style: TextStyle(
                      fontSize: 20, fontWeight: FontWeight.bold)),
            ],
          ),
          const SizedBox(height: 2),
          const Text('Patient reviews & sentiment',
              style: TextStyle(
                  color: AppColors.textGrey, fontSize: 12.5)),
        ],
      ),
    );
  }

  Widget _buildFilterRow() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child: Row(
        children: [
          Expanded(
            child: _SearchField(
              hint: 'Search patient or comment...',
              onChanged: (v) => setState(() => _search = v),
            ),
          ),
          const SizedBox(width: 8),
          _DropdownChip(
            value: _sentimentFilter,
            items: _sentiments,
            onChanged: (v) => setState(() => _sentimentFilter = v!),
          ),
          const SizedBox(width: 6),
          _DropdownChip(
            value: _deptFilter,
            items: _depts,
            onChanged: (v) => setState(() => _deptFilter = v!),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  FEEDBACK CARD
// ─────────────────────────────────────────────────────────────
class _FeedbackCard extends StatelessWidget {
  final FeedbackModel feedback;
  const _FeedbackCard({required this.feedback});

  @override
  Widget build(BuildContext context) {
    final accentColor =
        _specialtyColors[feedback.specialty] ?? AppColors.primary;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Header row ──
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    feedback.patientName,
                    style: const TextStyle(
                        fontSize: 14, fontWeight: FontWeight.bold),
                  ),
                ),
                Text(
                  feedback.date,
                  style: const TextStyle(
                      fontSize: 11.5, color: AppColors.textGrey),
                ),
              ],
            ),
            const SizedBox(height: 4),
            // ── Specialty + Doctor ──
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 7, vertical: 2),
                  decoration: BoxDecoration(
                    color: accentColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    feedback.specialty,
                    style: TextStyle(
                        fontSize: 10.5,
                        color: accentColor,
                        fontWeight: FontWeight.w600),
                  ),
                ),
                const SizedBox(width: 8),
                const Icon(Icons.person_outline_rounded,
                    size: 12, color: AppColors.textGrey),
                const SizedBox(width: 3),
                Text(
                  feedback.doctorName,
                  style: const TextStyle(
                      fontSize: 11.5, color: AppColors.textGrey),
                ),
              ],
            ),
            const SizedBox(height: 8),
            // ── Star rating ──
            Row(
              children: List.generate(5, (i) {
                return Icon(
                  i < feedback.rating
                      ? Icons.star_rounded
                      : Icons.star_outline_rounded,
                  size: 16,
                  color: i < feedback.rating
                      ? Colors.amber
                      : const Color(0xFFDDDDDD),
                );
              }),
            ),
            const SizedBox(height: 8),
            // ── Comment ──
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFF4F6FB),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                feedback.comment,
                style: const TextStyle(
                    fontSize: 12,
                    color: AppColors.textDark,
                    height: 1.45),
              ),
            ),
            const SizedBox(height: 8),
            // ── Sentiment badge ──
            _SentimentBadge(sentiment: feedback.sentiment),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  SENTIMENT BADGE
// ─────────────────────────────────────────────────────────────
class _SentimentBadge extends StatelessWidget {
  final Sentiment sentiment;
  const _SentimentBadge({required this.sentiment});

  @override
  Widget build(BuildContext context) {
    final (label, icon, bg, fg) = switch (sentiment) {
      Sentiment.positive => (
          'POSITIVE',
          '😊',
          const Color(0xFFE8F5E9),
          const Color(0xFF2E7D32),
        ),
      Sentiment.neutral => (
          'NEUTRAL',
          '😐',
          const Color(0xFFFFF8E1),
          const Color(0xFFF57F17),
        ),
      Sentiment.negative => (
          'NEGATIVE',
          '😟',
          const Color(0xFFFFEBEE),
          const Color(0xFFB71C1C),
        ),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(icon, style: const TextStyle(fontSize: 12)),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.bold,
                color: fg,
                letterSpacing: 0.4),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  LOCAL REUSABLE WIDGETS
// ─────────────────────────────────────────────────────────────
class _SearchField extends StatelessWidget {
  final String hint;
  final ValueChanged<String> onChanged;
  const _SearchField({required this.hint, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 38,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFE0E0E0)),
      ),
      child: TextField(
        onChanged: onChanged,
        style: const TextStyle(fontSize: 13),
        decoration: InputDecoration(
          prefixIcon: const Icon(Icons.search,
              size: 16, color: AppColors.textGrey),
          hintText: hint,
          hintStyle:
              const TextStyle(color: AppColors.textGrey, fontSize: 13),
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(vertical: 10),
        ),
      ),
    );
  }
}

class _DropdownChip extends StatelessWidget {
  final String value;
  final List<String> items;
  final ValueChanged<String?> onChanged;
  const _DropdownChip(
      {required this.value,
      required this.items,
      required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 38,
      padding: const EdgeInsets.symmetric(horizontal: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFE0E0E0)),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: value,
          items: items
              .map((e) => DropdownMenuItem(
                  value: e,
                  child: Text(e,
                      style: const TextStyle(fontSize: 12))))
              .toList(),
          onChanged: onChanged,
          style: const TextStyle(
              fontSize: 12, color: AppColors.textDark),
          icon: const Icon(Icons.keyboard_arrow_down_rounded,
              size: 16, color: AppColors.textGrey),
        ),
      ),
    );
  }
}