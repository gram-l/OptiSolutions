import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import '../main.dart' show appMenuItems;
import 'services/doctor_service.dart';

// ─────────────────────────────────────────────────────────────
//  DATA MODEL
// ─────────────────────────────────────────────────────────────
class DoctorModel {
  final int    id;
  final String name;
  final String specialty;
  final String schedule;
  final String description;
  final String phone;
  final bool   isActive;

  const DoctorModel({
    required this.id,
    required this.name,
    required this.specialty,
    required this.schedule,
    required this.description,
    required this.phone,
    required this.isActive,
  });

  // Parses the raw shape returned by GET /api/doctors
  // (matches your Doctor model's actual DB columns)
  factory DoctorModel.fromJson(Map<String, dynamic> json) {
    return DoctorModel(
      id: json['doctor_id'] is int
          ? json['doctor_id']
          : int.tryParse(json['doctor_id'].toString()) ?? 0,
      name: json['doctor_name'] ?? '',
      specialty: json['specialty'] ?? '',
      schedule: json['schedule'] ?? '',
      description: json['description'] ?? '',
      phone: json['contact_number'] ?? '',
      isActive: (json['status'] ?? '') == 'Active',
    );
  }
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
        return matchSearch && matchFilter;
      }).toList();

  // ── Add / Edit modal ──
  Future<void> _openDoctorForm({DoctorModel? doctor}) async {
    final nameCtrl = TextEditingController(text: doctor?.name ?? '');
    final specialtyCtrl = TextEditingController(text: doctor?.specialty ?? '');
    final scheduleCtrl = TextEditingController(text: doctor?.schedule ?? '');
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
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  TextFormField(
                    controller: specialtyCtrl,
                    decoration: const InputDecoration(labelText: 'Specialty *'),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  TextFormField(
                    controller: scheduleCtrl,
                    decoration: const InputDecoration(labelText: 'Schedule *'),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  TextFormField(
                    controller: descCtrl,
                    decoration: const InputDecoration(labelText: 'Description'),
                    maxLines: 2,
                  ),
                  TextFormField(
                    controller: phoneCtrl,
                    decoration: const InputDecoration(labelText: 'Contact Number'),
                    keyboardType: TextInputType.phone,
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
                      setModalState(() => isSaving = true);

                      final result = doctor == null
                          ? await DoctorService.createDoctor(
                              name: nameCtrl.text.trim(),
                              specialty: specialtyCtrl.text.trim(),
                              schedule: scheduleCtrl.text.trim(),
                              description: descCtrl.text.trim(),
                              phone: phoneCtrl.text.trim(),
                            )
                          : await DoctorService.updateDoctor(
                              id: doctor.id,
                              name: nameCtrl.text.trim(),
                              specialty: specialtyCtrl.text.trim(),
                              schedule: scheduleCtrl.text.trim(),
                              description: descCtrl.text.trim(),
                              phone: phoneCtrl.text.trim(),
                            );

                      if (!ctx.mounted) return;

                      if (result['success'] == true) {
                        Navigator.pop(ctx);
                        _loadDoctors();
                        if (mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text(result['message'] ?? 'Saved.')),
                          );
                        }
                      } else {
                        setModalState(() => isSaving = false);
                        ScaffoldMessenger.of(ctx).showSnackBar(
                          SnackBar(content: Text(result['message'] ?? 'Something went wrong.')),
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
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message'] ?? 'Status updated.')),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message'] ?? 'Could not update status.')),
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
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message'] ?? 'Doctor removed.')),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message'] ?? 'Could not remove doctor.')),
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
      child: Row(
        children: [
          Expanded(
            child: _SearchField(
              hint: 'Search doctors...',
              onChanged: (v) => setState(() => _search = v),
            ),
          ),
          const SizedBox(width: 8),
          _DropdownChip(
            value: _filter,
            items: const ['All', 'Active', 'Inactive'],
            onChanged: (v) => setState(() => _filter = v!),
          ),
          const SizedBox(width: 6),
          _AddButton(onTap: () => _openDoctorForm()),
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
              children: [
                const Icon(Icons.access_time_rounded, size: 13, color: AppColors.textGrey),
                const SizedBox(width: 4),
                Text(doctor.schedule, style: const TextStyle(fontSize: 11.5, color: AppColors.textGrey)),
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
                _ActionBtn(label: 'Edit', icon: Icons.edit_outlined, color: AppColors.editBlue, onTap: onEdit),
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
  final VoidCallback onTap;
  const _ActionBtn({required this.label, required this.icon, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: color.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          child: Row(
            children: [
              Icon(icon, size: 13, color: color),
              const SizedBox(width: 4),
              Text(label, style: TextStyle(fontSize: 12, color: color, fontWeight: FontWeight.w600)),
            ],
          ),
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
          prefixIcon: const Icon(Icons.search, size: 16, color: AppColors.textGrey),
          hintText: hint,
          hintStyle: const TextStyle(color: AppColors.textGrey, fontSize: 13),
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