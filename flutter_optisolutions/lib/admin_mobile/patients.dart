import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import '../main.dart' show appMenuItems;
// ─────────────────────────────────────────────────────────────
//  COLORS
// ─────────────────────────────────────────────────────────────

// specialty → accent color
const _specialtyColors = <String, Color>{
  'Ophthalmology': Color(0xFF1565C0),
  'Pediatrics':    Color(0xFF2E7D32),
  'ENT':           Color(0xFF00838F),
  'Cardiology':    Color(0xFFB71C1C),
  'Dermatology':   Color(0xFF6A1B9A),
};

// ─────────────────────────────────────────────────────────────
//  DATA MODEL
// ─────────────────────────────────────────────────────────────
class PatientModel {
  final String id;            // P-1001
  final String name;
  final String specialty;
  final String doctor;
  final int    age;
  final String phone;
  final String notes;

  const PatientModel({
    required this.id,
    required this.name,
    required this.specialty,
    required this.doctor,
    required this.age,
    required this.phone,
    required this.notes,
  });
}

const _patients = <PatientModel>[
  PatientModel(
    id: 'P-1001', name: 'Maria Santos',
    specialty: 'Ophthalmology', doctor: 'Dr. Maria Reyes',
    age: 34, phone: '09123456789',
    notes: 'Cataract surgery scheduled for June 15. No known allergies.',
  ),
  PatientModel(
    id: 'P-1002', name: 'John Dela Cruz',
    specialty: 'Pediatrics', doctor: 'Dr. Jose Mendoza',
    age: 5, phone: '09234567890',
    notes: 'Routine vaccination. Mild fever last week.',
  ),
  PatientModel(
    id: 'P-1003', name: 'Anna Rivera',
    specialty: 'ENT', doctor: 'Dr. Anna Garcia',
    age: 28, phone: '09345678901',
    notes: 'Chronic sinusitis. Prescribed antibiotics.',
  ),
  PatientModel(
    id: 'P-1004', name: 'Carlos Gomez',
    specialty: 'Cardiology', doctor: 'Dr. Carlos Santos',
    age: 58, phone: '09456789012',
    notes: 'Hypertension. Regular blood pressure monitoring.',
  ),
  PatientModel(
    id: 'P-1005', name: 'Elena Reyes',
    specialty: 'Dermatology', doctor: 'Dr. Elena Lopez',
    age: 41, phone: '09567890123',
    notes: 'Eczema flare-up. Topical steroid prescribed.',
  ),
];

// ─────────────────────────────────────────────────────────────
//  SCREEN
// ─────────────────────────────────────────────────────────────
class PatientRecordsScreen extends StatefulWidget {
  const PatientRecordsScreen({super.key});

  @override
  State<PatientRecordsScreen> createState() => _PatientRecordsScreenState();
}

class _PatientRecordsScreenState extends State<PatientRecordsScreen> {
  String _search     = '';
  String _deptFilter = 'All Depts';
  String _statusFilter = 'All';

  static const _depts = [
    'All Depts', 'Ophthalmology', 'Pediatrics', 'ENT', 'Cardiology', 'Dermatology',
  ];
  static const _statuses = ['All', 'Active', 'Inactive'];

  void _navigateTo(String route) {
    if (route != '/patients') Navigator.pushNamed(context, route);
  }

