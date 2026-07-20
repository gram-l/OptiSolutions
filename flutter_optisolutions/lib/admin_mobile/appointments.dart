// appointments.dart
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:csv/csv.dart';
import 'package:excel/excel.dart' as xls;
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import 'colors.dart';
import 'side_panel.dart';
import '../main.dart' show appMenuItems;
import 'services/visit_service.dart';

class AppointmentsScreen extends StatefulWidget {
  const AppointmentsScreen({super.key});

  @override
  State<AppointmentsScreen> createState() => _AppointmentsScreenState();
}

class _AppointmentsScreenState extends State<AppointmentsScreen> {
  String _searchQuery = '';
  String _selectedDept = 'All dept';

  DateTime _selectedDate = DateTime.now();
  List<Map<String, dynamic>> _visits = [];
  bool _loading = true;
  String? _error;

  static const _months = ['', 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  String get _dateLabel =>
      '${_months[_selectedDate.month]} ${_selectedDate.day}, ${_selectedDate.year}';

  @override
  void initState() {
    super.initState();
    _fetchVisits();
  }

  Future<void> _fetchVisits() async {
    setState(() { _loading = true; _error = null; });
    try {
      final visits = await VisitService.fetchDay(_selectedDate);
      setState(() { _visits = visits; _loading = false; });
    } catch (e) {
      setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  void _prevDay() {
    setState(() => _selectedDate = _selectedDate.subtract(const Duration(days: 1)));
    _fetchVisits();
  }

  void _nextDay() {
    setState(() => _selectedDate = _selectedDate.add(const Duration(days: 1)));
    _fetchVisits();
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
    );
    if (picked != null) {
      setState(() => _selectedDate = picked);
      _fetchVisits();
    }
  }

  List<Map<String, dynamic>> get _filtered {
    return _visits.where((v) {
      final matchesSearch = _searchQuery.isEmpty ||
          (v['patient_name'] ?? '').toString().toLowerCase().contains(_searchQuery.toLowerCase()) ||
          (v['doctor_name'] ?? '').toString().toLowerCase().contains(_searchQuery.toLowerCase()) ||
          (v['service_type'] ?? '').toString().toLowerCase().contains(_searchQuery.toLowerCase());
      final matchesDept = _selectedDept == 'All dept' || v['service_type'] == _selectedDept;
      return matchesSearch && matchesDept;
    }).toList();
  }

  void _navigateTo(String route) {
    if (route != '/appointments') Navigator.pushNamed(context, route);
  }

  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  // ── EXPORT ──────────────────────────────────────────────────
  void _showExportSheet() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(16))),
      builder: (context) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.table_chart_outlined, color: AppColors.primary),
              title: const Text('Export as CSV'),
              onTap: () { Navigator.pop(context); _exportCsv(); },
            ),
            ListTile(
              leading: const Icon(Icons.grid_on_rounded, color: AppColors.primary),
              title: const Text('Export as Excel'),
              onTap: () { Navigator.pop(context); _exportExcel(); },
            ),
            ListTile(
              leading: const Icon(Icons.picture_as_pdf_outlined, color: AppColors.primary),
              title: const Text('Export as PDF'),
              onTap: () { Navigator.pop(context); _exportPdf(); },
            ),
          ],
        ),
      ),
    );
  }

  List<List<String>> get _exportRows {
    final rows = <List<String>>[
      ['Date', 'Patient', 'Doctor', 'Service Type', 'Notes'],
    ];
    for (final v in _filtered) {
      rows.add([
        (v['visit_date'] ?? '').toString().split('T').first,
        v['patient_name']?.toString().trim().isNotEmpty == true ? v['patient_name'] : 'Unassigned',
        v['doctor_name']?.toString() ?? 'Unassigned',
        v['service_type']?.toString() ?? '',
        v['notes']?.toString() ?? '',
      ]);
    }
    return rows;
  }

  Future<File> _writeToDownloads(String filename, List<int> bytes) async {
    final dir = await getApplicationDocumentsDirectory();
    final file = File('${dir.path}/$filename');
    await file.writeAsBytes(bytes);
    return file;
  }

  String get _exportFileSafeDate =>
      '${_selectedDate.year}-${_selectedDate.month.toString().padLeft(2, '0')}-${_selectedDate.day.toString().padLeft(2, '0')}';

  Future<void> _exportCsv() async {
    final csv = const ListToCsvConverter().convert(_exportRows);
    final file = await _writeToDownloads('appointments_$_exportFileSafeDate.csv', csv.codeUnits);
    await Share.shareXFiles([XFile(file.path)], text: 'Appointments export ($_dateLabel)');
  }

  Future<void> _exportExcel() async {
    final workbook = xls.Excel.createExcel();
    final sheet = workbook['Appointments'];
    for (final row in _exportRows) {
      sheet.appendRow(row.map((c) => xls.TextCellValue(c)).toList());
    }
    final bytes = workbook.encode();
    if (bytes == null) return;
    final file = await _writeToDownloads('appointments_$_exportFileSafeDate.xlsx', bytes);
    await Share.shareXFiles([XFile(file.path)], text: 'Appointments export ($_dateLabel)');
  }

  Future<void> _exportPdf() async {
    final doc = pw.Document();
    doc.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.a4.landscape,
        build: (context) => pw.Table.fromTextArray(
          headers: _exportRows.first,
          data: _exportRows.skip(1).toList(),
          cellStyle: const pw.TextStyle(fontSize: 9),
          headerStyle: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold),
        ),
      ),
    );
    final bytes = await doc.save();
    final file = await _writeToDownloads('appointments_$_exportFileSafeDate.pdf', bytes);
    await Share.shareXFiles([XFile(file.path)], text: 'Appointments export ($_dateLabel)');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      backgroundColor: AppColors.background,
      drawer: SidePanel(items: appMenuItems, currentRoute: '/appointments', onItemTap: _navigateTo),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.menu_rounded, color: AppColors.iconColor),
                    onPressed: () => _scaffoldKey.currentState?.openDrawer(),
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(),
                  ),
                  const SizedBox(width: 10),
                  Container(
                    width: 30, height: 30,
                    decoration: BoxDecoration(
                      color: AppColors.primary.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.event_note_rounded, size: 15, color: AppColors.iconColor),
                  ),
                  const SizedBox(width: 10),
                  const Expanded(
                    child: Text('Scheduled Visits',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.darkNavy)),
                  ),
                  IconButton(
                    icon: const Icon(Icons.ios_share_rounded, color: AppColors.primary),
                    onPressed: _filtered.isEmpty ? null : _showExportSheet,
                  ),
                ],
              ),
            ),
            Expanded(
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
                          Text(
                            _error!,
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: AppColors.deleteRed),
                          ),
                          const SizedBox(height: 12),
                          ElevatedButton(onPressed: _fetchVisits, child: const Text('Retry')),
                        ]))
                      : RefreshIndicator(
                          onRefresh: _fetchVisits,
                          child: SingleChildScrollView(
                            physics: const AlwaysScrollableScrollPhysics(),
                            padding: const EdgeInsets.symmetric(horizontal: 16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SizedBox(height: 16),
                                Row(
                                  children: [
                                    Expanded(
                                      flex: 3,
                                      child: SizedBox(
                                        height: 40,
                                        child: TextField(
                                          onChanged: (v) => setState(() => _searchQuery = v),
                                          style: const TextStyle(fontSize: 13),
                                          decoration: InputDecoration(
                                            hintText: 'Search..',
                                            hintStyle: const TextStyle(color: AppColors.textGrey, fontSize: 13),
                                            prefixIcon: const Icon(Icons.search, size: 18, color: AppColors.textGrey),
                                            filled: true,
                                            fillColor: Colors.white,
                                            contentPadding: EdgeInsets.zero,
                                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.border)),
                                            enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.border)),
                                            focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.primary)),
                                          ),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 16),

                                // ── Day nav + date picker ──
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.end,
                                  children: [
                                    _NavButton(icon: Icons.chevron_left, onTap: _prevDay),
                                    const SizedBox(width: 6),
                                    GestureDetector(
                                      onTap: _pickDate,
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                                        decoration: BoxDecoration(
                                          color: AppColors.primary,
                                          borderRadius: BorderRadius.circular(20),
                                        ),
                                        child: Row(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            Text(_dateLabel,
                                                style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w500)),
                                            const SizedBox(width: 4),
                                            const Icon(Icons.calendar_month_rounded, size: 14, color: Colors.white),
                                          ],
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 6),
                                    _NavButton(icon: Icons.chevron_right, onTap: _nextDay),
                                  ],
                                ),
                                const SizedBox(height: 24),

                                if (_filtered.isEmpty)
                                  const _EmptyState()
                                else
                                  ..._filtered.map((v) => _VisitCard(visit: v)),

                                const SizedBox(height: 24),
                              ],
                            ),
                          ),
                        ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Visit card (single visit row) ──
