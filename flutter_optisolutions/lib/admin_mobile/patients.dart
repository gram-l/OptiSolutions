import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import '../main.dart' show appMenuItems;
import 'services/patient_service.dart';
import 'services/doctor_service.dart';
import 'center_snackbar.dart';
import 'dart:io';
import 'package:csv/csv.dart';
import 'package:excel/excel.dart' as xls;
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

// ─────────────────────────────────────────────────────────────
//  DATA MODEL — matches the real `patients` table exactly
// ─────────────────────────────────────────────────────────────
class PatientModel {
  final int id;
  final String fname;
  final String lname;
  final DateTime? birthdate;
  final String? email;
  final String? contact;

  const PatientModel({
    required this.id,
    required this.fname,
    required this.lname,
    this.birthdate,
    this.email,
    this.contact,
  });

  factory PatientModel.fromJson(Map<String, dynamic> json) {
    return PatientModel(
      id: json['patient_id'],
      fname: json['patient_fname'] ?? '',
      lname: json['patient_lname'] ?? '',
      birthdate: json['patient_birthdate'] != null
          ? DateTime.tryParse(json['patient_birthdate'])
          : null,
      email: json['patient_email'],
      contact: json['patient_contact'],
    );
  }

  String get fullName => '$fname $lname'.trim();

  int? get age {
    if (birthdate == null) return null;
    final now = DateTime.now();
    int years = now.year - birthdate!.year;
    if (now.month < birthdate!.month ||
        (now.month == birthdate!.month && now.day < birthdate!.day)) {
      years--;
    }
    return years;
  }
}

// Minimal doctor shape just for the filter dropdown — deliberately not the
// full DoctorModel from doctors.dart so this screen doesn't need to import
// that whole file just to read an id/name pair.
class _DoctorOption {
  final int id;
  final String name;
  const _DoctorOption({required this.id, required this.name});
}

// ─────────────────────────────────────────────────────────────
//  SCREEN
// ─────────────────────────────────────────────────────────────
class PatientRecordsScreen extends StatefulWidget {
  const PatientRecordsScreen({super.key});

  @override
  State<PatientRecordsScreen> createState() => _PatientRecordsScreenState();
}

class _PatientRecordsScreenState extends State<PatientRecordsScreen> {
  String _search = '';
  List<PatientModel> _patients = [];
  bool _loading = true;
  String? _error;

  // Doctor / service filters — both are applied server-side (via
  // schedule_visit), the local _search box still filters client-side
  // on top of whatever the server already returned.
  List<_DoctorOption> _doctorOptions = [];
  List<String> _serviceTypeOptions = [];
  int? _selectedDoctorId;
  String? _selectedServiceType;

  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  bool get _filtersActive => _selectedDoctorId != null || _selectedServiceType != null;

  @override
  void initState() {
    super.initState();
    _fetchPatients();
    _loadFilterOptions();
  }

  Future<void> _fetchPatients() async {
    setState(() { _loading = true; _error = null; });
    try {
      final data = await PatientService.fetchAll(
        doctorId: _selectedDoctorId,
        serviceType: _selectedServiceType,
      );
      setState(() {
        _patients = data.map((j) => PatientModel.fromJson(j)).toList();
        _loading = false;
      });
    } catch (e) {
      setState(() { _error = e.toString().replaceFirst('Exception: ', ''); _loading = false; });
    }
  }

  Future<void> _loadFilterOptions() async {
    try {
      final doctorsResult = await DoctorService.fetchDoctors();
      if (doctorsResult['success'] == true) {
        final List<dynamic> raw = doctorsResult['doctors'] ?? [];
        final options = raw.map((d) {
          final rawId = d['doctor_id'];
          return _DoctorOption(
            id: rawId is int ? rawId : int.tryParse(rawId.toString()) ?? 0,
            name: d['doctor_name'] ?? '',
          );
        }).toList();
        if (mounted) setState(() => _doctorOptions = options);
      }
    } catch (_) {
      // Filter dropdown just stays empty — not worth blocking the screen.
    }

    try {
      final types = await PatientService.fetchServiceTypes();
      if (mounted) setState(() => _serviceTypeOptions = types);
    } catch (_) {
      // Same here.
    }
  }

