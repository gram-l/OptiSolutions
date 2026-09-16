import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import '../main.dart' show appMenuItems;
import 'services/doctor_service.dart';
import 'center_snackbar.dart';

// ─────────────────────────────────────────────────────────────
//  DATA MODEL
// ─────────────────────────────────────────────────────────────
class DoctorModel {
  final int    id;
  final String name;
  final String specialty;
  final String schedule; // display-only string from the backend
  final List<_ScheduleSession> scheduleSessions; // structured, editable data
  final String description;
  final String phone;
  final bool   isActive;

  const DoctorModel({
    required this.id,
    required this.name,
    required this.specialty,
    required this.schedule,
    required this.scheduleSessions,
    required this.description,
    required this.phone,
    required this.isActive,
  });

  // Parses the raw shape returned by GET /api/doctors
  // (matches your Doctor model's actual DB columns)
  factory DoctorModel.fromJson(Map<String, dynamic> json) {
    final rawSessions = (json['schedule_sessions'] as List?) ?? [];
    final sessions = rawSessions
        .whereType<Map>()
        .map((s) {
          final start = _parseHHmm(s['start_time']?.toString());
          final end = _parseHHmm(s['end_time']?.toString());
          if (start == null || end == null) return null;
          return _ScheduleSession(day: s['day']?.toString() ?? '', start: start, end: end);
        })
        .whereType<_ScheduleSession>()
        .toList();

    return DoctorModel(
      id: json['doctor_id'] is int
          ? json['doctor_id']
          : int.tryParse(json['doctor_id'].toString()) ?? 0,
      name: json['doctor_name'] ?? '',
      specialty: json['specialty'] ?? '',
      schedule: json['schedule'] ?? '',
      scheduleSessions: sessions,
      description: json['description'] ?? '',
      phone: json['contact_number'] ?? '',
      isActive: (json['status'] ?? '') == 'Active',
    );
  }
}

// Parses "HH:mm" or "HH:mm:ss" into a TimeOfDay. Returns null if unparseable.
TimeOfDay? _parseHHmm(String? raw) {
  if (raw == null) return null;
  final parts = raw.split(':');
  if (parts.length < 2) return null;
  final hour = int.tryParse(parts[0]);
  final minute = int.tryParse(parts[1]);
  if (hour == null || minute == null) return null;
  return TimeOfDay(hour: hour, minute: minute);
}

// specialty → accent color
const _specialtyColors = <String, Color>{
  'Ophthalmology': Color(0xFF1565C0),
  'Pediatrics':    Color(0xFF2E7D32),
  'ENT':           Color(0xFF00838F),
  'Cardiology':    Color(0xFFB71C1C),
  'Dermatology':   Color(0xFF6A1B9A),
};

// ─────────────────────────────────────────────────────────────
//  SCHEDULE PICKER MODEL + HELPERS
//  Schedule is a flat list of "sessions" (day + start + end), so a doctor
//  can have multiple sessions on the same day (e.g. a morning shift and
//  an afternoon shift), each independently editable/removable.
// ─────────────────────────────────────────────────────────────
const List<String> _weekdays = [
  'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
];

class _ScheduleSession {
  String day;
  TimeOfDay start;
  TimeOfDay end;
  _ScheduleSession({required this.day, required this.start, required this.end});
}

// 24-hour "HH:mm" — used for storage/serialization only.
String _fmtTime(TimeOfDay t) =>
    '${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}';

// 12-hour "h:mm am/pm" — used for on-screen display.
String _fmtTime12(TimeOfDay t) {
  final period = t.period == DayPeriod.am ? 'am' : 'pm';
  final hour = t.hourOfPeriod == 0 ? 12 : t.hourOfPeriod;
  return '$hour:${t.minute.toString().padLeft(2, '0')} $period';
}

// Minutes-since-midnight, used for time comparisons/overlap checks below.
int _minutesOf(TimeOfDay t) => t.hour * 60 + t.minute;

