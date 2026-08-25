// terms_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'colors.dart';

class TermsScreen extends StatefulWidget {
  const TermsScreen({super.key});

  @override
  State<TermsScreen> createState() => _TermsScreenState();
}

class _TermsScreenState extends State<TermsScreen> {
  final ScrollController _scrollController = ScrollController();

  // Becomes true once the user has scrolled all the way to the bottom
  // of the Terms & Conditions text. Both the "I have read..." toggle
  // and the Agree/Decline buttons stay disabled until this is true.
  bool _hasScrolledToEnd = false;

  // The "I have read the Terms and Conditions" toggle. Only togglable
  // once _hasScrolledToEnd is true. Agree also requires this to be on.
  bool _hasConfirmedRead = false;

  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);

    // Handles the edge case where the content is short enough that the
    // ScrollView never actually needs to scroll (e.g. very large screen).
    // Without this, a user on such a device could never satisfy the
    // scroll-to-bottom requirement.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scrollController.hasClients) return;
      if (_scrollController.position.maxScrollExtent <= 0) {
        setState(() => _hasScrolledToEnd = true);
      }
    });
  }

  @override
  void dispose() {
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_hasScrolledToEnd) return; // no need to keep checking once satisfied
    const threshold = 24.0; // px of tolerance near the bottom
    final pos = _scrollController.position;
    if (pos.pixels >= pos.maxScrollExtent - threshold) {
      setState(() => _hasScrolledToEnd = true);
    }
  }

  Future<void> _agree() async {
    if (!_hasScrolledToEnd || !_hasConfirmedRead || _isSaving) return;

    setState(() => _isSaving = true);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('terms_accepted', true);

    if (!mounted) return;
    setState(() => _isSaving = false);
    Navigator.pushReplacementNamed(context, '/login');
  }

  void _decline() {
    if (!_hasScrolledToEnd) return;

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Agreement Required'),
        content: const Text(
          'You must accept the Terms & Conditions to use this application. '
          'Declining will close the app.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Review Again'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.deleteRed,
              foregroundColor: Colors.white,
            ),
            onPressed: () {
              Navigator.pop(ctx);
              SystemNavigator.pop(); // exits the app
            },
            child: const Text('Exit App'),
          ),
        ],
      ),
    );
  }

  // Whether the toggle can currently be interacted with.
  bool get _toggleEnabled => _hasScrolledToEnd;

  // Whether the Agree/Decline buttons can currently be pressed.
  bool get _buttonsEnabled => _hasScrolledToEnd;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F6FB),
      body: SafeArea(
        child: Column(
          children: [
            // ── Header ──
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 4),
              child: Row(
                children: [
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: AppColors.primary.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.description_outlined,
                        color: AppColors.primary, size: 20),
                  ),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Terms & Conditions',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textDark,
                          ),
                        ),
                        SizedBox(height: 2),
                        Text(
                          'PolyClinic Mobile Application',
                          style: TextStyle(fontSize: 12.5, color: AppColors.textGrey),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            if (!_hasScrolledToEnd)
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 10, 20, 0),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFF8E1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: const [
                      Icon(Icons.arrow_downward_rounded, size: 14, color: Color(0xFF8D6E00)),
                      SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          'Please scroll to the end to continue',
                          style: TextStyle(fontSize: 11.5, color: Color(0xFF8D6E00)),
                        ),
                      ),
                    ],
                  ),
                ),
              ),

            // ── Scrollable Terms content ──
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 10, 20, 0),
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Scrollbar(
                    controller: _scrollController,
                    thumbVisibility: true,
                    child: SingleChildScrollView(
                      controller: _scrollController,
                      padding: const EdgeInsets.all(18),
                      child: const _TermsBody(),
                    ),
                  ),
                ),
              ),
            ),

            // ── Toggle + Agree/Decline ──
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Toggle: "I have read the Terms and Conditions"
                  Opacity(
                    opacity: _toggleEnabled ? 1 : 0.45,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Text(
                              'I have read the Terms and Conditions',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: AppColors.textDark,
                              ),
                            ),
                          ),
                          Switch(
                            value: _hasConfirmedRead,
                            activeColor: AppColors.primary,
                            onChanged: _toggleEnabled
                                ? (val) => setState(() => _hasConfirmedRead = val)
                                : null,
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Agree / Decline buttons
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: _buttonsEnabled ? _decline : null,
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            side: BorderSide(
                              color: _buttonsEnabled ? AppColors.deleteRed : AppColors.border,
                            ),
                            foregroundColor: AppColors.deleteRed,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ),
                          child: const Text('Decline'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: ElevatedButton(
                          onPressed: (_buttonsEnabled && _hasConfirmedRead && !_isSaving)
                              ? _agree
                              : null,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            disabledBackgroundColor: AppColors.border,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ),
                          child: _isSaving
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : const Text('Agree'),
                        ),
                      ),
                    ],
                  ),
                  if (_buttonsEnabled && !_hasConfirmedRead) ...[
                    const SizedBox(height: 8),
                    const Text(
                      'Toggle "I have read the Terms and Conditions" to enable Agree.',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 11, color: AppColors.textGrey),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Full Terms & Conditions text ──────────────────────────────────────────
class _TermsBody extends StatelessWidget {
  const _TermsBody();

  static const _sections = <_Section>[
    _Section(
      title: 'Preamble',
      body: 'Effective Date: July 20, 2026\n\n'
          'These Terms and Conditions govern the use of the PolyClinic Mobile '
          'Application ("Application"), which is provided exclusively for '
          'authorized PolyClinic administrators and staff. By logging into and '
          'using the Application, you acknowledge that you have read, '
          'understood, and agreed to comply with these Terms and Conditions.',
    ),
    _Section(
      title: '1. Authorized Users',
      body: 'The Application is intended solely for authorized PolyClinic '
          'personnel.\n\n'
          '• User accounts are created and managed exclusively by the '
          'PolyClinic Administrator.\n'
          '• Employees are not permitted to create their own accounts.\n'
          '• Access to the Application is granted only for official '
          'work-related responsibilities.\n'
          '• The Company reserves the right to suspend or terminate access '
          'at any time when necessary.',
    ),
    _Section(
      title: '2. Confidentiality and Data Privacy',
      body: 'The Application contains Sensitive Personal Information, '
          'including Personally Identifiable Information (PII) and '
          'health-related information entrusted to PolyClinic.\n\n'
          'All users are required to:\n'
          '• Maintain the confidentiality of all patient, employee, and '
          'clinic information.\n'
          '• Access information only when required for legitimate business '
          'purposes.\n'
          '• Protect all confidential information from unauthorized '
          'disclosure.\n\n'
          'Users are strictly prohibited from:\n'
          '• Copying, downloading, exporting, or printing confidential '
          'information without authorization.\n'
          '• Taking screenshots or screen recordings of patient information.\n'
          '• Sharing patient information through personal communication '
          'platforms including but not limited to Facebook Messenger, Viber, '
          'WhatsApp, Telegram, Gmail, or similar services.\n'
          '• Disclosing confidential information to unauthorized individuals.\n\n'
          'Unauthorized disclosure of confidential information may result in '
          'disciplinary action, immediate termination of employment, civil '
          'liability, and criminal liability under applicable Philippine laws.',
    ),
    _Section(
      title: '3. Compliance with the Data Privacy Act of 2012',
      body: 'The Application complies with Republic Act No. 10173 (Data '
          'Privacy Act of 2012).\n\n'
          'Users acknowledge that they process sensitive personal information '
          'as part of their duties and agree to:\n'
          '• Process personal data only for authorized clinical and '
          'administrative purposes.\n'
          '• Observe confidentiality at all times.\n'
          '• Prevent unauthorized access, disclosure, alteration, or '
          'destruction of personal information.\n'
          '• Report any suspected data breach immediately.\n\n'
          'Any intentional misuse of personal data may constitute a '
          'violation of the Data Privacy Act and other applicable laws.',
    ),
    _Section(
      title: '4. Account Security',
      body: 'Each employee is responsible for safeguarding their assigned '
          'account.\n\n'
          'Users must:\n'
          '• Keep usernames and passwords confidential.\n'
          '• Never share login credentials with another employee.\n'
          '• Immediately report suspected unauthorized access.\n\n'
          'Employees are fully responsible for all activities performed '
          'using their assigned account until such access is reported as '
          'compromised.\n\n'
          'Credential sharing is strictly prohibited.',
    ),
    _Section(
      title: '5. Device Security Requirements',
      body: 'Employees accessing the Application using company-issued or '
          'personal mobile devices agree to maintain appropriate security '
          'measures.\n\n'
          'Users must:\n'
          '• Protect devices using a PIN, password, fingerprint, or facial '
          'recognition.\n'
          '• Keep the device operating system reasonably updated.\n'
          '• Use only trusted software.\n\n'
          'The following are strictly prohibited:\n'
          '• Using rooted Android devices.\n'
          '• Using jailbroken iPhones.\n'
          '• Installing software intended to bypass operating system '
          'security.\n\n'
          'PolyClinic reserves the right to deny Application access from '
          'devices determined to pose security risks.',
    ),
    _Section(
      title: '6. Separation of Company Data and Personal Data',
      body: 'The Application serves solely as a secure portal for '
          'authorized access.\n\n'
          'Users shall not:\n'
          '• Save patient information to their phone\'s gallery.\n'
          '• Export chat conversations to personal storage.\n'
          '• Upload clinic information to personal cloud storage services '
          'such as Google Drive, iCloud, Dropbox, OneDrive, or similar '
          'platforms.\n'
          '• Store customer contact information outside the Application '
          'unless specifically authorized by management.\n\n'
          'Company information must remain within authorized systems.',
    ),
    _Section(
      title: '7. Monitoring and Audit',
      body: 'To maintain operational integrity, quality assurance, and '
          'security, PolyClinic continuously monitors activities performed '
          'within the Application.\n\n'
          'The Company may record and review:\n'
          '• Login history\n'
          '• Chat conversations\n'
          '• Customer inquiries\n'
          '• Feedback responses\n'
          '• Appointment-related actions\n'
          '• Administrative actions\n'
          '• Activity timestamps\n'
          '• System usage logs\n'
          '• Keystrokes and inputs entered within the Application, where '
          'technically implemented\n\n'
          'This monitoring applies only to activities performed within the '
          'PolyClinic Application and does not extend to employees\' '
          'personal applications, files, messages, or other private content '
          'on their devices.\n\n'
          'By using the Application, employees expressly acknowledge and '
          'consent to this monitoring.',
    ),
    _Section(
      title: '8. Acceptable Use',
      body: 'The Application must be used professionally and solely for '
          'official clinic operations.\n\n'
          'Users shall not:\n'
          '• Use the chat system for personal conversations.\n'
          '• Harass, threaten, discriminate against, or abuse patients or '
          'co-workers.\n'
          '• Provide unauthorized or unscripted medical advice beyond their '
          'assigned responsibilities.\n'
          '• Use offensive, defamatory, or inappropriate language.\n'
          '• Attempt to bypass system security measures.\n\n'
          'Professional communication is expected at all times.',
    ),
    _Section(
      title: '9. Intellectual Property',
      body: 'All information generated, stored, or processed within the '
          'Application remains the exclusive property of PolyClinic.\n\n'
          'This includes but is not limited to:\n'
          '• Chat logs\n'
          '• Inquiry records\n'
          '• Appointment records\n'
          '• Feedback\n'
          '• Reports\n'
          '• Customer lists\n'
          '• Templates\n'
          '• Analytics\n'
          '• Documentation\n'
          '• System-generated data\n\n'
          'Employees acquire no ownership rights over any information '
          'created or processed using the Application.',
    ),
    _Section(
      title: '10. Lost or Stolen Device',
      body: 'If a device with authorized access to the Application is lost '
          'or stolen, the employee must notify PolyClinic within two (2) '
          'hours of becoming aware of the incident.\n\n'
          'PolyClinic reserves the right to:\n'
          '• Immediately deactivate the user\'s account.\n'
          '• Revoke access to the Application.\n'
          '• Remove cached application data where technically possible.\n'
          '• Require additional security verification before restoring '
          'access.\n\n'
          'Failure to promptly report a lost or stolen device may result in '
          'disciplinary action.',
    ),
    _Section(
      title: '11. Employment Separation',
      body: 'Access to the Application is directly tied to active '
          'employment.\n\n'
          'Upon resignation, retirement, suspension, or termination:\n'
          '• User accounts will be immediately deactivated.\n'
          '• Employees shall cease all access to the Application.\n'
          '• Employees must uninstall the Application from any personal '
          'devices if instructed by the Company.\n'
          '• Former employees shall not attempt to regain access using '
          'previous credentials.',
    ),
    _Section(
      title: '12. Prohibited Activities',
      body: 'Users shall not:\n'
          '• Attempt to hack, reverse engineer, or modify the Application.\n'
          '• Introduce malware or malicious software.\n'
          '• Circumvent authentication mechanisms.\n'
          '• Access information beyond their authorized permissions.\n'
          '• Use another employee\'s account.\n'
          '• Allow unauthorized individuals to access the Application.\n'
          '• Use the Application for any unlawful purpose.',
    ),
    _Section(
      title: '13. Violations',
      body: 'Violation of these Terms and Conditions may result in one or '
          'more of the following:\n'
          '• Suspension of Application access\n'
          '• Immediate account deactivation\n'
          '• Administrative or disciplinary sanctions\n'
          '• Termination of employment, where warranted\n'
          '• Civil liability\n'
          '• Criminal prosecution under applicable Philippine laws, '
          'including the Data Privacy Act of 2012 and the Cybercrime '
          'Prevention Act of 2012',
    ),
    _Section(
      title: '14. Amendments',
      body: 'PolyClinic reserves the right to modify these Terms and '
          'Conditions at any time to comply with operational requirements, '
          'legal obligations, or security standards. Continued use of the '
          'Application after such changes constitutes acceptance of the '
          'revised Terms.',
    ),
    _Section(
      title: '15. Acceptance',
      body: 'By logging into and using the PolyClinic Mobile Application, '
          'you acknowledge that:\n'
          '• You have read and understood these Terms and Conditions.\n'
          '• You agree to comply with all applicable clinic policies and '
          'Philippine laws.\n'
          '• You understand that all activities performed within the '
          'Application may be monitored and audited.\n'
          '• You accept responsibility for maintaining the confidentiality '
          'and security of your assigned account and any information '
          'accessed through the Application.\n'
          '• You understand that violations of these Terms may result in '
          'disciplinary action, termination of employment, and legal '
          'consequences where applicable.',
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        for (final section in _sections) ...[
          Text(
            section.title,
            style: const TextStyle(
              fontSize: 14.5,
              fontWeight: FontWeight.bold,
              color: AppColors.darkNavy,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            section.body,
            style: const TextStyle(
              fontSize: 12.5,
              height: 1.55,
              color: AppColors.textDark,
            ),
          ),
          const SizedBox(height: 18),
        ],
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: const Color(0xFFF4F6FB),
            borderRadius: BorderRadius.circular(10),
          ),
          child: const Text(
            'Note: Since your application is for a healthcare clinic, it '
            'would also be advisable to have these Terms reviewed by your '
            'clinic\'s legal counsel or Data Protection Officer (DPO) before '
            'deployment to ensure they align with your internal policies and '
            'Philippine legal requirements.',
            style: TextStyle(
              fontSize: 11.5,
              fontStyle: FontStyle.italic,
              color: AppColors.textGrey,
              height: 1.4,
            ),
          ),
        ),
      ],
    );
  }
}