  void _onDoctorFilterChanged(int? doctorId) {
    setState(() => _selectedDoctorId = doctorId);
    _fetchPatients();
  }

  void _onServiceFilterChanged(String? serviceType) {
    setState(() => _selectedServiceType = serviceType);
    _fetchPatients();
  }

  void _clearFilters() {
    setState(() {
      _selectedDoctorId = null;
      _selectedServiceType = null;
    });
    _fetchPatients();
  }

  List<PatientModel> get _filtered => _patients.where((p) {
        final q = _search.toLowerCase();
        return p.fullName.toLowerCase().contains(q) ||
            (p.email ?? '').toLowerCase().contains(q) ||
            (p.contact ?? '').toLowerCase().contains(q);
      }).toList();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      backgroundColor: AppColors.background,
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/patients',
        onItemTap: (route) => Navigator.pushNamed(context, route),
      ),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildHeader(),
            _buildFilterRow(),
            _buildFilterChipsRow(),
            Expanded(
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? Center(
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(_error!, textAlign: TextAlign.center),
                              const SizedBox(height: 12),
                              ElevatedButton(onPressed: _fetchPatients, child: const Text('Retry')),
                            ],
                          ),
                        )
                      : _filtered.isEmpty
                          ? _EmptyState(filtersActive: _filtersActive, onClearFilters: _clearFilters)
                          : RefreshIndicator(
                              onRefresh: _fetchPatients,
                              child: ListView.builder(
                                padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
                                itemCount: _filtered.length,
                                itemBuilder: (_, i) => _PatientCard(
                                  patient: _filtered[i],
                                  onEdited: _fetchPatients,
                                ),
                              ),
                            ),
            ),
          ],
        ),
      ),
    );
  }

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
    ['Patient ID', 'Name', 'Age', 'Email', 'Contact'],
  ];
  for (final p in _filtered) {
    rows.add([
      'P-${p.id}',
      p.fullName,
      p.age?.toString() ?? '',
      p.email ?? '',
      p.contact ?? '',
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

Future<void> _exportCsv() async {
  try {
    final csv = const ListToCsvConverter().convert(_exportRows);
    final file = await _writeToDownloads('patients.csv', csv.codeUnits);
    await Share.shareXFiles([XFile(file.path)], text: 'Patient records export');
    if (!mounted) return;
    showCenterSnackBar(context, 'CSV exported successfully.');
  } catch (e) {
    if (!mounted) return;
    showCenterSnackBar(context, 'CSV export failed. Please try again.', isError: true);
  }
}

Future<void> _exportExcel() async {
  try {
    final workbook = xls.Excel.createExcel();
    final sheet = workbook['Patients'];
    for (final row in _exportRows) {
      sheet.appendRow(row.map((c) => xls.TextCellValue(c)).toList());
    }
    final bytes = workbook.encode();
    if (bytes == null) {
      if (!mounted) return;
      showCenterSnackBar(context, 'Excel export failed. Please try again.', isError: true);
      return;
    }
    final file = await _writeToDownloads('patients.xlsx', bytes);
    await Share.shareXFiles([XFile(file.path)], text: 'Patient records export');
    if (!mounted) return;
    showCenterSnackBar(context, 'Excel file exported successfully.');
  } catch (e) {
    if (!mounted) return;
    showCenterSnackBar(context, 'Excel export failed. Please try again.', isError: true);
  }
}

Future<void> _exportPdf() async {
  try {
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
    final file = await _writeToDownloads('patients.pdf', bytes);
    await Share.shareXFiles([XFile(file.path)], text: 'Patient records export');
    if (!mounted) return;
    showCenterSnackBar(context, 'PDF exported successfully.');
  } catch (e) {
    if (!mounted) return;
    showCenterSnackBar(context, 'PDF export failed. Please try again.', isError: true);
  }
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
                child: const Icon(Icons.folder_shared_outlined, size: 15, color: AppColors.iconColor),
              ),
              const SizedBox(width: 10),
              const Text('Patients',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.darkNavy)),
            ],
          ),
          const Padding(
            padding: EdgeInsets.only(left: 48, top: 4),
            child: Text('Manage patient records',
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
          child: _SearchField(onChanged: (v) => setState(() => _search = v)),
        ),
        const SizedBox(width: 8),
        IconButton(
          icon: const Icon(Icons.ios_share_rounded, color: AppColors.primary),
          onPressed: _filtered.isEmpty ? null : _showExportSheet,
        ),
        _AddButton(onTap: () => _showAddDialog(context)),
      ],
    ),
  );
}

  // ── Doctor / service filter row ──
  // Both dropdowns filter server-side through schedule_visit; selecting
  // either one re-fetches the patient list with that filter applied.
  Widget _buildFilterChipsRow() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child: Row(
        children: [
          Expanded(
            child: _FilterDropdown(
              icon: Icons.medical_services_outlined,
              value: _selectedDoctorId == null
                  ? 'All Doctors'
                  : _doctorOptions
                      .firstWhere(
                        (d) => d.id == _selectedDoctorId,
                        orElse: () => const _DoctorOption(id: -1, name: 'All Doctors'),
                      )
                      .name,
              items: ['All Doctors', ..._doctorOptions.map((d) => d.name)],
              onChanged: (label) {
                if (label == 'All Doctors') {
                  _onDoctorFilterChanged(null);
                  return;
                }
                final match = _doctorOptions.firstWhere(
                  (d) => d.name == label,
                  orElse: () => const _DoctorOption(id: -1, name: ''),
                );
                if (match.id != -1) _onDoctorFilterChanged(match.id);
              },
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: _FilterDropdown(
              icon: Icons.event_note_outlined,
              value: _selectedServiceType ?? 'All Services',
              items: ['All Services', ..._serviceTypeOptions],
              onChanged: (label) {
                _onServiceFilterChanged(label == 'All Services' ? null : label);
              },
            ),
          ),
          if (_filtersActive) ...[
            const SizedBox(width: 4),
            IconButton(
              icon: const Icon(Icons.filter_alt_off_outlined, size: 18, color: AppColors.textGrey),
              tooltip: 'Clear filters',
              onPressed: _clearFilters,
            ),
          ],
        ],
      ),
    );
  }

  void _showAddDialog(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => _AddEditPatientSheet(onSaved: _fetchPatients),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  PATIENT CARD
// ─────────────────────────────────────────────────────────────
class _PatientCard extends StatelessWidget {
  final PatientModel patient;
  final VoidCallback onEdited;
  const _PatientCard({required this.patient, required this.onEdited});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2)),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(patient.fullName.isNotEmpty ? patient.fullName : 'Unnamed patient',
                style: const TextStyle(fontSize: 15.5, fontWeight: FontWeight.bold)),
            const SizedBox(height: 6),
            _IdChip(id: 'P-${patient.id}'),
            const SizedBox(height: 8),
            Row(
              children: [
                if (patient.age != null) ...[
                  const Icon(Icons.calendar_today_outlined, size: 12, color: AppColors.textGrey),
                  const SizedBox(width: 3),
                  Text('${patient.age} yrs', style: const TextStyle(fontSize: 11.5, color: AppColors.textGrey)),
                  const SizedBox(width: 10),
                ],
                if (patient.contact != null && patient.contact!.isNotEmpty) ...[
                  const Icon(Icons.phone_outlined, size: 12, color: AppColors.textGrey),
                  const SizedBox(width: 3),
                  Text(patient.contact!, style: const TextStyle(fontSize: 11.5, color: AppColors.textGrey)),
                ],
              ],
            ),
            if (patient.email != null && patient.email!.isNotEmpty) ...[
              const SizedBox(height: 4),
              Row(
                children: [
                  const Icon(Icons.email_outlined, size: 12, color: AppColors.textGrey),
                  const SizedBox(width: 3),
                  Expanded(
                    child: Text(patient.email!,
                        style: const TextStyle(fontSize: 11.5, color: AppColors.textGrey),
                        overflow: TextOverflow.ellipsis),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 10),
            Row(
              children: [
                _CardButton(
                  label: 'View',
                  icon: Icons.visibility_outlined,
                  color: AppColors.viewTeal,
                  onTap: () => _showViewSheet(context, patient),
                ),
                const SizedBox(width: 8),
                _CardButton(
                  label: 'Edit',
                  icon: Icons.edit_outlined,
                  color: AppColors.editBlue,
                  onTap: () => showModalBottomSheet(
                    context: context,
                    isScrollControlled: true,
                    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
                    builder: (_) => _AddEditPatientSheet(patient: patient, onSaved: onEdited),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _showViewSheet(BuildContext context, PatientModel p) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => _ViewPatientSheet(patient: p),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  VIEW PATIENT BOTTOM SHEET
// ─────────────────────────────────────────────────────────────
class _ViewPatientSheet extends StatefulWidget {
  final PatientModel patient;
  const _ViewPatientSheet({required this.patient});

  @override
  State<_ViewPatientSheet> createState() => _ViewPatientSheetState();
}

class _ViewPatientSheetState extends State<_ViewPatientSheet> {
  List<Map<String, dynamic>> _visits = [];
  bool _loadingVisits = true;
  String? _visitsError;

  @override
  void initState() {
    super.initState();
    _loadVisits();
  }

  Future<void> _loadVisits() async {
    setState(() { _loadingVisits = true; _visitsError = null; });
    try {
      final visits = await PatientService.fetchVisits(widget.patient.id);
      if (!mounted) return;
      setState(() { _visits = visits; _loadingVisits = false; });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _visitsError = e.toString().replaceFirst('Exception: ', '');
        _loadingVisits = false;
      });
    }
  }

  String _formatDate(String? raw) {
    if (raw == null) return '';
    final d = DateTime.tryParse(raw);
    if (d == null) return raw;
    return '${d.month}/${d.day}/${d.year}';
  }

  @override
  Widget build(BuildContext context) {
    final patient = widget.patient;
    return DraggableScrollableSheet(
      expand: false,
      initialChildSize: 0.6,
      maxChildSize: 0.9,
      builder: (_, ctrl) => Padding(
        padding: const EdgeInsets.all(20),
        child: ListView(
          controller: ctrl,
          children: [
            Center(
              child: Container(
                width: 40, height: 4,
                decoration: BoxDecoration(color: const Color(0xFFDDE1E8), borderRadius: BorderRadius.circular(2)),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                CircleAvatar(
                  radius: 24,
                  backgroundColor: AppColors.primary.withValues(alpha: 0.15),
                  child: Text(
                    patient.fname.isNotEmpty ? patient.fname.substring(0, 1) : '?',
                    style: const TextStyle(color: AppColors.primary, fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                ),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(patient.fullName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    Text('P-${patient.id}', style: const TextStyle(color: AppColors.textGrey, fontSize: 12)),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 18),
            if (patient.age != null)
              _DetailRow(icon: Icons.calendar_today_outlined, label: 'Age', value: '${patient.age} years old'),
            if (patient.birthdate != null)
              _DetailRow(
                icon: Icons.cake_outlined,
                label: 'Birthdate',
                value: '${patient.birthdate!.month}/${patient.birthdate!.day}/${patient.birthdate!.year}',
              ),
            if (patient.contact != null && patient.contact!.isNotEmpty)
              _DetailRow(icon: Icons.phone_outlined, label: 'Contact', value: patient.contact!),
            if (patient.email != null && patient.email!.isNotEmpty)
              _DetailRow(icon: Icons.email_outlined, label: 'Email', value: patient.email!),

            const SizedBox(height: 8),
            const Divider(height: 24),
            Row(
              children: const [
                Icon(Icons.event_note_outlined, size: 16, color: AppColors.primary),
                SizedBox(width: 6),
                Text('Visit / Service History',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              ],
            ),
            const SizedBox(height: 10),

            if (_loadingVisits)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 20),
                child: Center(child: CircularProgressIndicator()),
              )
            else if (_visitsError != null)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Column(
                  children: [
                    Text(_visitsError!,
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: AppColors.textGrey, fontSize: 12.5)),
                    const SizedBox(height: 8),
                    TextButton(
                      onPressed: _loadVisits,
                      child: const Text('Retry', style: TextStyle(color: AppColors.primary)),
                    ),
                  ],
                ),
              )
            else if (_visits.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 12),
                child: Text('No visit history yet.',
                    style: TextStyle(color: AppColors.textGrey, fontSize: 12.5)),
              )
            else
              ..._visits.map((v) => _VisitTile(
                    visitId: v['visit_id'] is int
                        ? v['visit_id'] as int
                        : int.tryParse('${v['visit_id']}'),
                    serviceType: (v['service_type'] as String?) ?? 'Not specified',
                    doctorName: v['doctor_name'] as String?,
                    visitDate: _formatDate(v['visit_date'] as String?),
                    notes: v['notes'] as String?,
                  )),
          ],
        ),
      ),
    );
  }
}

// One row in the Visit / Service History list — this is where the
// service type (from schedule_visit, via the /visits endpoint) is
// actually surfaced on the View sheet. Notes are editable in place.
class _VisitTile extends StatefulWidget {
  final int? visitId;
  final String serviceType;
  final String? doctorName;
  final String visitDate;
  final String? notes;
  const _VisitTile({
    required this.visitId,
    required this.serviceType,
    this.doctorName,
    required this.visitDate,
    this.notes,
  });

  @override
  State<_VisitTile> createState() => _VisitTileState();
}

class _VisitTileState extends State<_VisitTile> {
  bool _editing = false;
  bool _saving = false;
  late TextEditingController _notesCtrl;

  @override
  void initState() {
    super.initState();
    _notesCtrl = TextEditingController(text: widget.notes ?? '');
  }

  @override
  void dispose() {
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _saveNotes() async {
    if (widget.visitId == null) {
      showCenterSnackBar(
        context,
        'This visit can\'t be updated yet — visit ID is missing from the server response.',
        isError: true,
      );
      return;
    }

    setState(() => _saving = true);
    try {
      await PatientService.updateVisitNotes(widget.visitId!, _notesCtrl.text.trim());
      if (!mounted) return;
      setState(() { _editing = false; _saving = false; });
      showCenterSnackBar(context, 'Note updated.');
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      showCenterSnackBar(context, e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF4F6FB),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  widget.serviceType,
                  style: const TextStyle(
                    fontSize: 11.5,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primary,
                  ),
                ),
              ),
              const Spacer(),
              if (widget.visitDate.isNotEmpty)
                Text(widget.visitDate, style: const TextStyle(fontSize: 11.5, color: AppColors.textGrey)),
            ],
          ),
          if (widget.doctorName != null && widget.doctorName!.isNotEmpty) ...[
            const SizedBox(height: 6),
            Row(
              children: [
                const Icon(Icons.medical_services_outlined, size: 12, color: AppColors.textGrey),
                const SizedBox(width: 4),
                Text('Dr. ${widget.doctorName}', style: const TextStyle(fontSize: 12, color: AppColors.textDark)),
              ],
            ),
          ],
          const SizedBox(height: 6),
          if (_editing) ...[
            TextField(
              controller: _notesCtrl,
              autofocus: true,
              maxLines: 3,
              style: const TextStyle(fontSize: 11.5),
              decoration: InputDecoration(
                isDense: true,
                hintText: 'Add a note...',
                hintStyle: const TextStyle(color: AppColors.textGrey, fontSize: 11.5),
                filled: true,
                fillColor: Colors.white,
                contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
                ),
              ),
            ),
            const SizedBox(height: 6),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                TextButton(
                  onPressed: _saving
                      ? null
                      : () => setState(() {
                            _notesCtrl.text = widget.notes ?? '';
                            _editing = false;
                          }),
                  child: const Text('Cancel', style: TextStyle(fontSize: 12, color: AppColors.textGrey)),
                ),
                const SizedBox(width: 4),
                TextButton(
                  onPressed: _saving ? null : _saveNotes,
                  child: _saving
                      ? const SizedBox(
                          width: 14, height: 14,
                          child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.primary),
                        )
                      : const Text('Save', style: TextStyle(fontSize: 12, color: AppColors.primary, fontWeight: FontWeight.w600)),
                ),
              ],
            ),
          ] else
            GestureDetector(
              onTap: () => setState(() => _editing = true),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      (widget.notes != null && widget.notes!.isNotEmpty) ? widget.notes! : 'Add a note...',
                      style: TextStyle(
                        fontSize: 11.5,
                        color: (widget.notes != null && widget.notes!.isNotEmpty)
                            ? AppColors.textGrey
                            : AppColors.textGrey.withValues(alpha: 0.6),
                        fontStyle: (widget.notes != null && widget.notes!.isNotEmpty)
                            ? FontStyle.normal
                            : FontStyle.italic,
                      ),
                    ),
                  ),
                  const Icon(Icons.edit_outlined, size: 13, color: AppColors.textGrey),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  const _DetailRow({required this.icon, required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Icon(icon, size: 16, color: AppColors.primary),
          const SizedBox(width: 8),
          Text('$label: ', style: const TextStyle(color: AppColors.textGrey, fontSize: 12.5)),
          Expanded(child: Text(value, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12.5))),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  ADD / EDIT PATIENT BOTTOM SHEET
// ─────────────────────────────────────────────────────────────
class _AddEditPatientSheet extends StatefulWidget {
  final PatientModel? patient; // null = add mode, non-null = edit mode
  final VoidCallback onSaved;
  const _AddEditPatientSheet({this.patient, required this.onSaved});

  @override
  State<_AddEditPatientSheet> createState() => _AddEditPatientSheetState();
}

class _AddEditPatientSheetState extends State<_AddEditPatientSheet> {
  late final TextEditingController _fnameCtrl;
  late final TextEditingController _lnameCtrl;
  late final TextEditingController _emailCtrl;
  late final TextEditingController _contactCtrl;
  DateTime? _birthdate;
  bool _saving = false;
  String? _error;

  bool get _isEdit => widget.patient != null;

  @override
  void initState() {
    super.initState();
    _fnameCtrl = TextEditingController(text: widget.patient?.fname ?? '');
    _lnameCtrl = TextEditingController(text: widget.patient?.lname ?? '');
    _emailCtrl = TextEditingController(text: widget.patient?.email ?? '');
    _contactCtrl = TextEditingController(text: widget.patient?.contact ?? '');
    _birthdate = widget.patient?.birthdate;
  }

  @override
  void dispose() {
    _fnameCtrl.dispose();
    _lnameCtrl.dispose();
    _emailCtrl.dispose();
    _contactCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickBirthdate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _birthdate ?? DateTime(2000),
      firstDate: DateTime(1900),
      lastDate: DateTime.now(),
    );
    if (picked != null) setState(() => _birthdate = picked);
  }

  Future<void> _save() async {
    if (_fnameCtrl.text.trim().isEmpty || _lnameCtrl.text.trim().isEmpty) {
      setState(() => _error = 'First and last name are required.');
      return;
    }

    setState(() { _saving = true; _error = null; });

    final bdateStr = _birthdate != null
        ? '${_birthdate!.year}-${_birthdate!.month.toString().padLeft(2, '0')}-${_birthdate!.day.toString().padLeft(2, '0')}'
        : null;

    try {
      if (_isEdit) {
        await PatientService.update(
          id: widget.patient!.id,
          fname: _fnameCtrl.text.trim(),
          lname: _lnameCtrl.text.trim(),
          birthdate: bdateStr,
          email: _emailCtrl.text.trim().isEmpty ? null : _emailCtrl.text.trim(),
          contact: _contactCtrl.text.trim().isEmpty ? null : _contactCtrl.text.trim(),
        );
      } else {
        await PatientService.create(
          fname: _fnameCtrl.text.trim(),
          lname: _lnameCtrl.text.trim(),
          birthdate: bdateStr,
          email: _emailCtrl.text.trim().isEmpty ? null : _emailCtrl.text.trim(),
          contact: _contactCtrl.text.trim().isEmpty ? null : _contactCtrl.text.trim(),
        );
      }
      if (!mounted) return;
      widget.onSaved();
      showCenterSnackBar(context, _isEdit ? 'Patient updated successfully.' : 'Patient added successfully.');
      Navigator.pop(context);
    } catch (e) {
      setState(() { _saving = false; _error = e.toString().replaceFirst('Exception: ', ''); });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 20, right: 20, top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 40, height: 4,
              decoration: BoxDecoration(color: const Color(0xFFDDE1E8), borderRadius: BorderRadius.circular(2)),
            ),
          ),
          const SizedBox(height: 16),
          Text(_isEdit ? 'Edit Patient' : 'Add New Patient',
              style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold)),
          const SizedBox(height: 16),

          if (_error != null) ...[
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              decoration: BoxDecoration(color: const Color(0xFFFDEAEA), borderRadius: BorderRadius.circular(10)),
              child: Text(_error!, style: const TextStyle(color: Color(0xFFD32F2F), fontSize: 12.5)),
            ),
            const SizedBox(height: 12),
          ],

          _SheetField(hint: 'First Name', icon: Icons.person_outline_rounded, controller: _fnameCtrl),
          const SizedBox(height: 10),
          _SheetField(hint: 'Last Name', icon: Icons.person_outline_rounded, controller: _lnameCtrl),
          const SizedBox(height: 10),
          GestureDetector(
            onTap: _pickBirthdate,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
              decoration: BoxDecoration(
                color: const Color(0xFFF4F6FB),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: [
                  const Icon(Icons.cake_outlined, size: 18, color: AppColors.textGrey),
                  const SizedBox(width: 10),
                  Text(
                    _birthdate != null
                        ? '${_birthdate!.month}/${_birthdate!.day}/${_birthdate!.year}'
                        : 'Birthdate (optional)',
                    style: TextStyle(fontSize: 13.5, color: _birthdate != null ? AppColors.textDark : AppColors.textGrey),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 10),
          _SheetField(hint: 'Email (optional)', icon: Icons.email_outlined, controller: _emailCtrl,
              keyboardType: TextInputType.emailAddress),
          const SizedBox(height: 10),
          _SheetField(hint: 'Contact Number (optional)', icon: Icons.phone_outlined, controller: _contactCtrl,
              keyboardType: TextInputType.phone),
          const SizedBox(height: 18),
          SizedBox(
            width: double.infinity,
            height: 48,
            child: ElevatedButton.icon(
              onPressed: _saving ? null : _save,
              icon: _saving
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : Icon(_isEdit ? Icons.save_outlined : Icons.add, color: Colors.white, size: 18),
              label: Text(_isEdit ? 'Save Changes' : 'Add Patient',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SheetField extends StatelessWidget {
  final String hint;
  final IconData icon;
  final TextEditingController? controller;
  final TextInputType keyboardType;
  final int maxLines;
  const _SheetField({
    required this.hint,
    required this.icon,
    this.controller,
    this.keyboardType = TextInputType.text,
    this.maxLines = 1,
  });

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      keyboardType: keyboardType,
      maxLines: maxLines,
      style: const TextStyle(fontSize: 13.5),
      decoration: InputDecoration(
        prefixIcon: Icon(icon, size: 18, color: AppColors.textGrey),
        hintText: hint,
        hintStyle: const TextStyle(color: AppColors.textGrey, fontSize: 13),
        filled: true,
        fillColor: const Color(0xFFF4F6FB),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.border)),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.border)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.primary, width: 1.5)),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  SMALL REUSABLE WIDGETS
// ─────────────────────────────────────────────────────────────
class _IdChip extends StatelessWidget {
  final String id;
  const _IdChip({required this.id});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: const Color(0xFFF0F2F8), borderRadius: BorderRadius.circular(6)),
      child: Text(id,
          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.textGrey, letterSpacing: 0.3)),
    );
  }
}