// Two sessions overlap if they're on the same day and their time ranges
// intersect. Sessions are treated as same-day (no overnight wraparound).
bool _sessionsOverlap(_ScheduleSession a, _ScheduleSession b) {
  if (a.day != b.day) return false;
  final aStart = _minutesOf(a.start);
  final aEnd = _minutesOf(a.end);
  final bStart = _minutesOf(b.start);
  final bEnd = _minutesOf(b.end);
  return aStart < bEnd && bStart < aEnd;
}

// Parses e.g. "Monday 08:00-22:00; Tuesday 13:00-17:00" — RETIRED.
// Schedule now round-trips as structured JSON (schedule_sessions) between
// the app and the API, so no string parsing/serialization happens here
// anymore. Kept only as _fmtTime/_fmtTime12 helpers below for display and
// for building the outgoing schedule_sessions payload.

// Small picker dialog: choose a day + start/end time, then append the
// resulting session to the list (via setModalState so the parent dialog
// rebuilds with the grouped display).
//
// Validates, before adding:
//  - end time must be after start time
//  - the new session must not overlap any existing session on the same day
Future<void> _openAddSessionDialog(
  BuildContext context,
  List<_ScheduleSession> sessions,
  StateSetter setModalState,
) async {
  String day = 'Monday';
  TimeOfDay start = const TimeOfDay(hour: 8, minute: 0);
  TimeOfDay end = const TimeOfDay(hour: 17, minute: 0);
  String? sessionError;

  await showDialog(
    context: context,
    builder: (dialogCtx) => StatefulBuilder(
      builder: (dialogCtx, setDialogState) => AlertDialog(
        title: const Text('Add Session'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            DropdownButtonFormField<String>(
              initialValue: day,
              decoration: const InputDecoration(labelText: 'Day'),
              items: _weekdays
                  .map((d) => DropdownMenuItem(value: d, child: Text(d)))
                  .toList(),
              onChanged: (v) => setDialogState(() {
                day = v!;
                sessionError = null;
              }),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _TimeField(
                    label: 'Start Time',
                    time: start,
                    onChanged: (t) => setDialogState(() {
                      start = t;
                      sessionError = null;
                    }),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _TimeField(
                    label: 'End Time',
                    time: end,
                    onChanged: (t) => setDialogState(() {
                      end = t;
                      sessionError = null;
                    }),
                  ),
                ),
              ],
            ),
            if (sessionError != null) ...[
              const SizedBox(height: 10),
              Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  sessionError!,
                  style: const TextStyle(color: AppColors.deleteRed, fontSize: 12.5),
                ),
              ),
            ],
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogCtx),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              if (_minutesOf(end) <= _minutesOf(start)) {
                setDialogState(() => sessionError = 'End time must be after start time.');
                return;
              }

              final candidate = _ScheduleSession(day: day, start: start, end: end);
              final overlaps = sessions.any((s) => _sessionsOverlap(s, candidate));
              if (overlaps) {
                setDialogState(
                  () => sessionError = 'This session overlaps with an existing session on $day.',
                );
                return;
              }

              setModalState(() {
                sessions.add(candidate);
              });
              Navigator.pop(dialogCtx);
            },
            child: const Text('Add'),
          ),
        ],
      ),
    ),
  );
}

// ─────────────────────────────────────────────────────────────
//  SCREEN
// ─────────────────────────────────────────────────────────────
class DoctorsScreen extends StatefulWidget {
  const DoctorsScreen({super.key});

  @override
  State<DoctorsScreen> createState() => _DoctorsScreenState();
}

class _DoctorsScreenState extends State<DoctorsScreen> {
  String _search = '';
  String _filter = 'All'; // All | Active | Inactive
  String _dayFilter = 'All Days'; // All Days | Monday..Sunday

  static const List<String> _dayFilterOptions = ['All Days', ..._weekdays];

  List<DoctorModel> _doctors = [];
  bool _isLoading = true;
  String? _errorMessage;

  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  @override
  void initState() {
    super.initState();
    _loadDoctors();
  }