class _Section {
  final String title;
  final String body;
  const _Section({required this.title, required this.body});
}

// ─────────────────────────────────────────────────────────────
//  Popup dialog version — call this from anywhere (e.g. login
//  screen's initState) to block the app with the Terms & Conditions
//  until the user agrees, without navigating to a separate screen.
// ─────────────────────────────────────────────────────────────

/// Shows the Terms & Conditions as a blocking dialog if the user hasn't
/// already agreed to them (checked via the same 'terms_accepted' flag
/// that [TermsScreen] writes to SharedPreferences). No-op if already
/// accepted, so it's safe to call on every login screen mount.
Future<void> showTermsDialogIfNeeded(BuildContext context) async {
  final prefs = await SharedPreferences.getInstance();
  final alreadyAccepted = prefs.getBool('terms_accepted') ?? false;
  if (alreadyAccepted) return;

  if (!context.mounted) return;

  await showDialog<void>(
    context: context,
    barrierDismissible: false, // must Agree or Decline, no tapping outside
    builder: (_) => const _TermsDialog(),
  );
}

class _TermsDialog extends StatefulWidget {
  const _TermsDialog();

  @override
  State<_TermsDialog> createState() => _TermsDialogState();
}

class _TermsDialogState extends State<_TermsDialog> {
  final ScrollController _scrollController = ScrollController();

