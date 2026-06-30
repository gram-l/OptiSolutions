// main.dart
import 'package:flutter/material.dart';
import 'colors.dart';
import 'side_panel.dart';
import 'login.dart';
import 'dashboard.dart';
import 'appointments.dart';
import 'doctors.dart';
import 'patients.dart';
import 'chatbot.dart';
import 'feedback.dart';
import 'user_management.dart';
import 'settings.dart';
import 'notifications.dart';

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
        '/':               (_) => const LoginScreen(),
        '/reset': (_) => const ResetPasswordScreen(),
        '/verify-otp': (_) => const OtpScreen(),
        '/new-password': (_) => const NewPasswordScreen(),
        '/dashboard':      (_) => const DashboardScreen(),
        '/chatbot':        (_) => const InquiriesScreen(),
        '/appointments':   (_) => const AppointmentsScreen(),
        '/doctors':        (_) => const DoctorsScreen(),
        '/patients':       (_) => const PatientRecordsScreen(),
        '/feedback':       (_) => const FeedbackScreen(),
        '/user_management': (_) => const UserManagementScreen(),
        '/settings':       (_) => const SettingsScreen(),
        '/notifications':  (_) => const NotificationsScreen(),
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
  SideMenuItem(icon: Icons.event_note_rounded,         label: 'Appointments',     route: '/appointments'),
  SideMenuItem(icon: Icons.medical_services_outlined,  label: 'Manage Doctors',   route: '/doctors'),
  SideMenuItem(icon: Icons.folder_shared_outlined,     label: 'Patient Records',  route: '/patients'),
  SideMenuItem(icon: Icons.star_border_rounded,        label: 'Patient Feedback', route: '/feedback'),
  SideMenuItem(icon: Icons.manage_accounts_outlined,   label: 'User Management',  route: '/user_management'),
  SideMenuItem(icon: Icons.settings_outlined,          label: 'Settings',         route: '/settings'),
];