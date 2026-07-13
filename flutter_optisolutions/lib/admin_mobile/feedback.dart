import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import '../main.dart' show appMenuItems;

import 'services/feedback_service.dart';

// ─────────────────────────────────────────────────────────────
//  SCREEN
// ─────────────────────────────────────────────────────────────
class FeedbackScreen extends StatefulWidget {
  const FeedbackScreen({super.key});

  @override
  State<FeedbackScreen> createState() => _FeedbackScreenState();
}

class _FeedbackScreenState extends State<FeedbackScreen> {
  final FeedbackService _service = FeedbackService();
  late Future<List<FeedbackItem>> _feedbackFuture;

  String _search = '';
  String _sentimentFilter = 'All';

  static const _sentiments = ['All', 'Positive', 'Neutral', 'Negative'];

  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  @override
  void initState() {
    super.initState();
    _feedbackFuture = _service.getFeedback();
  }

  Future<void> _refresh() async {
    setState(() {
      _feedbackFuture = _service.getFeedback();
    });
  }

  List<FeedbackItem> _applyFilters(List<FeedbackItem> data) {
    return data.where((f) {
      final q = _search.toLowerCase();
      final matchSearch = f.comment.toLowerCase().contains(q);
      final matchSentiment = _sentimentFilter == 'All' ||
          f.sentiment.toLowerCase() == _sentimentFilter.toLowerCase();
      return matchSearch && matchSentiment;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      backgroundColor: AppColors.background,
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/feedback',
        onItemTap: (route) => Navigator.pushNamed(context, route),
      ),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildHeader(),
            _buildFilterRow(),
            Expanded(
              child: FutureBuilder<List<FeedbackItem>>(
                future: _feedbackFuture,
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (snapshot.hasError) {
                    return Center(
                      child: Text('Failed to load feedback: ${snapshot.error}',
                          style: const TextStyle(color: AppColors.textGrey)),
                    );
                  }
                  final filtered = _applyFilters(snapshot.data ?? []);
                  if (filtered.isEmpty) {
                    return const Center(
                      child: Text('No feedback found.',
                          style: TextStyle(color: AppColors.textGrey)),
                    );
                  }
                  return RefreshIndicator(
                    onRefresh: _refresh,
                    child: ListView.builder(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 8),
                      itemCount: filtered.length,
                      itemBuilder: (_, i) =>
                          _FeedbackCard(feedback: filtered[i]),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 10, 16, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              IconButton(
                icon: const Icon(Icons.menu_rounded, color: AppColors.iconColor),
                onPressed: () => _scaffoldKey.currentState?.openDrawer(),
              ),
              Container(
                width: 30, height: 30,
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(
                  Icons.star_border_rounded,
                  size: 15,
                  color: AppColors.iconColor,
                ),
              ),
              const SizedBox(width: 10),
              const Text('Feedback',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppColors.darkNavy,
                  )),
            ],
          ),
          const Padding(
            padding: EdgeInsets.only(left: 48, top: 4),
            child: Text('Patient reviews & sentiment',
                style: TextStyle(color: AppColors.textGrey, fontSize: 13)),
          ),
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
              hint: 'Search comment...',
              onChanged: (v) => setState(() => _search = v),
            ),
          ),
          const SizedBox(width: 8),
          _DropdownChip(
            value: _sentimentFilter,
            items: _sentiments,
            onChanged: (v) => setState(() => _sentimentFilter = v!),
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
  final FeedbackItem feedback;
  const _FeedbackCard({required this.feedback});

  @override
  Widget build(BuildContext context) {
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
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    feedback.patientId != null
                        ? 'Patient #${feedback.patientId}'
                        : 'Anonymous',
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
            const SizedBox(height: 8),
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
  final String sentiment;
  const _SentimentBadge({required this.sentiment});

  @override
  Widget build(BuildContext context) {
    final (label, icon, bg, fg) = switch (sentiment.toLowerCase()) {
      'positive' => (
          'POSITIVE',
          '😊',
          const Color(0xFFE8F5E9),
          const Color(0xFF2E7D32),
        ),
      'neutral' => (
          'NEUTRAL',
          '😐',
          const Color(0xFFFFF8E1),
          const Color(0xFFF57F17),
        ),
      'negative' => (
          'NEGATIVE',
          '😟',
          const Color(0xFFFFEBEE),
          const Color(0xFFB71C1C),
        ),
      _ => (
          'PENDING',
          '⏳',
          const Color(0xFFF0F0F0),
          const Color(0xFF757575),
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
//  LOCAL REUSABLE WIDGETS (unchanged from your original)
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
        border: Border.all(color: AppColors.border),
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
        border: Border.all(color: AppColors.border),
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