  bool _hasScrolledToEnd = false;
  bool _hasConfirmedRead = false;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);

    // Same short-content safety net as TermsScreen: if there's nothing
    // to scroll, unlock immediately.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scrollController.hasClients) return;
      if (_scrollController.position.maxScrollExtent <= 0) {
        setState(() => _hasScrolledToEnd = true);
      }
    });
  }

  @override
  void dispose() {
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_hasScrolledToEnd) return;
    const threshold = 24.0;
    final pos = _scrollController.position;
    if (pos.pixels >= pos.maxScrollExtent - threshold) {
      setState(() => _hasScrolledToEnd = true);
    }
  }

  Future<void> _agree() async {
    if (!_hasScrolledToEnd || !_hasConfirmedRead || _isSaving) return;

    setState(() => _isSaving = true);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('terms_accepted', true);

    if (!mounted) return;
    Navigator.of(context).pop(); // close the dialog, login screen continues
  }

  void _decline() {
    if (!_hasScrolledToEnd) return;

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Agreement Required'),
        content: const Text(
          'You must accept the Terms & Conditions to use this application. '
          'Declining will close the app.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Review Again'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.deleteRed,
              foregroundColor: Colors.white,
            ),
            onPressed: () {
              Navigator.pop(ctx);
              SystemNavigator.pop(); // exits the app
            },
            child: const Text('Exit App'),
          ),
        ],
      ),
    );
  }

  bool get _toggleEnabled => _hasScrolledToEnd;
  bool get _buttonsEnabled => _hasScrolledToEnd;

  @override
  Widget build(BuildContext context) {
    final screenHeight = MediaQuery.of(context).size.height;

    return PopScope(
      // Block back-button/back-gesture dismissal — same intent as
      // barrierDismissible: false, but also covers Android back button.
      canPop: false,
      child: Dialog(
        insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 40),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        child: ConstrainedBox(
          constraints: BoxConstraints(
            maxHeight: screenHeight * 0.85,
            maxWidth: 480,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // ── Header ──
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 4),
                child: Row(
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: AppColors.primary.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.description_outlined,
                          color: AppColors.primary, size: 20),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Terms & Conditions',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: AppColors.textDark,
                            ),
                          ),
                          SizedBox(height: 2),
                          Text(
                            'PolyClinic Mobile Application',
                            style: TextStyle(fontSize: 12.5, color: AppColors.textGrey),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              if (!_hasScrolledToEnd)
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 10, 20, 0),
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFF8E1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: const [
                        Icon(Icons.arrow_downward_rounded, size: 14, color: Color(0xFF8D6E00)),
                        SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            'Please scroll to the end to continue',
                            style: TextStyle(fontSize: 11.5, color: Color(0xFF8D6E00)),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

              // ── Scrollable Terms content ──
              Flexible(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 10, 20, 0),
                  child: Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Scrollbar(
                      controller: _scrollController,
                      thumbVisibility: true,
                      child: SingleChildScrollView(
                        controller: _scrollController,
                        padding: const EdgeInsets.all(18),
                        child: const _TermsBody(),
                      ),
                    ),
                  ),
                ),
              ),

              // ── Toggle + Agree/Decline ──
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Opacity(
                      opacity: _toggleEnabled ? 1 : 0.45,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                'I have read the Terms and Conditions',
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.textDark,
                                ),
                              ),
                            ),
                            Switch(
                              value: _hasConfirmedRead,
                              activeColor: AppColors.primary,
                              onChanged: _toggleEnabled
                                  ? (val) => setState(() => _hasConfirmedRead = val)
                                  : null,
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),

                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            onPressed: _buttonsEnabled ? _decline : null,
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              side: BorderSide(
                                color: _buttonsEnabled ? AppColors.deleteRed : AppColors.border,
                              ),
                              foregroundColor: AppColors.deleteRed,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                              ),
                            ),
                            child: const Text('Decline'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: ElevatedButton(
                            onPressed: (_buttonsEnabled && _hasConfirmedRead && !_isSaving)
                                ? _agree
                                : null,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.primary,
                              disabledBackgroundColor: AppColors.border,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                              ),
                            ),
                            child: _isSaving
                                ? const SizedBox(
                                    width: 18,
                                    height: 18,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Text('Agree'),
                          ),
                        ),
                      ],
                    ),
                    if (_buttonsEnabled && !_hasConfirmedRead) ...[
                      const SizedBox(height: 8),
                      const Text(
                        'Toggle "I have read the Terms and Conditions" to enable Agree.',
                        textAlign: TextAlign.center,
                        style: TextStyle(fontSize: 11, color: AppColors.textGrey),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}