class _VisitCard extends StatelessWidget {
  final Map<String, dynamic> visit;
  const _VisitCard({required this.visit});

  @override
  Widget build(BuildContext context) {
    final patientName = visit['patient_name']?.toString().trim();
    final hasPatient = patientName != null && patientName.isNotEmpty && patientName != 'null null';

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(hasPatient ? patientName : 'Unassigned patient',
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 2),
                Text('${visit['service_type'] ?? ''} · ${visit['doctor_name'] ?? 'Unassigned doctor'}',
                    style: const TextStyle(fontSize: 12, color: AppColors.textGrey)),
                if ((visit['notes'] ?? '').toString().isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(visit['notes'].toString(),
                      style: const TextStyle(fontSize: 11.5, color: AppColors.textGrey, fontStyle: FontStyle.italic)),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _NavButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;
  const _NavButton({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 28, height: 28,
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(6), border: Border.all(color: AppColors.border)),
        child: Icon(icon, size: 18, color: AppColors.iconColor),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Padding(
        padding: EdgeInsets.symmetric(vertical: 48),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('📬', style: TextStyle(fontSize: 52)),
            SizedBox(height: 16),
            Text('No appointments this day', style: TextStyle(fontSize: 15, color: AppColors.textGrey, fontWeight: FontWeight.w500)),
          ],
        ),
      ),
    );
  }
}