class _CardButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  const _CardButton({required this.label, required this.icon, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: color.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
          child: Row(
            children: [
              Icon(icon, size: 14, color: color),
              const SizedBox(width: 5),
              Text(label, style: TextStyle(fontSize: 12.5, color: color, fontWeight: FontWeight.w600)),
            ],
          ),
        ),
      ),
    );
  }
}

class _SearchField extends StatelessWidget {
  final ValueChanged<String> onChanged;
  const _SearchField({required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 38,
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(10), border: Border.all(color: AppColors.border)),
      child: TextField(
        onChanged: onChanged,
        style: const TextStyle(fontSize: 13),
        decoration: const InputDecoration(
          isDense: true,
          prefixIcon: Icon(Icons.search, size: 16, color: AppColors.textGrey),
          prefixIconConstraints: BoxConstraints(minWidth: 36, minHeight: 0),
          hintText: 'Search patients...',
          hintStyle: TextStyle(color: AppColors.textGrey, fontSize: 12),
          border: InputBorder.none,
          contentPadding: EdgeInsets.symmetric(vertical: 10, horizontal: 4),
        ),
      ),
    );
  }
}

// Filter dropdown used for the Doctor / Service Type row.
class _FilterDropdown extends StatelessWidget {
  final IconData icon;
  final String value;
  final List<String> items;
  final ValueChanged<String> onChanged;
  const _FilterDropdown({
    required this.icon,
    required this.value,
    required this.items,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    // Guard against a stale `value` that's no longer in `items` (e.g. right
    // after items finish loading) — falls back to the first entry so
    // DropdownButton never throws over a transient mismatch.
    final safeValue = items.contains(value) ? value : items.first;

    return Container(
      height: 38,
      padding: const EdgeInsets.symmetric(horizontal: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: safeValue,
          isExpanded: true,
          isDense: true,
          icon: const Icon(Icons.keyboard_arrow_down_rounded, size: 16, color: AppColors.textGrey),
          style: const TextStyle(fontSize: 12, color: AppColors.textDark),
          items: items
              .map((e) => DropdownMenuItem(
                    value: e,
                    child: Row(
                      children: [
                        Icon(icon, size: 14, color: AppColors.textGrey),
                        const SizedBox(width: 6),
                        Flexible(child: Text(e, overflow: TextOverflow.ellipsis)),
                      ],
                    ),
                  ))
              .toList(),
          onChanged: (v) {
            if (v != null) onChanged(v);
          },
        ),
      ),
    );
  }
}

class _AddButton extends StatelessWidget {
  final VoidCallback onTap;
  const _AddButton({required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 38,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(color: AppColors.primary, borderRadius: BorderRadius.circular(10)),
        child: const Row(
          children: [
            Icon(Icons.add, color: Colors.white, size: 16),
            SizedBox(width: 4),
            Text('Add', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
          ],
        ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  final bool filtersActive;
  final VoidCallback onClearFilters;
  const _EmptyState({this.filtersActive = false, required this.onClearFilters});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 48),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('👥', style: TextStyle(fontSize: 52)),
            const SizedBox(height: 16),
            Text(
              filtersActive ? 'No patients match this filter' : 'No patients found',
              style: const TextStyle(fontSize: 15, color: AppColors.textGrey, fontWeight: FontWeight.w500),
            ),
            if (filtersActive) ...[
              const SizedBox(height: 8),
              TextButton(
                onPressed: onClearFilters,
                child: const Text('Clear filters', style: TextStyle(fontSize: 13, color: AppColors.primary)),
              ),
            ],
          ],
        ),
      ),
    );
  }
}