  Future<void> _loadDoctors() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final result = await DoctorService.fetchDoctors();

    if (result['success'] == true) {
      final List<dynamic> data = result['doctors'] ?? [];
      setState(() {
        _doctors = data.map((d) => DoctorModel.fromJson(d)).toList();
        _isLoading = false;
      });
    } else {
      setState(() {
        _errorMessage = result['message'] ?? 'Failed to load doctors.';
        _isLoading = false;
      });
    }
  }

  List<DoctorModel> get _filtered => _doctors.where((d) {
        final q = _search.toLowerCase();
        final matchSearch = d.name.toLowerCase().contains(q) ||
            d.specialty.toLowerCase().contains(q);
        final matchFilter = _filter == 'All' ||
            (_filter == 'Active' ? d.isActive : !d.isActive);
        final matchDay = _dayFilter == 'All Days' ||
            d.scheduleSessions.any((s) => s.day == _dayFilter);
        return matchSearch && matchFilter && matchDay;
      }).toList();

  // ── Add / Edit modal ──
  Future<void> _openDoctorForm({DoctorModel? doctor}) async {
    final nameCtrl = TextEditingController(text: doctor?.name ?? '');
    final specialtyCtrl = TextEditingController(text: doctor?.specialty ?? '');
    // Clone so edits in this dialog don't mutate the original model's
    // list until the user actually saves.
    final sessions = (doctor?.scheduleSessions ?? <_ScheduleSession>[])
        .map((s) => _ScheduleSession(day: s.day, start: s.start, end: s.end))
        .toList();
    final descCtrl = TextEditingController(text: doctor?.description ?? '');
    final phoneCtrl = TextEditingController(text: doctor?.phone ?? '');
    final formKey = GlobalKey<FormState>();
    bool isSaving = false;

    await showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) => AlertDialog(
          title: Text(doctor == null ? 'Add New Doctor' : 'Edit Doctor Profile'),
          content: Form(
            key: formKey,
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  TextFormField(
                    controller: nameCtrl,
                    decoration: const InputDecoration(labelText: 'Full Name *'),
                    validator: (v) {
                      final val = v?.trim() ?? '';
                      if (val.isEmpty) return 'Required';
                      if (val.length < 2) return 'Name is too short.';
                      if (val.length > 100) return 'Name is too long (max 100 characters).';
                      return null;
                    },
                  ),
                  TextFormField(
                    controller: specialtyCtrl,
                    decoration: const InputDecoration(labelText: 'Specialty *'),
                    validator: (v) {
                      final val = v?.trim() ?? '';
                      if (val.isEmpty) return 'Required';
                      if (val.length > 60) return 'Specialty is too long (max 60 characters).';
                      return null;
                    },
                  ),
                  Align(
                    alignment: Alignment.centerLeft,
                    child: Padding(
                      padding: const EdgeInsets.only(top: 10, bottom: 6),
                      child: Text('Schedule *',
                          style: TextStyle(
                              fontSize: 12,
                              color: AppColors.textGrey,
                              fontWeight: FontWeight.w600)),
                    ),
                  ),
                  ..._weekdays
                      .where((day) => sessions.any((s) => s.day == day))
                      .map((day) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(day,
                              style: const TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.darkNavy)),
                          const SizedBox(height: 6),
                          ...List.generate(sessions.length, (i) => i)
                              .where((i) => sessions[i].day == day)
                              .map((i) {
                            final s = sessions[i];
                            return Container(
                              margin: const EdgeInsets.only(bottom: 6),
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF4F6FB),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      '${_fmtTime12(s.start)} - ${_fmtTime12(s.end)}',
                                      style: const TextStyle(fontSize: 13),
                                    ),
                                  ),
                                  IconButton(
                                    icon: const Icon(Icons.delete_outline_rounded,
                                        color: AppColors.deleteRed, size: 20),
                                    onPressed: () =>
                                        setModalState(() => sessions.removeAt(i)),
                                  ),
                                ],
                              ),
                            );
                          }),
                        ],
                      ),
                    );
                  }),
                  Align(
                    alignment: Alignment.centerLeft,
                    child: TextButton.icon(
                      onPressed: () =>
                          _openAddSessionDialog(ctx, sessions, setModalState),
                      icon: const Icon(Icons.add, size: 16),
                      label: const Text('Add session'),
                    ),
                  ),
                  const SizedBox(height: 4),
                  TextFormField(
                    controller: descCtrl,
                    decoration: const InputDecoration(labelText: 'Description'),
                    maxLines: 2,
                    maxLength: 500,
                    validator: (v) {
                      final val = v?.trim() ?? '';
                      if (val.length > 500) return 'Description is too long (max 500 characters).';
                      return null;
                    },
                  ),
                  TextFormField(
                    controller: phoneCtrl,
                    decoration: const InputDecoration(
                      labelText: 'Contact Number',
                      hintText: 'Digits only, e.g. 09171234567',
                    ),
                    keyboardType: TextInputType.phone,
                    validator: (v) {
                      final val = v?.trim() ?? '';
                      if (val.isEmpty) return null; // optional field
                      final digitsOnly = RegExp(r'^\+?[0-9]{7,15}$');
                      if (!digitsOnly.hasMatch(val)) {
                        return 'Enter a valid phone number (digits only, 7–15 digits).';
                      }
                      return null;
                    },
                  ),
                ],
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: isSaving ? null : () => Navigator.pop(ctx),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: isSaving
                  ? null
                  : () async {
                      if (!formKey.currentState!.validate()) return;

                      if (sessions.isEmpty) {
                        showCenterSnackBar(
                          ctx,
                          'Add at least one schedule session.',
                          isError: true,
                        );
                        return;
                      }

                      // Belt-and-suspenders: re-check for overlaps across the
                      // full saved list (guards against any edge case the
                      // per-add check above might have missed).
                      for (var i = 0; i < sessions.length; i++) {
                        for (var j = i + 1; j < sessions.length; j++) {
                          if (_sessionsOverlap(sessions[i], sessions[j])) {
                            showCenterSnackBar(
                              ctx,
                              'Sessions on ${sessions[i].day} overlap. Please fix before saving.',
                              isError: true,
                            );
                            return;
                          }
                        }
                      }

                      final trimmedName = nameCtrl.text.trim();
                      final isDuplicate = _doctors.any((d) =>
                          d.name.trim().toLowerCase() == trimmedName.toLowerCase() &&
                          (doctor == null || d.id != doctor.id));
                      if (isDuplicate) {
                        showCenterSnackBar(
                          ctx,
                          'A doctor named "$trimmedName" already exists.',
                          isError: true,
                        );
                        return;
                      }

                      final scheduleSessionsPayload = sessions
                          .map((s) => {
                                'day': s.day,
                                'start_time': _fmtTime(s.start),
                                'end_time': _fmtTime(s.end),
                              })
                          .toList();

                      setModalState(() => isSaving = true);

                      final result = doctor == null
                          ? await DoctorService.createDoctor(
                              name: trimmedName,
                              specialty: specialtyCtrl.text.trim(),
                              scheduleSessions: scheduleSessionsPayload,
                              description: descCtrl.text.trim(),
                              phone: phoneCtrl.text.trim(),
                            )
                          : await DoctorService.updateDoctor(
                              id: doctor.id,
                              name: trimmedName,
                              specialty: specialtyCtrl.text.trim(),
                              scheduleSessions: scheduleSessionsPayload,
                              description: descCtrl.text.trim(),
                              phone: phoneCtrl.text.trim(),
                            );

                      if (!ctx.mounted) return;

                      if (result['success'] == true) {
                        Navigator.pop(ctx);
                        _loadDoctors();
                        if (mounted) {
                          showCenterSnackBar(context, result['message'] ?? 'Saved.');
                        }
                      } else {
                        setModalState(() => isSaving = false);
                        showCenterSnackBar(
                          ctx,
                          result['message'] ?? 'Something went wrong.',
                          isError: true,
                        );
                      }
                    },
              child: isSaving
                  ? const SizedBox(
                      width: 16, height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Text('Save'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _toggleStatus(DoctorModel doctor) async {
    final result = await DoctorService.toggleDoctorStatus(doctor.id);
    if (!mounted) return;
    if (result['success'] == true) {
      _loadDoctors();
      showCenterSnackBar(context, result['message'] ?? 'Status updated.');
    } else {
      showCenterSnackBar(
        context,
        result['message'] ?? 'Could not update status.',
        isError: true,
      );
    }
  }

  Future<void> _removeDoctor(DoctorModel doctor) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Remove Doctor'),
        content: Text(
          'Are you sure you want to permanently remove ${doctor.name} from the system? This action cannot be undone.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Remove', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    final result = await DoctorService.deleteDoctor(doctor.id);
    if (!mounted) return;
    if (result['success'] == true) {
      _loadDoctors();
      showCenterSnackBar(context, result['message'] ?? 'Doctor removed.');
    } else {
      showCenterSnackBar(
        context,
        result['message'] ?? 'Could not remove doctor.',
        isError: true,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      backgroundColor: AppColors.background,
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/doctors',
        onItemTap: (route) => Navigator.pushNamed(context, route),
      ),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildHeader(),
            _buildFilterRow(),
            Expanded(child: _buildBody()),
          ],
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_errorMessage != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(_errorMessage!, style: const TextStyle(color: Colors.red)),
            const SizedBox(height: 8),
            ElevatedButton(onPressed: _loadDoctors, child: const Text('Retry')),
          ],
        ),
      );
    }
    if (_filtered.isEmpty) {
      return const Center(child: Text('No doctors found.'));
    }
    return RefreshIndicator(
      onRefresh: _loadDoctors,
      child: ListView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        itemCount: _filtered.length,
        itemBuilder: (_, i) {
          final doctor = _filtered[i];
          return _DoctorCard(
            doctor: doctor,
            onEdit: () => _openDoctorForm(doctor: doctor),
            onToggle: () => _toggleStatus(doctor),
            onRemove: () => _removeDoctor(doctor),
          );
        },
      ),
    );
  }

  // ── Merged header: hamburger + icon box + title + subtitle ──
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
                  Icons.medical_services_outlined,
                  size: 15,
                  color: AppColors.iconColor,
                ),
              ),
              const SizedBox(width: 10),
              const Text('Doctors',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppColors.darkNavy,
                  )),
            ],
          ),
          const Padding(
            padding: EdgeInsets.only(left: 48, top: 4),
            child: Text('Manage physician profiles',
                style: TextStyle(color: AppColors.textGrey, fontSize: 13)),
          ),
        ],
      ),
    );
  }

  // ── Search + filter ──
  Widget _buildFilterRow() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: _SearchField(
                  hint: 'Search doctors...',
                  onChanged: (v) => setState(() => _search = v),
                ),
              ),
              const SizedBox(width: 6),
              _AddButton(onTap: () => _openDoctorForm()),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _DropdownChip(
                  value: _filter,
                  items: const ['All', 'Active', 'Inactive'],
                  onChanged: (v) => setState(() => _filter = v!),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _DropdownChip(
                  value: _dayFilter,
                  items: _dayFilterOptions,
                  onChanged: (v) => setState(() => _dayFilter = v!),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  DOCTOR CARD
// ─────────────────────────────────────────────────────────────
class _DoctorCard extends StatelessWidget {
  final DoctorModel doctor;
  final VoidCallback onEdit;
  final VoidCallback onToggle;
  final VoidCallback onRemove;

  const _DoctorCard({
    required this.doctor,
    required this.onEdit,
    required this.onToggle,
    required this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    final accentColor = _specialtyColors[doctor.specialty] ?? AppColors.primary;

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
            Row(
              children: [
                Expanded(
                  child: Text(doctor.name,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14.5)),
                ),
                _StatusBadge(active: doctor.isActive),
              ],
            ),
            Text(doctor.specialty,
                style: TextStyle(color: accentColor, fontWeight: FontWeight.w600, fontSize: 12.5)),
            const SizedBox(height: 8),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Padding(
                  padding: EdgeInsets.only(top: 1),
                  child: Icon(Icons.access_time_rounded, size: 13, color: AppColors.textGrey),
                ),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(doctor.schedule,
                      style: const TextStyle(fontSize: 11.5, color: AppColors.textGrey)),
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
              child: Text(doctor.description,
                  style: const TextStyle(fontSize: 12, color: AppColors.textDark, height: 1.45)),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.phone_outlined, size: 13, color: AppColors.textGrey),
                const SizedBox(width: 4),
                Text(doctor.phone, style: const TextStyle(fontSize: 11.5, color: AppColors.textGrey)),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                _ActionBtn(
                    label: 'Edit',
                    icon: Icons.edit_outlined,
                    color: AppColors.editBlue,
                    onTap: doctor.isActive ? onEdit : null),
                const SizedBox(width: 6),
                doctor.isActive
                    ? _ActionBtn(
                        label: 'Deactivate',
                        icon: Icons.block_outlined,
                        color: AppColors.deactivateOrange,
                        onTap: onToggle)
                    : _ActionBtn(
                        label: 'Activate',
                        icon: Icons.check_circle_outline_rounded,
                        color: AppColors.activateGreen,
                        onTap: onToggle),
                const SizedBox(width: 6),
                _ActionBtn(
                    label: 'Remove', icon: Icons.delete_outline_rounded, color: AppColors.deleteRed, onTap: onRemove),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  REUSABLE WIDGETS
// ─────────────────────────────────────────────────────────────
class _StatusBadge extends StatelessWidget {
  final bool active;
  const _StatusBadge({required this.active});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: active ? const Color(0xFFE8F5E9) : const Color(0xFFF5F5F5),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        active ? 'ACTIVE' : 'INACTIVE',
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.bold,
          letterSpacing: 0.4,
          color: active ? AppColors.activeGreen : AppColors.inactiveGrey,
        ),
      ),
    );
  }
}

