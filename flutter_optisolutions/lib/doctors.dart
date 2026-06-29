import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import 'main.dart' show appMenuItems;
// ─────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────────────────────
//  DATA MODEL
// ─────────────────────────────────────────────────────────────
class DoctorModel {
  final String name;
  final String specialty;
  final String schedule;        // e.g. "Mon, Wed, Fri • 9AM-5PM"
  final String description;
  final String phone;
  final bool   isActive;

  const DoctorModel({
    required this.name,
    required this.specialty,
    required this.schedule,
    required this.description,
    required this.phone,
    required this.isActive,
  });
}

const _doctors = <DoctorModel>[
  DoctorModel(
    name: 'Dr. Maria Reyes',
    specialty: 'Ophthalmology',
    schedule: 'Mon, Wed, Fri  •  9AM–5PM',
    description:
        'Board-certified ophthalmologist with 15+ years in cataract surgery and LASIK.',
    phone: '09123458789',
    isActive: true,
  ),
  DoctorModel(
    name: 'Dr. Jose Mendoza',
    specialty: 'Pediatrics',
    schedule: 'Tue, Thu, Sat  •  10AM–6PM',
    description:
        'Pediatrician focused on child development and preventive care.',
    phone: '09234567890',
    isActive: true,
  ),
  DoctorModel(
    name: 'Dr. Anna Garcia',
    specialty: 'ENT',
    schedule: 'Mon, Tue, Wed  •  8AM–4PM',
    description:
        'Otolaryngologist specializing in sinus disorders and hearing loss.',
    phone: '09345678901',
    isActive: true,
  ),
  DoctorModel(
    name: 'Dr. Carlos Santos',
    specialty: 'Cardiology',
    schedule: 'Wed, Thu, Fri  •  1PM–7PM',
    description:
        'Interventional cardiologist with expertise in hypertension and heart failure.',
    phone: '09456789012',
    isActive: false,
  ),
  DoctorModel(
    name: 'Dr. Elena Lopez',
    specialty: 'Dermatology',
    schedule: 'Mon, Fri  •  9AM–3PM',
    description:
        'Dermatologist offering medical and cosmetic dermatology services.',
    phone: '09567890123',
    isActive: true,
  ),
];

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
  String _search     = '';
  String _filter     = 'All';   // All | Active | Inactive

  List<DoctorModel> get _filtered => _doctors.where((d) {
        final q = _search.toLowerCase();
        final matchSearch = d.name.toLowerCase().contains(q) ||
            d.specialty.toLowerCase().contains(q);
        final matchFilter = _filter == 'All' ||
            (_filter == 'Active' ? d.isActive : !d.isActive);
        return matchSearch && matchFilter;
      }).toList();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F6FB),
      drawer: SidePanel(
        items: appMenuItems,
        currentRoute: '/doctors',
        onItemTap: (route) => Navigator.pushNamed(context, route),
      ),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildTopBar(),
            _buildHeader(),
            _buildFilterRow(),
            Expanded(
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                itemCount: _filtered.length,
                itemBuilder: (_, i) => _DoctorCard(doctor: _filtered[i]),
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
          const CircleAvatar(
            radius: 13,
            backgroundColor: Color(0xFFE3F2FD),
            child: Icon(Icons.local_hospital, color: AppColors.primary, size: 14),
          ),
          const SizedBox(width: 6),
          const Text('Polyclinic',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          const Spacer(),
          IconButton(
            icon: const Icon(Icons.notifications_none_rounded, color: AppColors.textDark, size: 22),
            onPressed: () => Navigator.pushNamed(context, '/notifications'),
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(),
          ),
          const SizedBox(width: 4),
          CircleAvatar(
            radius: 14,
            backgroundColor: AppColors.primary,
            child: const Text('DL',
                style: TextStyle(
                    color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
          ),
          const SizedBox(width: 8),
          const Icon(Icons.logout_outlined, color: AppColors.textGrey, size: 20),
        ],
      ),
    );
  }

  // ── Page heading ──
  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Back button
          GestureDetector(
            onTap: () => Navigator.maybePop(context),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.arrow_back_ios_new_rounded, size: 12, color: Colors.white),
                  SizedBox(width: 4),
                  Text('Dashboard',
                      style: TextStyle(color: Colors.white, fontSize: 12)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 10),
          const Row(
            children: [
              Icon(Icons.medical_services_outlined,
                  color: AppColors.primary, size: 22),
              SizedBox(width: 8),
              Text('Doctors',
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            ],
          ),
          const SizedBox(height: 2),
          const Text('Manage physician profiles',
              style: TextStyle(color: AppColors.textGrey, fontSize: 12.5)),
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
          _AddButton(onTap: () {}),
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
  const _DoctorCard({required this.doctor});

  @override
  Widget build(BuildContext context) {
    final accentColor =
        _specialtyColors[doctor.specialty] ?? AppColors.primary;

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
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
            // ── Name + status ──
            Row(
              children: [
                Expanded(
                  child: Text(
                    doctor.name,
                    style: const TextStyle(
                        fontWeight: FontWeight.bold, fontSize: 14.5),
                  ),
                ),
                _StatusBadge(active: doctor.isActive),
              ],
            ),
            // ── Specialty ──
            Text(
              doctor.specialty,
              style: TextStyle(
                  color: accentColor,
                  fontWeight: FontWeight.w600,
                  fontSize: 12.5),
            ),
            const SizedBox(height: 8),
            // ── Schedule ──
            Row(
              children: [
                const Icon(Icons.access_time_rounded,
                    size: 13, color: AppColors.textGrey),
                const SizedBox(width: 4),
                Text(doctor.schedule,
                    style: const TextStyle(
                        fontSize: 11.5, color: AppColors.textGrey)),
              ],
            ),
            const SizedBox(height: 8),
            // ── Description ──
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFF4F6FB),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                doctor.description,
                style: const TextStyle(fontSize: 12, color: AppColors.textDark, height: 1.45),
              ),
            ),
            const SizedBox(height: 8),
            // ── Phone ──
            Row(
              children: [
                const Icon(Icons.phone_outlined,
                    size: 13, color: AppColors.textGrey),
                const SizedBox(width: 4),
                Text(doctor.phone,
                    style: const TextStyle(
                        fontSize: 11.5, color: AppColors.textGrey)),
              ],
            ),
            const SizedBox(height: 10),
            // ── Action buttons ──
            Row(
              children: doctor.isActive
                  ? [
                      _ActionBtn(
                          label: 'Edit',
                          icon: Icons.edit_outlined,
                          color: AppColors.editBlue,
                          onTap: () {}),
                      const SizedBox(width: 6),
                      _ActionBtn(
                          label: 'Deactivate',
                          icon: Icons.block_outlined,
                          color: AppColors.deactivateOrange,
                          onTap: () {}),
                      const SizedBox(width: 6),
                      _ActionBtn(
                          label: 'Remove',
                          icon: Icons.delete_outline_rounded,
                          color: AppColors.deleteRed,
                          onTap: () {}),
                    ]
                  : [
                      _ActionBtn(
                          label: 'Edit',
                          icon: Icons.edit_outlined,
                          color: AppColors.editBlue,
                          onTap: () {}),
                      const SizedBox(width: 6),
                      _ActionBtn(
                          label: 'Activate',
                          icon: Icons.check_circle_outline_rounded,
                          color: AppColors.activateGreen,
                          onTap: () {}),
                      const SizedBox(width: 6),
                      _ActionBtn(
                          label: 'Remove',
                          icon: Icons.delete_outline_rounded,
                          color: AppColors.deleteRed,
                          onTap: () {}),
                    ],
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  REUSABLE WIDGETS (shared with user_management_screen.dart)
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
  const _ActionBtn(
      {required this.label,
      required this.icon,
      required this.color,
      required this.onTap});

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
              Text(label,
                  style: TextStyle(
                      fontSize: 12,
                      color: color,
                      fontWeight: FontWeight.w600)),
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
        border: Border.all(color: const Color(0xFFE0E0E0)),
      ),
      child: TextField(
        onChanged: onChanged,
        style: const TextStyle(fontSize: 13),
        decoration: InputDecoration(
          prefixIcon:
              const Icon(Icons.search, size: 16, color: AppColors.textGrey),
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
        border: Border.all(color: const Color(0xFFE0E0E0)),
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
            Text('Add',
                style: TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.bold)),
          ],
        ),
      ),
    );
  }
}