import 'package:flutter/material.dart';
import 'screens/dashboard.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});
// main.dart
import 'package:flutter/material.dart';
import 'admin_mobile/colors.dart';
import 'admin_mobile/side_panel.dart';
import 'admin_mobile/login.dart';
import 'admin_mobile/dashboard.dart';
import 'admin_mobile/appointments.dart';
import 'admin_mobile/doctors.dart';
import 'admin_mobile/patients.dart';
import 'admin_mobile/chatbot.dart';
import 'admin_mobile/feedback.dart';
import 'admin_mobile/user_management.dart';
import 'admin_mobile/settings.dart';
import 'admin_mobile/notifications.dart';

void main() {
  runApp(const PolyclinicApp());
}

class PolyclinicApp extends StatelessWidget {
  const PolyclinicApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'PolyClinic',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        fontFamily: 'Roboto',
        useMaterial3: true,
      ),
      debugShowCheckedModeBanner: false,
      home: const Dashboard(),
    );
  }
}
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
