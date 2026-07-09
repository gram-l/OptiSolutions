import 'package:flutter/material.dart';


class HelpPage extends StatefulWidget {
  const HelpPage({super.key});

  @override
  State<HelpPage> createState() => _HelpPageState();
}

class _HelpPageState extends State<HelpPage> {
  // Expanded FAQ sections
  bool _faqExpanded1 = false;
  bool _faqExpanded2 = false;
  bool _faqExpanded3 = false;
  bool _faqExpanded4 = false;
  bool _faqExpanded5 = false;
  bool _faqExpanded6 = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,

      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1A237E)),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'Help & Support',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 20,
            color: Color(0xFF1A237E),
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 1,
        centerTitle: false,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout, color: Color(0xFF1A237E)),
            onPressed: () {
              _showLogoutDialog(context);
            },
            tooltip: 'Logout',
          ),
        ],
      ),

      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Welcome Section
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFF1A237E),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.help_outline, color: Colors.white, size: 28),
                      SizedBox(width: 12),
                      Text(
                        'How can we help you?',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Find answers to frequently asked questions, guides, and contact information.',
                    style: TextStyle(fontSize: 14, color: Colors.white70),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),

            // Section: FAQs
            _buildSectionHeader('Frequently Asked Questions'),
            const SizedBox(height: 8),

            // FAQ 1
            _buildFAQItem(
              context,
              'How do I view patient records?',
              'Go to the Patients tab from the bottom navigation. You can search for patients by name or ID. Click "View" to see full patient details including medical history, doctor, and notes.',
              _faqExpanded1,
              () {
                setState(() {
                  _faqExpanded1 = !_faqExpanded1;
                });
              },
            ),

            const SizedBox(height: 8),

            // FAQ 2
            _buildFAQItem(
              context,
              'How do I manage appointments?',
              'Navigate to the Appointments tab. You can approve, reschedule, or cancel appointments. Use the filter to view appointments by status (Pending, Approved, Cancelled).',
              _faqExpanded2,
              () {
                setState(() {
                  _faqExpanded2 = !_faqExpanded2;
                });
              },
            ),

            const SizedBox(height: 8),

            // FAQ 3
            _buildFAQItem(
              context,
              'How do I respond to inquiries?',
              'Go to the Inquiries tab and click on any inquiry to open the chat. You can type your reply and send it directly to the patient. The chatbot will assist with initial responses.',
              _faqExpanded3,
              () {
                setState(() {
                  _faqExpanded3 = !_faqExpanded3;
                });
              },
            ),

            const SizedBox(height: 8),

            // FAQ 4
            _buildFAQItem(
              context,
              'How do I update doctor availability?',
              'Go to the Doctors tab. Click the "Availability" button on a doctor\'s card to toggle between Available and Unavailable. You can also edit their schedule using "Edit Schedule".',
              _faqExpanded4,
              () {
                setState(() {
                  _faqExpanded4 = !_faqExpanded4;
                });
              },
            ),

            const SizedBox(height: 8),

            // FAQ 5
            _buildFAQItem(
              context,
              'How do I change my password?',
              'Go to Settings from the drawer menu. Under "Login Settings", click "Change Password". Enter your current password and new password, then click Update.',
              _faqExpanded5,
              () {
                setState(() {
                  _faqExpanded5 = !_faqExpanded5;
                });
              },
            ),

            const SizedBox(height: 8),

            // FAQ 6
            _buildFAQItem(
              context,
              'How do I contact support?',
              'You can contact us through the Contact Support section below. We will respond within 24 hours.',
              _faqExpanded6,
              () {
                setState(() {
                  _faqExpanded6 = !_faqExpanded6;
                });
              },
            ),

            const SizedBox(height: 20),

            // Section: Guide for Staff
            _buildSectionHeader('Guide for Staff'),
            const SizedBox(height: 8),

            Container(
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
                children: [
                  _buildGuideItem(
                    '1. Dashboard',
                    'View key metrics including appointments, inquiries, doctors, and patients.',
                    Icons.dashboard,
                    Colors.blue,
                  ),
                  _buildDivider(),
                  _buildGuideItem(
                    '2. Manage Inquiries',
                    'Respond to patient inquiries and chat with them directly.',
                    Icons.question_answer,
                    Colors.orange,
                  ),
                  _buildDivider(),
                  _buildGuideItem(
                    '3. Appointments',
                    'Approve, reschedule, or cancel patient appointments.',
                    Icons.calendar_today,
                    Colors.green,
                  ),
                  _buildDivider(),
                  _buildGuideItem(
                    '4. Doctor Management',
                    'Update doctor availability and edit their schedules.',
                    Icons.medical_services,
                    Colors.purple,
                  ),
                  _buildDivider(),
                  _buildGuideItem(
                    '5. Patient Records',
                    'View and edit patient information and medical notes.',
                    Icons.people,
                    Colors.teal,
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),

            //  Section: Contact Support
            _buildSectionHeader('Contact Support'),
            const SizedBox(height: 8),

            Container(
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
                children: [
                  // Email
                  _buildContactItem(
                    Icons.email,
                    'Email Us',
                    'support@polyclinic.com',
                    'We will respond within 24 hours.',
                  ),
                  _buildDivider(),
                  // Phone
                  _buildContactItem(
                    Icons.phone,
                    'Call Us',
                    '+63 (2) 8123-4567',
                    'Monday to Friday, 8:00 AM - 5:00 PM',
                  ),
                  _buildDivider(),
                  // Location
                  _buildContactItem(
                    Icons.location_on,
                    'Visit Us',
                    '123 Medical Center, Manila, Philippines',
                    'Open Monday to Saturday, 8:00 AM - 5:00 PM',
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Text(
      title,
      style: const TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.bold,
        color: Color(0xFF1A237E),
      ),
    );
  }

  Widget _buildFAQItem(
    BuildContext context,
    String question,
    String answer,
    bool isExpanded,
    VoidCallback onTap,
  ) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.05),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          ListTile(
            title: Text(
              question,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w500,
                color: Color(0xFF1A237E),
              ),
            ),
            trailing: Icon(
              isExpanded ? Icons.expand_less : Icons.expand_more,
              color: const Color(0xFF1A237E),
            ),
            onTap: onTap,
          ),
          if (isExpanded)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: Text(
                answer,
                style: const TextStyle(
                  fontSize: 13,
                  color: Colors.grey,
                  height: 1.5,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildGuideItem(
    String title,
    String description,
    IconData icon,
    Color color,
  ) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    color: Color(0xFF1A237E),
                  ),
                ),
                Text(
                  description,
                  style: const TextStyle(fontSize: 12, color: Colors.grey),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildContactItem(
    IconData icon,
    String title,
    String detail,
    String subtitle,
  ) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: const Color(0xFF1A237E).withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: const Color(0xFF1A237E), size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                    color: Color(0xFF1A237E),
                  ),
                ),
                Text(
                  detail,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Colors.black87,
                  ),
                ),
                Text(
                  subtitle,
                  style: const TextStyle(fontSize: 11, color: Colors.grey),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDivider() {
    return Divider(color: Colors.grey.shade200, height: 1);
  }

  void _showLogoutDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Logout'),
        content: const Text('Are you sure you want to logout?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (context) => const PCLogin()),
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