class _ActionBtn extends StatelessWidget {
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback? onTap;
  const _ActionBtn({required this.label, required this.icon, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final bool disabled = onTap == null;
    final Color effectiveColor = disabled ? AppColors.textGrey : color;

    return Material(
      color: effectiveColor.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          child: Row(
            children: [
              Icon(icon, size: 13, color: effectiveColor),
              const SizedBox(width: 4),
              Text(label, style: TextStyle(fontSize: 12, color: effectiveColor, fontWeight: FontWeight.w600)),
            ],
          ),
        ),
      ),
    );
  }
}

class _TimeField extends StatelessWidget {
  final String label;
  final TimeOfDay time;
  final ValueChanged<TimeOfDay> onChanged;
  const _TimeField(
      {required this.label, required this.time, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(8),
      onTap: () async {
        final picked = await showTimePicker(context: context, initialTime: time);
        if (picked != null) onChanged(picked);
      },
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          isDense: true,
          border: const OutlineInputBorder(),
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(_fmtTime12(time), style: const TextStyle(fontSize: 13)),
            const Icon(Icons.keyboard_arrow_down_rounded,
                size: 16, color: AppColors.textGrey),
          ],
        ),
      ),
    );
  }
}

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
  const _DropdownChip({required this.value, required this.items, required this.onChanged});

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
          isExpanded: true,
          items: items.map((e) => DropdownMenuItem(value: e, child: Text(e, style: const TextStyle(fontSize: 12)))).toList(),
          onChanged: onChanged,
          style: const TextStyle(fontSize: 12, color: AppColors.textDark),
          icon: const Icon(Icons.keyboard_arrow_down_rounded, size: 16, color: AppColors.textGrey),
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