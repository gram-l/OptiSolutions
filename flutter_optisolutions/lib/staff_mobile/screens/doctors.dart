import 'dart:io';
import 'package:flutter/material.dart';
import 'package:csv/csv.dart';
import 'package:excel/excel.dart' as xls;
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import 'staffcolor.dart';
import 'package:flutter_optisolutions/login.dart';
import 'dashboard.dart';
import 'inquiries.dart';
import 'appointments.dart';
import 'patients.dart';
import 'profile.dart';
import 'notifications.dart';
import 'settings.dart';
import '../widgets/notification_badge.dart';
import '../widgets/center_snackbar.dart';
import '../services/api_service.dart';

class DoctorsPage extends StatefulWidget {
  const DoctorsPage({super.key});

  @override
  State<DoctorsPage> createState() => _DoctorsPageState();
}

class _DoctorsPageState extends State<DoctorsPage> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  String _selectedDepartment = 'All Departments';

  // Loaded from the database via API
  List<Map<String, dynamic>> _allDoctors = [];
  bool _loading = true;
  String? _loadError;

  // Logged-in staff name 
  String _staffName = 'Staff';
  String _staffEmail = '';

  static const List<String> _weekDays = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday',
  ];

  @override
  void initState() {
    super.initState();
    _loadStaffName();
    _loadDoctors();
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

  Future<void> _loadDoctors() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    try {
      final result = await ApiService.get('/doctors');
      setState(() {
        _allDoctors = (result as List).map<Map<String, dynamic>>((doc) {
          return {

            'id': doc['id'],
            'name': (doc['name'] ?? 'Unknown').toString(),
            'specialty': (doc['specialty'] ?? 'General').toString(),
            'status': (doc['status'] ?? 'Unavailable').toString(),

            'scheduleByDay': _groupSchedules(doc['schedules'] as List? ?? []),
            'isActive': doc['is_active'] == 1,
            'avatar': (doc['avatar'] ?? '').toString(),
            'color': _hexToColor(doc['color']),
          };
        }).toList();
        _loading = false;
      });
    } catch (e) {
      setState(() {
        _loadError = e.toString().replaceFirst('Exception: ', '');
        _loading = false;
      });
    }
  }

  Color _hexToColor(String? hex) {
    if (hex == null || hex.isEmpty) return Colors.blueGrey;
    final cleanHex = hex.replaceFirst('#', '');
    try {
      return Color(int.parse('FF$cleanHex', radix: 16));
    } catch (_) {
      
      return Colors.blueGrey;
    }
  }

  // Get unique departments for filter
  List<String> get _departments {
   
    List<String> depts = _allDoctors
        .map((doc) => (doc['specialty'] ?? 'General').toString())
        .toSet()
        .toList();
    depts.sort();
    return ['All Departments', ...depts];
  }

  // Get filtered doctors by department and search
  List<Map<String, dynamic>> get _filteredDoctors {
    List<Map<String, dynamic>> result = List.from(_allDoctors);

    // Filter by department
    if (_selectedDepartment != 'All Departments') {
      result = result
          .where((doc) => doc['specialty'] == _selectedDepartment)
          .toList();
    }

    // Filter by search query
    if (_searchQuery.isNotEmpty) {
      final query = _searchQuery.toLowerCase();
      result = result.where((doc) {
        
        final name = (doc['name'] ?? '').toString().toLowerCase();
        final specialty = (doc['specialty'] ?? '').toString().toLowerCase();
        return name.contains(query) || specialty.contains(query);
      }).toList();
    }

    return result;
  }


  TimeOfDay? _timeFromHHmm(String? hhmm) {
    if (hhmm == null || hhmm.isEmpty) return null;
    final parts = hhmm.split(':');
    if (parts.length < 2) return null;
    final h = int.tryParse(parts[0]);
    final m = int.tryParse(parts[1]);
    if (h == null || m == null) return null;
    return TimeOfDay(hour: h, minute: m);
  }

  /// Converts a TimeOfDay back into "HH:mm" (24-hour) for the API.
  String _hhmmFromTime(TimeOfDay t) =>
      '${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}';

  /// Friendly 12-hour display, e.g. "8:00 AM".
  String _formatTimeOfDay(TimeOfDay? t) {
    if (t == null) return '';
    final hour12 = t.hourOfPeriod == 0 ? 12 : t.hourOfPeriod;
    final period = t.period == DayPeriod.am ? 'AM' : 'PM';
    final minute = t.minute.toString().padLeft(2, '0');
    return '$hour12:$minute $period';
  }


  Map<String, List<Map<String, TimeOfDay?>>> _groupSchedules(
    List apiSchedules,
  ) {
    final Map<String, List<Map<String, TimeOfDay?>>> result = {
      for (final d in _weekDays) d: <Map<String, TimeOfDay?>>[],
    };
    for (final entry in apiSchedules) {
      if (entry is! Map) continue;
      final day = (entry['day'] ?? '').toString();
      if (!_weekDays.contains(day)) continue;
      result[day]!.add({
        'start': _timeFromHHmm(entry['start_time']?.toString()),
        'end': _timeFromHHmm(entry['end_time']?.toString()),
      });
    }
    return result;
  }

  List<Map<String, String?>> _flattenSchedule(
    Map<String, List<Map<String, TimeOfDay?>>> schedule,
  ) {
    final entries = <Map<String, String?>>[];
    for (final day in _weekDays) {
      for (final session in schedule[day]!) {
        entries.add({
          'day': day,
          'start_time': session['start'] != null
              ? _hhmmFromTime(session['start']!)
              : null,
          'end_time': session['end'] != null
              ? _hhmmFromTime(session['end']!)
              : null,
        });
      }
    }
    return entries;
  }

  /// e.g. "Mon, Tue, Wed, Thu, Fri"
  String _scheduleDaysSummary(Map<String, List<Map<String, TimeOfDay?>>> s) {
    final activeDays = _weekDays.where((d) => s[d]!.isNotEmpty).toList();
    if (activeDays.isEmpty) return 'Not set';
    return activeDays.map((d) => d.substring(0, 3)).join(', ');
  }

  /// e.g. "8:00 AM - 11:00 AM | 2:00 PM - 6:00 PM"
  String _scheduleSessionsSummary(
    Map<String, List<Map<String, TimeOfDay?>>> s,
  ) {
    final sessions = <String>{};
    for (final d in _weekDays) {
      for (final session in s[d]!) {
        final start = _formatTimeOfDay(session['start']);
        final end = _formatTimeOfDay(session['end']);
        if (start.isNotEmpty && end.isNotEmpty) {
          sessions.add('$start - $end');
        }
      }
    }
    if (sessions.isEmpty) return 'Not set';
    return sessions.join(' | ');
  }

  // Edit Schedule — checklist (Mon-Sun) + multiple sessions per day
  void _editSchedule(Map<String, dynamic> doctor) {
    
    final source =
        doctor['scheduleByDay'] as Map<String, List<Map<String, TimeOfDay?>>>;
    final Map<String, List<Map<String, TimeOfDay?>>> schedule = {
      for (final d in _weekDays)
        d: source[d]!.map((s) => Map<String, TimeOfDay?>.from(s)).toList(),
    };

    showDialog(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (dialogContext, setStateDialog) {
          Future<void> addSession(String day) async {
            final start = await showTimePicker(
              context: dialogContext,
              initialTime: const TimeOfDay(hour: 8, minute: 0),
              helpText: 'Start time',
            );
            if (start == null) return;
            if (!dialogContext.mounted) return;
            final end = await showTimePicker(
              context: dialogContext,
              initialTime: const TimeOfDay(hour: 11, minute: 0),
              helpText: 'End time',
            );
            if (end == null) return;
            setStateDialog(() {
              schedule[day]!.add({'start': start, 'end': end});
            });
          }

          Future<void> editSession(String day, int index) async {
            final current = schedule[day]![index];
            final start = await showTimePicker(
              context: dialogContext,
              initialTime:
                  current['start'] ?? const TimeOfDay(hour: 8, minute: 0),
              helpText: 'Start time',
            );
            if (start == null) return;
            if (!dialogContext.mounted) return;
            final end = await showTimePicker(
              context: dialogContext,
              initialTime:
                  current['end'] ?? const TimeOfDay(hour: 11, minute: 0),
              helpText: 'End time',
            );
            if (end == null) return;
            setStateDialog(() {
              schedule[day]![index] = {'start': start, 'end': end};
            });
          }

          return AlertDialog(
            title: Text('Edit Schedule - ${doctor['name']}'),
            contentPadding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            content: SizedBox(
              width: double.maxFinite,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: _weekDays.map((day) {
                    final sessions = schedule[day]!;
                    final isChecked = sessions.isNotEmpty;
                    return Container(
                      margin: const EdgeInsets.only(bottom: 10),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Checkbox(
                                value: isChecked,
                                activeColor: StaffColors.primary,
                                
                                onChanged: (checked) async {
                                  if (checked == true) {
                                    await addSession(day);
                                  } else {
                                    setStateDialog(() {
                                      schedule[day] = [];
                                    });
                                  }
                                },
                              ),
                              Text(
                                day,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w600,
                                  fontSize: 14,
                                ),
                              ),
                            ],
                          ),
                          if (isChecked) ...[
                            ...List.generate(sessions.length, (i) {
                              final s = sessions[i];
                              return Padding(
                                padding: const EdgeInsets.only(
                                  left: 40,
                                  bottom: 6,
                                ),
                                child: Row(
                                  children: [
                                    Expanded(
                                      child: InkWell(
                                        onTap: () => editSession(day, i),
                                        child: Text(
                                          '${_formatTimeOfDay(s['start'])} - ${_formatTimeOfDay(s['end'])}',
                                          style: const TextStyle(fontSize: 13),
                                        ),
                                      ),
                                    ),
                                    IconButton(
                                      icon: const Icon(
                                        Icons.delete_outline,
                                        size: 18,
                                        color: Colors.red,
                                      ),
                                      constraints: const BoxConstraints(),
                                      padding: EdgeInsets.zero,
                                      onPressed: () {
                                        setStateDialog(() {
                                          schedule[day]!.removeAt(i);
                                        });
                                      },
                                    ),
                                  ],
                                ),
                              );
                            }),
                            Padding(
                              padding: const EdgeInsets.only(
                                left: 40,
                                bottom: 6,
                              ),
                              child: TextButton.icon(
                                onPressed: () => addSession(day),
                                icon: const Icon(Icons.add, size: 16),
                                label: const Text('Add session'),
                                style: TextButton.styleFrom(
                                  foregroundColor: StaffColors.primary,
                                  padding: EdgeInsets.zero,
                                  minimumSize: const Size(0, 30),
                                  tapTargetSize:
                                      MaterialTapTargetSize.shrinkWrap,
                                  alignment: Alignment.centerLeft,
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    );
                  }).toList(),
                ),
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(dialogContext),
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: () async {
                  final index = _allDoctors.indexWhere(
                    (d) => d['name'] == doctor['name'],
                  );
                  if (index == -1) return;

                  final entries = _flattenSchedule(schedule);

                  try {
                    await ApiService.patch('/doctors/${doctor['id']}', {
                      'schedules': entries,
                    });

                    setState(() {
                      final updatedDoctor = Map<String, dynamic>.from(
                        _allDoctors[index],
                      );
                      updatedDoctor['scheduleByDay'] = schedule;
                      _allDoctors[index] = updatedDoctor;
                    });

                    if (!dialogContext.mounted) return;
                    Navigator.pop(dialogContext);
                    if (!context.mounted) return;
                    showCenterSnackBar(
                      context,
                      'Schedule updated successfully!',
                    );
                  } catch (e) {
                    if (!dialogContext.mounted) return;
                    showCenterSnackBar(
                      dialogContext,
                      'Failed to update: $e',
                      isError: true,
                    );
                  }
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: StaffColors.primary,
                  foregroundColor: Colors.white,
                ),
                child: const Text('Save'),
              ),
            ],
          );
        },
      ),
    );
  }

  // ─────────────────────────────────────────────
  //  EXPORT (CSV / Excel / PDF)
  // ─────────────────────────────────────────────
  void _showExportSheet() {
    final doctorsToExport = _filteredDoctors;

    if (doctorsToExport.isEmpty) {
      showCenterSnackBar(context, 'No doctors to export', isError: true);
      return;
    }

    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(
                Icons.table_chart_outlined,
                color: StaffColors.primary,
              ),
              title: const Text('Export as CSV'),
              onTap: () {
                Navigator.pop(context);
                _exportCsv();
              },
            ),
            ListTile(
              leading: const Icon(
                Icons.grid_on_rounded,
                color: StaffColors.primary,
              ),
              title: const Text('Export as Excel'),
              onTap: () {
                Navigator.pop(context);
                _exportExcel();
              },
            ),
            ListTile(
              leading: const Icon(
                Icons.picture_as_pdf_outlined,
                color: StaffColors.primary,
              ),
              title: const Text('Export as PDF'),
              onTap: () {
                Navigator.pop(context);
                _exportPdf();
              },
            ),
          ],
        ),
      ),
    );
  }

  List<List<String>> get _exportRows {
    final rows = <List<String>>[
      ['Name', 'Department', 'Status', 'Schedule', 'Time'],
    ];
    for (final d in _filteredDoctors) {
      final scheduleByDay =
          d['scheduleByDay'] as Map<String, List<Map<String, TimeOfDay?>>>;
      final scheduleLabel = _scheduleDaysSummary(scheduleByDay);
      final timeLabel = _scheduleSessionsSummary(scheduleByDay);
      rows.add([
        (d['name'] ?? '').toString(),
        (d['specialty'] ?? '').toString(),
        (d['status'] ?? '').toString(),
        scheduleLabel,
        timeLabel,
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
      final file = await _writeToDownloads('doctors.csv', csv.codeUnits);
      await Share.shareXFiles([XFile(file.path)], text: 'Doctors list export');
      if (!mounted) return;
      showCenterSnackBar(context, 'CSV exported successfully.');
    } catch (e) {
      if (!mounted) return;
      showCenterSnackBar(
        context,
        'CSV export failed. Please try again.',
        isError: true,
      );
    }
  }

  Future<void> _exportExcel() async {
    try {
      final workbook = xls.Excel.createExcel();
      final sheet = workbook['Doctors'];
      for (final row in _exportRows) {
        sheet.appendRow(row.map((c) => xls.TextCellValue(c)).toList());
      }
      final bytes = workbook.encode();
      if (bytes == null) {
        if (!mounted) return;
        showCenterSnackBar(
          context,
          'Excel export failed. Please try again.',
          isError: true,
        );
        return;
      }
      final file = await _writeToDownloads('doctors.xlsx', bytes);
      await Share.shareXFiles([XFile(file.path)], text: 'Doctors list export');
      if (!mounted) return;
      showCenterSnackBar(context, 'Excel file exported successfully.');
    } catch (e) {
      if (!mounted) return;
      showCenterSnackBar(
        context,
        'Excel export failed. Please try again.',
        isError: true,
      );
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
            headerStyle: pw.TextStyle(
              fontSize: 10,
              fontWeight: pw.FontWeight.bold,
            ),
          ),
        ),
      );
      final bytes = await doc.save();
      final file = await _writeToDownloads('doctors.pdf', bytes);
      await Share.shareXFiles([XFile(file.path)], text: 'Doctors list export');
      if (!mounted) return;
      showCenterSnackBar(context, 'PDF exported successfully.');
    } catch (e) {
      if (!mounted) return;
      showCenterSnackBar(
        context,
        'PDF export failed. Please try again.',
        isError: true,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      drawer: _buildDrawer(),
      appBar: _buildAppBar(),
      body: Column(
        children: [
          _buildHeader(),
          _buildSearchAndFilter(),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _loadError != null
                ? Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          _loadError!,
                          style: const TextStyle(color: Colors.red),
                        ),
                        const SizedBox(height: 12),
                        ElevatedButton(
                          onPressed: _loadDoctors,
                          child: const Text('Retry'),
                        ),
                      ],
                    ),
                  )
                : _filteredDoctors.isEmpty
                ? _buildEmptyState()
                : RefreshIndicator(
                    onRefresh: _loadDoctors,
                    child: ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _filteredDoctors.length,
                      itemBuilder: (context, index) {
                        final doctor = _filteredDoctors[index];
                        return _buildDoctorCard(doctor);
                      },
                    ),
                  ),
          ),
        ],
      ),
      bottomNavigationBar: _buildBottomNav(),
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
              color: Colors.black87, //newc
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
          _buildDrawerItem(
            Icons.help,
            'Help',
            false,
            () => Navigator.pop(context),
          ),
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

  // Header with Download
  Widget _buildHeader() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      color: Colors.white,
      child: Row(
        children: [
          const Icon(
            Icons.medical_services,
            color: StaffColors.primary,
            size: 28,
          ),
          const SizedBox(width: 12),
          const Text(
            'Doctors',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.black87, //newc
            ),
          ),
          const Spacer(),
          ElevatedButton.icon(
            onPressed: _showExportSheet,
            icon: const Icon(Icons.download, size: 18),
            label: const Text('Download'),
            style: ElevatedButton.styleFrom(
              backgroundColor: StaffColors.primary,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // Search and Filter - CHANGED to department filter
  Widget _buildSearchAndFilter() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      color: Colors.white,
      child: Row(
        children: [
          // Search
          Expanded(
            child: Container(
              height: 40,
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(8),
              ),
              child: TextField(
                controller: _searchController,
                onChanged: (value) => setState(() => _searchQuery = value),
                decoration: InputDecoration(
                  hintText: 'Search doctors...',
                  hintStyle: const TextStyle(fontSize: 13, color: Colors.grey),
                  prefixIcon: const Icon(
                    Icons.search,
                    size: 18,
                    color: Colors.grey,
                  ),
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(vertical: 8),
                  suffixIcon: _searchQuery.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear, size: 18),
                          onPressed: () => setState(() {
                            _searchController.clear();
                            _searchQuery = '';
                          }),
                        )
                      : null,
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),

          // Department Filter - CHANGED from doctor names to departments
          Container(
            height: 40,
            padding: const EdgeInsets.symmetric(horizontal: 8),
            decoration: BoxDecoration(
              color: Colors.grey.shade100,
              borderRadius: BorderRadius.circular(8),
            ),
            child: DropdownButton<String>(
              value: _selectedDepartment,
              underline: const SizedBox(),
              icon: const Icon(
                Icons.arrow_drop_down,
                color: StaffColors.primary,
              ),
              style: const TextStyle(
                fontSize: 13,
                color: StaffColors.primary,
                fontWeight: FontWeight.w500,
              ),
              onChanged: (String? newValue) {
                setState(() {
                  _selectedDepartment = newValue!;
                });
              },
              items: _departments.map<DropdownMenuItem<String>>((String value) {
                return DropdownMenuItem<String>(
                  value: value,
                  child: Text(value, style: const TextStyle(fontSize: 13)),
                );
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.medical_services, size: 60, color: Colors.grey.shade400),
          const SizedBox(height: 12),
          Text(
            'No doctors found in this department',
            style: TextStyle(fontSize: 16, color: Colors.grey.shade500),
          ),
        ],
      ),
    );
  }

  // Doctor Card
  Widget _buildDoctorCard(Map<String, dynamic> doctor) {
    final bool isAvailable = doctor['status'] == 'Available';

    
    final scheduleByDay =
        doctor['scheduleByDay'] as Map<String, List<Map<String, TimeOfDay?>>>;
    final scheduleLabel = _scheduleDaysSummary(scheduleByDay);
    final timeLabel = _scheduleSessionsSummary(scheduleByDay);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
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
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Doctor Info Row
          Row(
            children: [
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: doctor['color'].withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    doctor['avatar'],
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: doctor['color'],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      doctor['name'],
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.black87, //newc
                      ),
                    ),
                    // Department/Specialty badge
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: StaffColors.primary.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        doctor['specialty'],
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                          color: StaffColors.primary,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              // Status Badge
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: isAvailable
                      ? Colors.green.withValues(alpha: 0.1)
                      : Colors.red.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: isAvailable
                        ? Colors.green.withValues(alpha: 0.3)
                        : Colors.red.withValues(alpha: 0.3),
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 6,
                      height: 6,
                      decoration: BoxDecoration(
                        color: isAvailable ? Colors.green : Colors.red,
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      doctor['status'],
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: isAvailable ? Colors.green : Colors.red,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Schedule
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.grey.shade50,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: [
                const Icon(Icons.calendar_today, size: 14, color: Colors.grey),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    scheduleLabel,
                    style: const TextStyle(fontSize: 13, color: Colors.black87),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.grey.shade50,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: [
                const Icon(Icons.access_time, size: 14, color: Colors.grey),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    timeLabel,
                    style: const TextStyle(fontSize: 13, color: Colors.black87),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          
          Builder(
            builder: (context) {
              final bool doctorActive = doctor['isActive'] == true;
              return SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () {
                    if (!doctorActive) {
                      showDialog(
                        context: context,
                        builder: (context) => AlertDialog(
                          title: const Text('Schedule Locked'),
                          content: const Text(
                            "You cannot change this doctor's schedule because the administrator marked the doctor as inactive.",
                          ),
                          actions: [
                            TextButton(
                              onPressed: () => Navigator.pop(context),
                              child: const Text('OK'),
                            ),
                          ],
                        ),
                      );
                      return;
                    }
                    _editSchedule(doctor);
                  },
                  icon: Icon(
                    Icons.edit,
                    size: 16,
                    color: doctorActive ? StaffColors.primary : Colors.grey,
                  ),
                  label: Text(
                    'Edit Schedule',
                    style: TextStyle(
                      color: doctorActive ? StaffColors.primary : Colors.grey,
                    ),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: doctorActive
                        ? StaffColors.primary.withValues(alpha: 0.05)
                        : Colors.grey.withValues(alpha: 0.08),
                    foregroundColor: doctorActive
                        ? StaffColors.primary
                        : Colors.grey,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                      side: BorderSide(
                        color: doctorActive
                            ? StaffColors.primary.withValues(alpha: 0.2)
                            : Colors.grey.withValues(alpha: 0.3),
                      ),
                    ),
                  ),
                ),
              );
            },
          ),
        ],
      ),
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
        currentIndex: 3,
        onTap: (index) {
          _searchController.clear();
          setState(() {
            _searchQuery = '';
          });

          switch (index) {
            case 0:
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (context) => const Dashboard()),
                (route) => false,
              );
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
              // Doctors na (current page)
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
            label: 'Manage Doctors',
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
