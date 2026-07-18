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
import 'doctors.dart';
import 'patients.dart';
import 'profile.dart';
import 'notifications.dart';
import 'settings.dart';
import 'help.dart';
import '../widgets/notification_badge.dart';
import '../widgets/center_snackbar.dart';
import '../services/api_service.dart';

class AppointmentsPage extends StatefulWidget {
  const AppointmentsPage({super.key});

  @override
  State<AppointmentsPage> createState() => _AppointmentsPageState();
}

class _AppointmentsPageState extends State<AppointmentsPage> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  List<Map<String, dynamic>> _allAppointments = [];
  bool _isLoading = true;
  String? _error;

  // Date Range Filter
  DateTime? _startDate;
  DateTime? _endDate;
  bool _isDateFilterActive = false;

  // Logged-in staff name (shown in the drawer header)
  String _staffName = 'Staff';
  String _staffEmail = '';

  @override
  void initState() {
    super.initState();
    _loadStaffName();
    _fetchAppointments();
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

  Future<void> _fetchAppointments() async {
    try {
      setState(() {
        _isLoading = true;
        _error = null;
      });
      final data = await ApiService.get('/appointments');

      // Handle both list and paginated response
      final List list = data is List ? data : (data['data'] ?? []);

      setState(() {
        _allAppointments = list
            .map(
              (apt) => {
                // appointment_id ang primary key sa DB, hindi 'id'
                'id': 'APP-${apt['appointment_id']}',
                // 'patient' at 'doctor' ay plain String na sa response,
                // hindi na sila nested Map kaya walang ?['patient_name']
                'patientName': apt['patient'] ?? 'N/A',
                'doctor': apt['doctor'] ?? 'N/A',
                // walang department/service_type column sa DB, ginamit
                // na lang ang 'status' bilang filterable field
                'status': apt['status'] ?? 'N/A',
                'date': _formatDate(apt['date']),
              },
            )
            .toList();
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  String _formatDate(dynamic rawDate) {
    if (rawDate == null) return 'N/A';
    final str = rawDate.toString();
    // Kunin lang ang date part (YYYY-MM-DD), tanggalin ang time kung meron
    return str.split('T').first;
  }

  List<Map<String, dynamic>> get _filteredAppointments {
    List<Map<String, dynamic>> result = List.from(_allAppointments);

    // Filter by date range
    if (_isDateFilterActive && _startDate != null && _endDate != null) {
      result = result.where((app) {
        try {
          final appDate = DateTime.parse(app['date']);
          return appDate.isAfter(
                _startDate!.subtract(const Duration(days: 1)),
              ) &&
              appDate.isBefore(_endDate!.add(const Duration(days: 1)));
        } catch (e) {
          return false;
        }
      }).toList();
    }

    if (_searchQuery.isNotEmpty) {
      final query = _searchQuery.toLowerCase();
      result = result.where((app) {
        return app['patientName'].toLowerCase().contains(query) ||
            app['doctor'].toLowerCase().contains(query) ||
            app['status'].toLowerCase().contains(query) ||
            app['id'].toLowerCase().contains(query);
      }).toList();
    }

    return result;
  }

  // Show date range picker
  Future<void> _selectDateRange() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _startDate ?? DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
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
        lastDate: DateTime(2100),
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

  // ─────────────────────────────────────────────
  //  EXPORT (CSV / Excel / PDF)
  // ─────────────────────────────────────────────
  void _showExportSheet() {
    final appointmentsToExport = _filteredAppointments;

    if (appointmentsToExport.isEmpty) {
      showCenterSnackBar(context, 'No appointments to export', isError: true);
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
      ['Appointment ID', 'Patient', 'Doctor', 'Status', 'Date'],
    ];
    for (final a in _filteredAppointments) {
      rows.add([
        (a['id'] ?? '').toString(),
        (a['patientName'] ?? '').toString(),
        (a['doctor'] ?? '').toString(),
        (a['status'] ?? '').toString(),
        (a['date'] ?? '').toString(),
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
      final file = await _writeToDownloads('appointments.csv', csv.codeUnits);
      await Share.shareXFiles([
        XFile(file.path),
      ], text: 'Appointments list export');
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
      final sheet = workbook['Appointments'];
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
      final file = await _writeToDownloads('appointments.xlsx', bytes);
      await Share.shareXFiles([
        XFile(file.path),
      ], text: 'Appointments list export');
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
      final file = await _writeToDownloads('appointments.pdf', bytes);
      await Share.shareXFiles([
        XFile(file.path),
      ], text: 'Appointments list export');
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

  void _viewAppointment(Map<String, dynamic> appointment) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(appointment['patientName']),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('ID: ${appointment['id']}'),
            Text('Doctor: ${appointment['doctor']}'),
            Text('Status: ${appointment['status']}'),
            Text('Date: ${appointment['date']}'),
          ],
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      drawer: _buildDrawer(),
      appBar: _buildAppBar(),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.error, color: Colors.red, size: 60),
                  const SizedBox(height: 12),
                  Text(_error!, textAlign: TextAlign.center),
                  const SizedBox(height: 12),
                  ElevatedButton(
                    onPressed: _fetchAppointments,
                    child: const Text('Retry'),
                  ),
                ],
              ),
            )
          : Column(
              children: [
                _buildHeader(),
                _buildSearchAndFilter(),
                Expanded(
                  child: _filteredAppointments.isEmpty
                      ? _buildEmptyState()
                      : RefreshIndicator(
                          onRefresh: _fetchAppointments,
                          child: ListView.builder(
                            padding: const EdgeInsets.all(16),
                            itemCount: _filteredAppointments.length,
                            itemBuilder: (context, index) {
                              return _buildAppointmentCard(
                                _filteredAppointments[index],
                              );
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
          Image.asset('assets/PCLOGO.png', width: 35, height: 35),
          const SizedBox(width: 12),
          const Text(
            'Polyclinic',
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 22,
              color: StaffColors.primary,
            ),
          ),
        ],
      ),
      backgroundColor: Colors.white,
      foregroundColor: StaffColors.primary,
      elevation: 2,
      actions: [
        NotificationBadge(
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const NotificationsPage()),
          ),
        ),
        IconButton(
          icon: const Icon(Icons.logout, color: StaffColors.primary),
          onPressed: () => _showLogoutDialog(context),
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
              MaterialPageRoute(builder: (_) => const ProfilePage()),
            );
          }),
          _buildDrawerItem(Icons.settings, 'Settings', false, () {
            Navigator.pop(context);
            Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const SettingsPage()),
            );
          }),
          _buildDrawerItem(Icons.help, 'Help', false, () {
            Navigator.pop(context);
            Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const HelpPage()),
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
      onTap: onTap,
    );
  }

  Widget _buildHeader() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      color: Colors.white,
      child: Row(
        children: [
          const Icon(
            Icons.calendar_today,
            color: StaffColors.primary,
            size: 28,
          ),
          const SizedBox(width: 12),
          const Text(
            'Scheduled Visits',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: StaffColors.primary,
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

  Widget _buildSearchAndFilter() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      color: Colors.white,
      child: Column(
        children: [
          Row(
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
                      hintText: 'Search appointments...',
                      hintStyle: const TextStyle(
                        fontSize: 13,
                        color: Colors.grey,
                      ),
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
              // Date range filter (calendar)
              Container(
                height: 40,
                width: 40,
                decoration: BoxDecoration(
                  color: _isDateFilterActive
                      ? StaffColors.primary
                      : Colors.grey.shade100,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: IconButton(
                  onPressed: _selectDateRange,
                  tooltip: 'Filter by date range',
                  icon: Icon(
                    Icons.calendar_today,
                    size: 18,
                    color: _isDateFilterActive
                        ? Colors.white
                        : StaffColors.primary,
                  ),
                ),
              ),
            ],
          ),
          if (_isDateFilterActive && _startDate != null && _endDate != null)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Row(
                children: [
                  const Icon(Icons.event, size: 16, color: StaffColors.primary),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      '${_formatDateShort(_startDate!)} - ${_formatDateShort(_endDate!)}',
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                        color: StaffColors.primary,
                      ),
                    ),
                  ),
                  GestureDetector(
                    onTap: _clearDateFilter,
                    child: const Icon(Icons.close, size: 18, color: Colors.red),
                  ),
                ],
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
          Icon(Icons.calendar_today, size: 60, color: Colors.grey.shade400),
          const SizedBox(height: 12),
          Text(
            'No appointments found',
            style: TextStyle(fontSize: 16, color: Colors.grey.shade500),
          ),
          const SizedBox(height: 12),
          ElevatedButton(
            onPressed: _fetchAppointments,
            child: const Text('Refresh'),
          ),
        ],
      ),
    );
  }

  Widget _buildAppointmentCard(Map<String, dynamic> appointment) {
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
          Text(
            appointment['patientName'],
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: StaffColors.primary,
            ),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              const Icon(Icons.medical_services, size: 16, color: Colors.grey),
              const SizedBox(width: 4),
              Text(
                appointment['doctor'],
                style: const TextStyle(fontSize: 14, color: Colors.black87),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Row(
            children: [
              const Icon(Icons.calendar_today, size: 14, color: Colors.grey),
              const SizedBox(width: 4),
              Text(
                appointment['date'],
                style: const TextStyle(fontSize: 13, color: Colors.grey),
              ),
            ],
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () => _viewAppointment(appointment),
              icon: const Icon(Icons.visibility, size: 16),
              label: const Text(
                'View Details',
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: StaffColors.primary,
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(vertical: 10),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomNav() {
    return BottomNavigationBar(
      type: BottomNavigationBarType.fixed,
      backgroundColor: Colors.white,
      selectedItemColor: StaffColors.primary,
      unselectedItemColor: Colors.grey.shade400,
      currentIndex: 2,
      onTap: (index) {
        switch (index) {
          case 0:
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(builder: (_) => const Dashboard()),
              (r) => false,
            );
            break;
          case 1:
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(builder: (_) => const InquiriesPage()),
              (r) => false,
            );
            break;
          case 2:
            break;
          case 3:
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(builder: (_) => const DoctorsPage()),
              (r) => false,
            );
            break;
          case 4:
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(builder: (_) => const PatientsPage()),
              (r) => false,
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
          label: 'Scheduled Visits',
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
                MaterialPageRoute(builder: (_) => const LoginScreen()),
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
