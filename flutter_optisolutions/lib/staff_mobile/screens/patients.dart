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
import 'doctors.dart';
import 'profile.dart';
import 'notifications.dart';
import 'settings.dart';
import '../widgets/notification_badge.dart';
import '../widgets/center_snackbar.dart';
import '../services/api_service.dart';

class PatientsPage extends StatefulWidget {
  const PatientsPage({super.key});

  @override
  State<PatientsPage> createState() => _PatientsPageState();
}

class _PatientsPageState extends State<PatientsPage> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  String _selectedDepartment = 'All Depts';

  // Date Range Filter
  DateTime? _startDate;
  DateTime? _endDate;
  bool _isDateFilterActive = false;

  List<Map<String, dynamic>> _allPatients = [];
  bool _loading = true;
  String? _loadError;

  // Logged-in staff name
  String _staffName = 'Staff';
  String _staffEmail = '';

  @override
  void initState() {
    super.initState();
    _loadStaffName();
    _loadPatients();
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

  Future<void> _loadPatients() async {
    setState(() {
      _loading = true;
      _loadError = null;
    });
    try {
      final result = await ApiService.get('/patients');
      setState(() {
        _allPatients = (result as List).map<Map<String, dynamic>>((p) {
          return {
            'dbId': p['dbId'],
            'id': p['id'],
            'name': p['name'],
            'department': p['department'],
            'doctor': p['doctor'],
            'birthday': p['birthday'],
            'contact': p['contact'],
            'status': p['status'],
            'dateRegistered': p['dateRegistered'],
            'notes': p['notes'],
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

  // Get unique departments for filter
  List<String> get _departmentList {
    List<String> depts = ['All Depts'];
    for (var patient in _allPatients) {
      if (!depts.contains(patient['department'])) {
        depts.add(patient['department']);
      }
    }
    return depts;
  }

  // Get filtered patients with date range and department
  List<Map<String, dynamic>> get _filteredPatients {
    List<Map<String, dynamic>> result = List.from(_allPatients);

    // Filter by date range
    if (_isDateFilterActive && _startDate != null && _endDate != null) {
      result = result.where((patient) {
        try {
          final registeredDate = DateTime.parse(patient['dateRegistered']);
          return registeredDate.isAfter(
                _startDate!.subtract(const Duration(days: 1)),
              ) &&
              registeredDate.isBefore(_endDate!.add(const Duration(days: 1)));
        } catch (e) {
          return false;
        }
      }).toList();
    }

    // Filter by department
    if (_selectedDepartment != 'All Depts') {
      result = result
          .where((patient) => patient['department'] == _selectedDepartment)
          .toList();
    }

    // Filter by search query
    if (_searchQuery.isNotEmpty) {
      final query = _searchQuery.toLowerCase();
      result = result.where((patient) {
        return patient['name'].toLowerCase().contains(query) ||
            patient['id'].toLowerCase().contains(query) ||
            patient['department'].toLowerCase().contains(query) ||
            patient['doctor'].toLowerCase().contains(query);
      }).toList();
    }

    return result;
  }

  // Format date for display
  String _formatDate(String date) {
    if (date.isEmpty) return 'N/A';
    try {
      final parts = date.split('-');
      final year = parts[0];
      final month = parts[1];
      final day = parts[2];
      final monthNames = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec',
      ];
      return '${monthNames[int.parse(month) - 1]} $day, $year';
    } catch (e) {
      return date;
    }
  }

  // Format date for display (short)
  String _formatDateShort(DateTime date) {
    final monthNames = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec',
    ];
    return '${monthNames[date.month - 1]} ${date.day}, ${date.year}';
  }

  // Calculate age from birthday
  int _calculateAge(String birthday) {
    if (birthday.isEmpty) return 0;
    try {
      final birthDate = DateTime.parse(birthday);
      final today = DateTime.now();
      int age = today.year - birthDate.year;
      if (today.month < birthDate.month ||
          (today.month == birthDate.month && today.day < birthDate.day)) {
        age--;
      }
      return age;
    } catch (e) {
      return 0;
    }
  }

  // Show date range picker
  Future<void> _selectDateRange() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _startDate ?? DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: ThemeData.light().copyWith(
            colorScheme: const ColorScheme.light(
              primary: StaffColors.primary,
              onPrimary: Colors.white,
              surface: Colors.white,
            ),
          ),
          child: child!,
        );
      },
    );

    if (!mounted) return;

    if (picked != null) {
      final DateTime? endPicked = await showDatePicker(
        context: context,
        initialDate: picked,
        firstDate: picked,
        lastDate: DateTime.now(),
        builder: (context, child) {
          return Theme(
            data: ThemeData.light().copyWith(
              colorScheme: const ColorScheme.light(
                primary: StaffColors.primary,
                onPrimary: Colors.white,
                surface: Colors.white,
              ),
            ),
            child: child!,
          );
        },
      );

      if (endPicked != null) {
        setState(() {
          _startDate = picked;
          _endDate = endPicked;
          _isDateFilterActive = true;
        });
      }
    }
  }

  // Clear date filter
  void _clearDateFilter() {
    setState(() {
      _startDate = null;
      _endDate = null;
      _isDateFilterActive = false;
    });
  }

  // ─────────────────────────────────────────────
  //  EXPORT (CSV / Excel / PDF)
  // ─────────────────────────────────────────────
  void _showExportSheet() {
    final patientsToExport = _filteredPatients;

    if (patientsToExport.isEmpty) {
      showCenterSnackBar(context, 'No patients to export', isError: true);
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
      [
        'Patient ID',
        'Name',
        'Department',
        'Doctor',
        'Contact',
        'Status',
        'Date Registered',
      ],
    ];
    for (final p in _filteredPatients) {
      rows.add([
        (p['id'] ?? '').toString(),
        (p['name'] ?? '').toString(),
        (p['department'] ?? '').toString(),
        (p['doctor'] ?? '').toString(),
        (p['contact'] ?? '').toString(),
        (p['status'] ?? '').toString(),
        _formatDate((p['dateRegistered'] ?? '').toString()),
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
      await Share.shareXFiles([XFile(file.path)], text: 'Patients list export');
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
      final sheet = workbook['Patients'];
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
      final file = await _writeToDownloads('patients.xlsx', bytes);
      await Share.shareXFiles([XFile(file.path)], text: 'Patients list export');
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
      final file = await _writeToDownloads('patients.pdf', bytes);
      await Share.shareXFiles([XFile(file.path)], text: 'Patients list export');
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

  // View patient details
  void _viewPatient(Map<String, dynamic> patient) {
    final age = _calculateAge(patient['birthday']);
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            const Icon(Icons.person, color: StaffColors.primary),
            const SizedBox(width: 8),
            Text(patient['name']),
          ],
        ),
        content: SizedBox(
          width: double.maxFinite,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildInfoRow('Patient ID', patient['id']),
              _buildInfoRow('Department', patient['department']),
              _buildInfoRow('Doctor', patient['doctor']),
              _buildInfoRow('Birthday', _formatDate(patient['birthday'])),
              _buildInfoRow('Age', '$age yrs'),
              _buildInfoRow('Contact', patient['contact']),
              _buildInfoRow(
                'Date Registered',
                _formatDate(patient['dateRegistered']),
              ),
              _buildInfoRow('Status', patient['status']),
              const Divider(),
              const Text(
                'Notes:',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                  color: StaffColors.primary,
                ),
              ),
              const SizedBox(height: 4),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.grey.shade50,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  patient['notes'],
                  style: const TextStyle(fontSize: 13),
                ),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
            child: Text(
              '$label:',
              style: const TextStyle(
                fontWeight: FontWeight.w500,
                fontSize: 13,
                color: Colors.grey,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontSize: 13, color: Colors.black87),
            ),
          ),
        ],
      ),
    );
  }

  // edit patients
  void _editPatient(Map<String, dynamic> patient) {
    TextEditingController nameController = TextEditingController(
      text: patient['name'],
    );
    TextEditingController departmentController = TextEditingController(
      text: patient['department'],
    );
    TextEditingController doctorController = TextEditingController(
      text: patient['doctor'],
    );
    TextEditingController birthdayController = TextEditingController(
      text: patient['birthday'], // TEXT FIELD na lang
    );
    TextEditingController contactController = TextEditingController(
      text: patient['contact'],
    );
    TextEditingController notesController = TextEditingController(
      text: patient['notes'],
    );

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setStateDialog) {
          return AlertDialog(
            title: Row(
              children: [
                const Icon(Icons.edit, color: StaffColors.primary),
                const SizedBox(width: 8),
                Text('Edit Patient - ${patient['name']}'),
              ],
            ),
            content: SizedBox(
              width: double.maxFinite,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 10,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.grey.shade300),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.badge, size: 18, color: Colors.grey),
                          const SizedBox(width: 12),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Patient ID',
                                style: TextStyle(
                                  fontSize: 11,
                                  color: Colors.grey,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                              Text(
                                patient['id'],
                                style: const TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.bold,
                                  color: StaffColors.primary,
                                ),
                              ),
                            ],
                          ),
                          const Spacer(),
                          const Icon(Icons.lock, size: 16, color: Colors.grey),
                        ],
                      ),
                    ),
                    const SizedBox(height: 8),
                    _buildEditField('Name', nameController, Icons.person),
                    const SizedBox(height: 8),
                    _buildEditField(
                      'Department',
                      departmentController,
                      Icons.business,
                    ),
                    const SizedBox(height: 8),
                    _buildEditField(
                      'Doctor',
                      doctorController,
                      Icons.medical_services,
                    ),
                    const SizedBox(height: 8),

                    _buildEditField(
                      'Birthday (YYYY-MM-DD)',
                      birthdayController,
                      Icons.cake,
                      hintText: 'e.g. 1990-01-15',
                    ),
                    const SizedBox(height: 8),
                    _buildEditField('Contact', contactController, Icons.phone),
                    const SizedBox(height: 8),
                    _buildEditField(
                      'Notes',
                      notesController,
                      Icons.note,
                      maxLines: 3,
                    ),
                  ],
                ),
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: () async {
                  // Validate birthday format
                  final birthday = birthdayController.text.trim();
                  final birthdayRegex = RegExp(r'^\d{4}-\d{2}-\d{2}$');

                  if (birthday.isNotEmpty &&
                      !birthdayRegex.hasMatch(birthday)) {
                    showCenterSnackBar(
                      context,
                      'Please enter birthday in YYYY-MM-DD format',
                      isError: true,
                    );
                    return;
                  }

                  final index = _allPatients.indexWhere(
                    (p) => p['id'] == patient['id'],
                  );
                  if (index == -1) return;

                  final Map<String, dynamic> changes = {};
                  if (nameController.text.trim().isNotEmpty) {
                    changes['name'] = nameController.text.trim();
                  }
                  if (departmentController.text.trim().isNotEmpty) {
                    changes['department'] = departmentController.text.trim();
                  }
                  if (contactController.text.trim().isNotEmpty) {
                    changes['contact'] = contactController.text.trim();
                  }
                  if (notesController.text.trim().isNotEmpty) {
                    changes['notes'] = notesController.text.trim();
                  }

                  try {
                    final dbId = _allPatients[index]['dbId'];
                    if (dbId != null && changes.isNotEmpty) {
                      await ApiService.patch('/patients/$dbId', changes);
                    }

                    setState(() {
                      final updatedPatient = Map<String, dynamic>.from(
                        _allPatients[index],
                      );
                      if (nameController.text.trim().isNotEmpty) {
                        updatedPatient['name'] = nameController.text.trim();
                      }
                      if (departmentController.text.trim().isNotEmpty) {
                        updatedPatient['department'] = departmentController.text
                            .trim();
                      }
                      if (doctorController.text.trim().isNotEmpty) {
                        updatedPatient['doctor'] = doctorController.text.trim();
                      }
                      if (birthday.isNotEmpty) {
                        updatedPatient['birthday'] = birthday;
                      }
                      if (contactController.text.trim().isNotEmpty) {
                        updatedPatient['contact'] = contactController.text
                            .trim();
                      }
                      if (notesController.text.trim().isNotEmpty) {
                        updatedPatient['notes'] = notesController.text.trim();
                      }
                      _allPatients[index] = updatedPatient;
                    });

                    if (!context.mounted) return;
                    Navigator.pop(context);
                    showCenterSnackBar(
                      context,
                      'Patient updated successfully!',
                    );
                  } catch (e) {
                    if (!context.mounted) return;
                    showCenterSnackBar(
                      context,
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

  Widget _buildEditField(
    String label,
    TextEditingController controller,
    IconData icon, {
    int maxLines = 1,
    String? hintText,
  }) {
    return TextField(
      controller: controller,
      maxLines: maxLines,
      decoration: InputDecoration(
        labelText: label,
        hintText: hintText,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
        prefixIcon: Icon(icon, size: 18, color: Colors.grey),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 12,
          vertical: 10,
        ),
      ),
    );
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
          _buildDateFilterBar(),
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
                          onPressed: _loadPatients,
                          child: const Text('Retry'),
                        ),
                      ],
                    ),
                  )
                : _filteredPatients.isEmpty
                ? _buildEmptyState()
                : RefreshIndicator(
                    onRefresh: _loadPatients,
                    child: ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _filteredPatients.length,
                      itemBuilder: (context, index) {
                        final patient = _filteredPatients[index];
                        return _buildPatientCard(patient);
                      },
                    ),
                  ),
          ),
        ],
      ),
      bottomNavigationBar: _buildBottomNav(),
    );
  }

  // Date Filter Bar
  Widget _buildDateFilterBar() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      color: Colors.white,
      child: Row(
        children: [
          IconButton(
            onPressed: _selectDateRange,
            icon: const Icon(Icons.calendar_today, color: StaffColors.primary),
            tooltip: 'Select Date Range',
          ),
          Expanded(
            child: GestureDetector(
              onTap: _selectDateRange,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 8,
                ),
                decoration: BoxDecoration(
                  color: Colors.grey.shade50,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.grey.shade300),
                ),
                child: Row(
                  children: [
                    if (_isDateFilterActive &&
                        _startDate != null &&
                        _endDate != null)
                      Expanded(
                        child: Row(
                          children: [
                            const Icon(
                              Icons.event,
                              size: 16,
                              color: StaffColors.primary,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              '${_formatDateShort(_startDate!)} - ${_formatDateShort(_endDate!)}',
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w500,
                                color: StaffColors.primary,
                              ),
                            ),
                          ],
                        ),
                      )
                    else
                      const Expanded(
                        child: Text(
                          'Select date range to filter patients',
                          style: TextStyle(fontSize: 13, color: Colors.grey),
                        ),
                      ),
                    if (_isDateFilterActive)
                      IconButton(
                        onPressed: _clearDateFilter,
                        icon: const Icon(
                          Icons.close,
                          size: 18,
                          color: Colors.red,
                        ),
                        padding: EdgeInsets.zero,
                        constraints: const BoxConstraints(),
                      ),
                  ],
                ),
              ),
            ),
          ),
          // Download / Export
          Container(
            margin: const EdgeInsets.only(left: 8),
            child: ElevatedButton.icon(
              onPressed: _showExportSheet,
              icon: const Icon(Icons.download, size: 16),
              label: const Text('Download'),
              style: ElevatedButton.styleFrom(
                backgroundColor: StaffColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 8,
                ),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20),
                ),
              ),
            ),
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
          // ❌ REMOVED Notifications from drawer
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

  // Header
  Widget _buildHeader() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      color: Colors.white,
      child: Row(
        children: [
          const Icon(Icons.people, color: StaffColors.primary, size: 28),
          const SizedBox(width: 12),
          const Text(
            'Patients',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.black87, //newc
            ),
          ),
        ],
      ),
    );
  }

  // Search and Filter
  Widget _buildSearchAndFilter() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      color: Colors.white,
      child: Row(
        children: [
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
                  hintText: 'Search patients...',
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
              items: _departmentList.map<DropdownMenuItem<String>>((
                String value,
              ) {
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
    String message = 'No patients found';
    if (_isDateFilterActive && _startDate != null && _endDate != null) {
      message =
          'No patients registered from ${_formatDateShort(_startDate!)} to ${_formatDateShort(_endDate!)}';
    }
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.people_outline, size: 60, color: Colors.grey.shade400),
          const SizedBox(height: 12),
          Text(
            message,
            style: TextStyle(fontSize: 16, color: Colors.grey.shade500),
          ),
          if (_isDateFilterActive)
            TextButton(
              onPressed: _clearDateFilter,
              child: const Text('Clear date filter'),
            ),
        ],
      ),
    );
  }

  // Patient Card
  Widget _buildPatientCard(Map<String, dynamic> patient) {
    final age = _calculateAge(patient['birthday']);
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
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: StaffColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Center(
                  child: Text(
                    patient['name'][0],
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: StaffColors.primary,
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
                      patient['name'],
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.black87, //newc
                      ),
                    ),
                    Row(
                      children: [
                        Text(
                          patient['id'],
                          style: const TextStyle(
                            fontSize: 12,
                            color: Colors.grey,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          width: 4,
                          height: 4,
                          decoration: const BoxDecoration(
                            color: Colors.grey,
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          patient['department'],
                          style: const TextStyle(
                            fontSize: 12,
                            color: Colors.grey,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 12,
            runSpacing: 4,
            children: [
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(
                    Icons.medical_services,
                    size: 14,
                    color: Colors.grey,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    patient['doctor'],
                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                ],
              ),
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.cake, size: 14, color: Colors.grey),
                  const SizedBox(width: 4),
                  Text(
                    _formatDate(patient['birthday']),
                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                  const SizedBox(width: 4),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 6,
                      vertical: 2,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.blue.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      '$age yrs',
                      style: const TextStyle(
                        fontSize: 10,
                        color: Colors.blue,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ],
              ),
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.phone, size: 14, color: Colors.grey),
                  const SizedBox(width: 4),
                  Text(
                    patient['contact'],
                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                ],
              ),
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(
                    Icons.calendar_today,
                    size: 12,
                    color: Colors.grey,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    'Reg: ${_formatDate(patient['dateRegistered'])}',
                    style: const TextStyle(fontSize: 11, color: Colors.grey),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 6),
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.grey.shade50,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              patient['notes'],
              style: const TextStyle(fontSize: 12, color: Colors.black87),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _viewPatient(patient),
                  icon: const Icon(Icons.visibility, size: 16),
                  label: const Text('View'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.blue.withValues(alpha: 0.1),
                    foregroundColor: Colors.blue,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                      side: BorderSide(
                        color: Colors.blue.withValues(alpha: 0.3),
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _editPatient(patient),
                  icon: const Icon(Icons.edit, size: 16),
                  label: const Text('Edit'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: StaffColors.primary.withValues(
                      alpha: 0.05,
                    ),
                    foregroundColor: StaffColors.primary,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                      side: BorderSide(
                        color: StaffColors.primary.withValues(alpha: 0.2),
                      ),
                    ),
                  ),
                ),
              ),
            ],
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
        currentIndex: 4,
        onTap: (index) {
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
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (context) => const DoctorsPage()),
                (route) => false,
              );
              break;
            case 4:
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
