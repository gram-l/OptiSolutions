// main.dart
import 'package:flutter/material.dart';
import 'admin_mobile/colors.dart';
import 'admin_mobile/side_panel.dart';
import 'login.dart';
import 'admin_mobile/dashboard.dart';
import 'admin_mobile/appointments.dart';
import 'admin_mobile/doctors.dart';
import 'admin_mobile/patients.dart';
import 'admin_mobile/chatbot.dart';
import 'admin_mobile/feedback.dart';
import 'admin_mobile/user_management.dart';
import 'admin_mobile/settings.dart';
import 'admin_mobile/notifications.dart';
import 'admin_mobile/profile.dart';

// Staff-side screens. No alias needed — class names here (AppointmentsPage,
// Dashboard, DoctorsPage, etc.) don't collide with the admin_mobile classes
// above (AppointmentsScreen, DashboardScreen, etc.).
import 'staff_mobile/screens/appointments.dart';
import 'staff_mobile/screens/dashboard.dart';
import 'staff_mobile/screens/doctors.dart';
import 'staff_mobile/screens/help.dart';
import 'staff_mobile/screens/inquiries.dart';
import 'staff_mobile/screens/notifications.dart' as staffnotif;
import 'staff_mobile/screens/patients.dart';
import 'staff_mobile/screens/profile.dart';
import 'staff_mobile/screens/settings.dart' as staffsettings;

void main() {
  runApp(const PolyclinicApp());
}

class PolyclinicApp extends StatelessWidget {
  const PolyclinicApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Polyclinic Dashboard',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        fontFamily: 'Roboto',
        scaffoldBackgroundColor: const Color(0xFFF4F6FB),
        primaryColor: AppColors.primary,
        useMaterial3: true,
      ),
      initialRoute: '/',
      routes: {
        // ── Admin routes ──
        '/':                (_) => const LoginScreen(),
        '/reset':           (_) => const ResetPasswordScreen(),
        '/verify-otp':      (_) => const OtpScreen(),
        '/new-password':    (_) => const NewPasswordScreen(),
        '/dashboard':       (_) => const DashboardScreen(),
        '/chatbot':         (_) => const InquiriesScreen(),
        '/appointments':    (_) => const AppointmentsScreen(),
        '/doctors':         (_) => const DoctorsScreen(),
        '/patients':        (_) => const PatientRecordsScreen(),
        '/feedback':        (_) => const FeedbackScreen(),
        '/user_management': (_) => const UserManagementScreen(),
        '/settings':        (_) => const SettingsScreen(),
        '/notifications':   (_) => const NotificationsScreen(),
        '/profile':         (_) => const ProfileScreen(),

        // ── Staff routes ──
        // Note: InquiryChatPage needs specific arguments (inquiryId, patientId,
        // etc.) so it's opened directly via Navigator.push from inquiries.dart
        // rather than through a static named route here.
        '/staff/dashboard':     (_) => const Dashboard(),
        '/staff/appointments':  (_) => const AppointmentsPage(),
        '/staff/doctors':       (_) => const DoctorsPage(),
        '/staff/help':          (_) => const HelpPage(),
        '/staff/inquiries':     (_) => const InquiriesPage(),
        '/staff/notifications': (_) => const staffnotif.NotificationsPage(),
        '/staff/patients':      (_) => const PatientsPage(),
        '/staff/profile':       (_) => const ProfilePage(),
        '/staff/settings':      (_) => const staffsettings.SettingsPage(),
      },
      onUnknownRoute: (settings) => MaterialPageRoute(
        builder: (_) => const DashboardScreen(),
      ),
    );
  }
}

// ── Shared side-menu items used by every screen ──
const appMenuItems = <SideMenuItem>[
  SideMenuItem(icon: Icons.dashboard_rounded,          label: 'Dashboard',        route: '/dashboard'),
  SideMenuItem(icon: Icons.chat_bubble_outline_rounded,label: 'Chatbot Inquiries',route: '/chatbot'),
  SideMenuItem(icon: Icons.event_note_rounded,         label: 'Scheduled Visits',     route: '/appointments'),
  SideMenuItem(icon: Icons.medical_services_outlined,  label: 'Manage Doctors',   route: '/doctors'),
  SideMenuItem(icon: Icons.folder_shared_outlined,     label: 'Patient Records',  route: '/patients'),
  SideMenuItem(icon: Icons.star_border_rounded,        label: 'Patient Feedback', route: '/feedback'),
  SideMenuItem(icon: Icons.manage_accounts_outlined,   label: 'User Management',  route: '/user_management'),
  SideMenuItem(icon: Icons.settings_outlined,          label: 'Settings',         route: '/settings'),
];