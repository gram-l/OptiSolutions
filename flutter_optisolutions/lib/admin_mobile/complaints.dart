import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import '../main.dart' show appMenuItems;

import 'services/complaints_service.dart';

// Converts the DB enum ('pending' | 'in_progress' | 'resolved') into the
// label shown in the UI ('Pending' | 'In Progress' | 'Resolved').
String _statusLabel(String rawStatus) {
  switch (rawStatus.toLowerCase()) {
    case 'pending':
      return 'Pending';
    case 'in_progress':
      return 'In Progress';
    case 'resolved':
      return 'Resolved';
    default:
      return rawStatus;
  }
}

// ─────────────────────────────────────────────────────────────
//  SCREEN
// ─────────────────────────────────────────────────────────────
class ComplaintsScreen extends StatefulWidget {
  const ComplaintsScreen({super.key});

  @override
  State<ComplaintsScreen> createState() => _ComplaintsScreenState();
}

class _ComplaintsScreenState extends State<ComplaintsScreen> {
  final ComplaintService _service = ComplaintService();
  late Future<List<ComplaintItem>> _complaintsFuture;

  String _search = '';
  String _statusFilter = 'All';

  static const _statuses = ['All', 'Pending', 'In Progress', 'Resolved'];

  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  @override
  void initState() {
    super.initState();
    _complaintsFuture = _service.getComplaints();
  }

  Future<void> _refresh() async {
    setState(() {
      _complaintsFuture = _service.getComplaints();
    });
  }

  List<ComplaintItem> _applyFilters(List<ComplaintItem> data) {
    return data.where((c) {
      final q = _search.toLowerCase();
      final matchSearch = c.complaintText.toLowerCase().contains(q);
      final matchStatus = _statusFilter == 'All' ||
          _statusLabel(c.status).toLowerCase() == _statusFilter.toLowerCase();
      return matchSearch && matchStatus;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      backgroundColor: AppColors.background,
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/complaints',
        onItemTap: (route) => Navigator.pushNamed(context, route),
      ),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildHeader(),
            _buildFilterRow(),
            Expanded(
              child: FutureBuilder<List<ComplaintItem>>(
                future: _complaintsFuture,
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (snapshot.hasError) {
                    return Center(
                      child: Text('Failed to load complaints: ${snapshot.error}',
                          style: const TextStyle(color: AppColors.textGrey)),
                    );
                  }
                  final filtered = _applyFilters(snapshot.data ?? []);
                  if (filtered.isEmpty) {
                    return const Center(
                      child: Text('No complaints found.',
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
                          _ComplaintCard(complaint: filtered[i]),
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
                  Icons.report_problem_outlined,
                  size: 15,
                  color: AppColors.iconColor,
                ),
              ),
              const SizedBox(width: 10),
              const Text('Patient Complaints',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppColors.darkNavy,
                  )),
            ],
          ),
          const Padding(
            padding: EdgeInsets.only(left: 48, top: 4),
            child: Text('Reported issues & resolutions',
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
              hint: 'Search complaints...',
              onChanged: (v) => setState(() => _search = v),
            ),
          ),
          const SizedBox(width: 8),
          _DropdownChip(
            value: _statusFilter,
            items: _statuses,
            onChanged: (v) => setState(() => _statusFilter = v!),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  COMPLAINT CARD
// ─────────────────────────────────────────────────────────────
class _ComplaintCard extends StatelessWidget {
  final ComplaintItem complaint;
  const _ComplaintCard({required this.complaint});

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
                    'Patient #${complaint.patientId}',
                    style: const TextStyle(
                        fontSize: 14, fontWeight: FontWeight.bold),
                  ),
                ),
                Text(
                  complaint.date,
                  style: const TextStyle(
                      fontSize: 11.5, color: AppColors.textGrey),
                ),
              ],
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
                complaint.complaintText,
                style: const TextStyle(
                    fontSize: 12,
                    color: AppColors.textDark,
                    height: 1.45),
              ),
            ),
            const SizedBox(height: 8),
            _StatusBadge(status: complaint.status),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  STATUS BADGE
// ─────────────────────────────────────────────────────────────
class _StatusBadge extends StatelessWidget {
  final String status;
  const _StatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    final (label, icon, bg, fg) = switch (status.toLowerCase()) {
      'pending' => (
          'PENDING',
          '🔴',
          const Color(0xFFFFEBEE),
          AppColors.deleteRed,
        ),
      'in_progress' => (
          'IN PROGRESS',
          '🟠',
          const Color(0xFFFFF3E0),
          AppColors.deactivateOrange,
        ),
      'resolved' => (
          'RESOLVED',
          '🟢',
          const Color(0xFFE8F5E9),
          AppColors.activeGreen,
        ),
      _ => (
          status.toUpperCase(),
          '⏳',
          const Color(0xFFF0F0F0),
          AppColors.textGrey,
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
//  LOCAL REUSABLE WIDGETS (same pattern as feedback.dart)
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
          isDense: true,
          prefixIcon: const Icon(Icons.search,
              size: 16, color: AppColors.textGrey),
          prefixIconConstraints: const BoxConstraints(
            minWidth: 36,
            minHeight: 0,
          ),
          hintText: hint,
          hintStyle:
              const TextStyle(color: AppColors.textGrey, fontSize: 13),
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(vertical: 10, horizontal: 4),
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