  List<PatientModel> get _filtered => _patients.where((p) {
        final q = _search.toLowerCase();
        final matchSearch = p.name.toLowerCase().contains(q) ||
            p.id.toLowerCase().contains(q) ||
            p.doctor.toLowerCase().contains(q);
        final matchDept = _deptFilter == 'All Depts' || p.specialty == _deptFilter;
        return matchSearch && matchDept;
      }).toList();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/patients',
        onItemTap: _navigateTo,
      ),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildTopBar(),
            _buildHeader(),
            _buildFilterRow(),
            _buildExportButton(),
            Expanded(
              child: ListView.builder(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
                itemCount: _filtered.length,
                itemBuilder: (_, i) => _PatientCard(patient: _filtered[i]),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ── Top bar ──
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
          const SizedBox(width: 4),
          Container(
            width: 34, height: 34,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(8),
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 4)],
            ),
            child: const _AppIcon(),
          ),
          const SizedBox(width: 8),
          const Text('Polyclinic',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const Spacer(),
          CircleAvatar(
            radius: 16,
            backgroundColor: AppColors.primary,
            child: const Text('DL',
                style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
          ),
          const SizedBox(width: 10),
          const Icon(Icons.logout_outlined, color: AppColors.textGrey, size: 20),
        ],
      ),
    );
  }

  // ── Page heading ──
  Widget _buildHeader() {
    return const Padding(
      padding: EdgeInsets.fromLTRB(16, 4, 16, 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.folder_shared_outlined, color: AppColors.primary, size: 24),
              SizedBox(width: 8),
              Text('Patients',
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            ],
          ),
          SizedBox(height: 2),
          Text('Manage patient records',
              style: TextStyle(color: AppColors.textGrey, fontSize: 12.5)),
        ],
      ),
    );
  }

  // ── Search + filters + Add ──
  Widget _buildFilterRow() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child: Row(
        children: [
          // Search
          Expanded(
            flex: 3,
            child: _SearchField(onChanged: (v) => setState(() => _search = v)),
          ),
          const SizedBox(width: 8),
          // Dept dropdown
          _DropdownBox(
            value: _deptFilter,
            items: _depts,
            onChanged: (v) => setState(() => _deptFilter = v!),
          ),
          const SizedBox(width: 6),
          // Status dropdown
          _DropdownBox(
            value: _statusFilter,
            items: _statuses,
            onChanged: (v) => setState(() => _statusFilter = v!),
          ),
          const SizedBox(width: 6),
          // Add button
          _AddButton(onTap: () => _showAddDialog(context)),
        ],
      ),
    );
  }

  // ── Export row ──
  Widget _buildExportButton() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 6),
      child: Align(
        alignment: Alignment.centerRight,
        child: Material(
          color: AppColors.primary,
          borderRadius: BorderRadius.circular(8),
          child: InkWell(
            borderRadius: BorderRadius.circular(8),
            onTap: () {},
            child: const Padding(
              padding: EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.download_rounded, color: Colors.white, size: 15),
                  SizedBox(width: 5),
                  Text('Export',
                      style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w600)),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  // ── Add Patient Dialog ──
  void _showAddDialog(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => const _AddPatientSheet(),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  PATIENT CARD
// ─────────────────────────────────────────────────────────────
class _PatientCard extends StatelessWidget {
  final PatientModel patient;
  const _PatientCard({required this.patient});

  @override
  Widget build(BuildContext context) {
    final accent = _specialtyColors[patient.specialty] ?? AppColors.primary;

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
            // ── Name ──
            Text(patient.name,
                style: const TextStyle(fontSize: 15.5, fontWeight: FontWeight.bold)),
            const SizedBox(height: 6),

            // ── ID + Specialty ──
            Row(
              children: [
                _IdChip(id: patient.id),
                const SizedBox(width: 8),
                _SpecialtyChip(label: patient.specialty, color: accent),
              ],
            ),
            const SizedBox(height: 8),

            // ── Doctor + Age + Phone ──
            Row(
              children: [
                const Icon(Icons.person_outline_rounded, size: 13, color: AppColors.textGrey),
                const SizedBox(width: 4),
                Flexible(
                  child: Text(patient.doctor,
                      style: const TextStyle(fontSize: 11.5, color: AppColors.textGrey)),
                ),
                const SizedBox(width: 10),
                const Icon(Icons.calendar_today_outlined, size: 12, color: AppColors.textGrey),
                const SizedBox(width: 3),
                Text('${patient.age} yrs',
                    style: const TextStyle(fontSize: 11.5, color: AppColors.textGrey)),
                const SizedBox(width: 10),
                const Icon(Icons.phone_outlined, size: 12, color: AppColors.textGrey),
                const SizedBox(width: 3),
                Text(patient.phone,
                    style: const TextStyle(fontSize: 11.5, color: AppColors.textGrey)),
              ],
            ),
            const SizedBox(height: 10),

            // ── Notes ──
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
              decoration: BoxDecoration(
                color: const Color(0xFFF4F6FB),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(patient.notes,
                  style: const TextStyle(fontSize: 12.5, color: AppColors.textDark, height: 1.4)),
            ),
            const SizedBox(height: 10),

            // ── Action buttons ──
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
                  onTap: () {},
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
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => _ViewPatientSheet(patient: p),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  VIEW PATIENT BOTTOM SHEET
// ─────────────────────────────────────────────────────────────
class _ViewPatientSheet extends StatelessWidget {
  final PatientModel patient;
  const _ViewPatientSheet({required this.patient});

  @override
  Widget build(BuildContext context) {
    final accent = _specialtyColors[patient.specialty] ?? AppColors.primary;
    return DraggableScrollableSheet(
      expand: false,
      initialChildSize: 0.55,
      maxChildSize: 0.85,
      builder: (_, ctrl) => Padding(
        padding: const EdgeInsets.all(20),
        child: ListView(
          controller: ctrl,
          children: [
            Center(
              child: Container(
                width: 40, height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFDDE1E8),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                CircleAvatar(
                  radius: 24,
                  backgroundColor: accent.withValues(alpha: 0.15),
                  child: Text(
                    patient.name.substring(0, 1),
                    style: TextStyle(color: accent, fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                ),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(patient.name,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    Text(patient.id,
                        style: const TextStyle(color: AppColors.textGrey, fontSize: 12)),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 18),
            _DetailRow(icon: Icons.local_hospital_outlined, label: 'Specialty', value: patient.specialty),
            _DetailRow(icon: Icons.person_outline_rounded, label: 'Doctor', value: patient.doctor),
            _DetailRow(icon: Icons.calendar_today_outlined, label: 'Age', value: '${patient.age} years old'),
            _DetailRow(icon: Icons.phone_outlined, label: 'Phone', value: patient.phone),
            const SizedBox(height: 12),
            const Text('Notes', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF4F6FB),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(patient.notes,
                  style: const TextStyle(fontSize: 13, height: 1.5)),
            ),
          ],
        ),
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
          Expanded(
            child: Text(value,
                style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12.5)),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  ADD PATIENT BOTTOM SHEET
// ─────────────────────────────────────────────────────────────
class _AddPatientSheet extends StatelessWidget {
  const _AddPatientSheet();

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
              decoration: BoxDecoration(
                color: const Color(0xFFDDE1E8),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 16),
          const Text('Add New Patient',
              style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold)),
          const SizedBox(height: 16),
          _SheetField(hint: 'Full Name', icon: Icons.person_outline_rounded),
          const SizedBox(height: 10),
          _SheetField(hint: 'Phone Number', icon: Icons.phone_outlined,
              keyboardType: TextInputType.phone),
          const SizedBox(height: 10),
          _SheetField(hint: 'Age', icon: Icons.calendar_today_outlined,
              keyboardType: TextInputType.number),
          const SizedBox(height: 10),
          _SheetField(hint: 'Notes / Diagnosis', icon: Icons.notes_outlined, maxLines: 3),
          const SizedBox(height: 18),
          SizedBox(
            width: double.infinity,
            height: 48,
            child: ElevatedButton.icon(
              onPressed: () => Navigator.pop(context),
              icon: const Icon(Icons.add, color: Colors.white, size: 18),
              label: const Text('Add Patient',
                  style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
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
  final TextInputType keyboardType;
  final int maxLines;
  const _SheetField({
    required this.hint,
    required this.icon,
    this.keyboardType = TextInputType.text,
    this.maxLines = 1,
  });

  @override
  Widget build(BuildContext context) {
    return TextField(
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
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
        ),
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
      decoration: BoxDecoration(
        color: const Color(0xFFF0F2F8),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(id,
          style: const TextStyle(
              fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.textGrey, letterSpacing: 0.3)),
    );
  }
}

class _SpecialtyChip extends StatelessWidget {
  final String label;
  final Color color;
  const _SpecialtyChip({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(label,
          style: TextStyle(
              fontSize: 11, fontWeight: FontWeight.w700, color: color, letterSpacing: 0.2)),
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
              Text(label,
                  style: TextStyle(fontSize: 12.5, color: color, fontWeight: FontWeight.w600)),
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
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: TextField(
        onChanged: onChanged,
        style: const TextStyle(fontSize: 13),
        decoration: const InputDecoration(
          prefixIcon: Icon(Icons.search, size: 16, color: AppColors.textGrey),
          hintText: 'Search patients...',
          hintStyle: TextStyle(color: AppColors.textGrey, fontSize: 12),
          border: InputBorder.none,
          contentPadding: EdgeInsets.symmetric(vertical: 10),
        ),
      ),
    );
  }
}

class _DropdownBox extends StatelessWidget {
  final String value;
  final List<String> items;
  final ValueChanged<String?> onChanged;
  const _DropdownBox({required this.value, required this.items, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 38,
      padding: const EdgeInsets.symmetric(horizontal: 6),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: value,
          items: items.map((e) =>
              DropdownMenuItem(value: e, child: Text(e, style: const TextStyle(fontSize: 11.5)))).toList(),
          onChanged: onChanged,
          icon: const Icon(Icons.keyboard_arrow_down_rounded, size: 16, color: AppColors.textGrey),
          style: const TextStyle(fontSize: 11.5, color: AppColors.textDark),
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
        decoration: BoxDecoration(
          color: AppColors.primary,
          borderRadius: BorderRadius.circular(10),
        ),
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

// ─────────────────────────────────────────────────────────────
//  APP ICON (4-quadrant)
// ─────────────────────────────────────────────────────────────
class _AppIcon extends StatelessWidget {
  const _AppIcon();

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(8),
      child: GridView.count(
        crossAxisCount: 2,
        padding: const EdgeInsets.all(5),
        mainAxisSpacing: 2,
        crossAxisSpacing: 2,
        physics: const NeverScrollableScrollPhysics(),
        children: const [
          Icon(Icons.favorite_outline,    color: Color(0xFFE53935), size: 11),
          Icon(Icons.medical_services,    color: Color(0xFF1565C0), size: 11),
          Icon(Icons.chat_bubble_outline, color: Color(0xFF43A047), size: 11),
          Icon(Icons.local_hospital,      color: Color(0xFFFF9800), size: 11),
        ],
      ),
    );
  }
}