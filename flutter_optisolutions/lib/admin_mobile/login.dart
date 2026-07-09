import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_optisolutions/auth/auth_service.dart';
import 'package:flutter_optisolutions/auth/forgot_password_service.dart';
import 'package:flutter_optisolutions/auth/google_auth.dart';


// ─────────────────────────────────────────────────────────────
//  SHARED CONSTANTS
// ─────────────────────────────────────────────────────────────
class _C {
  static const bgTop    = Color(0xFF0D3B72);
  static const bgBottom = Color(0xFF0A2A52);
  static const card     = Colors.white;
  static const btn      = Color(0xFF0D3B72);
  static const hint     = Color(0xFFBEC3CC);
  static const label    = Color(0xFF4A5568);
  static const link     = Color(0xFF1565C0);
  static const border   = Color(0xFFDDE1E8);
}

// ─────────────────────────────────────────────────────────────
//  SHARED BACKGROUND SCAFFOLD
// ─────────────────────────────────────────────────────────────
class _AuthScaffold extends StatelessWidget {
  final Widget child;
  const _AuthScaffold({required this.child});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [_C.bgTop, _C.bgBottom],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Column(
              children: [
                const SizedBox(height: 60),
                // ── Logo ──
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Container(
                      width: 52,
                      height: 52,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(color: Colors.black.withValues(alpha: 0.2), blurRadius: 8),
                        ],
                      ),
                      child: const _AppIcon(),
                    ),
                    const SizedBox(width: 12),
                    const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('PolyClinic',
                            style: TextStyle(
                                color: Colors.white,
                                fontSize: 22,
                                fontWeight: FontWeight.bold)),
                        Text('Secure Inquiry System',
                            style: TextStyle(color: Colors.white70, fontSize: 12)),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 40),
                // ── Card ──
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: _C.card,
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                      BoxShadow(color: Colors.black.withValues(alpha: 0.15), blurRadius: 20),
                    ],
                  ),
                  child: child,
                ),
                const SizedBox(height: 40),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _AppIcon extends StatelessWidget {
  const _AppIcon();

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      'assets/polyclinic_logo.png',
      width: 100,
      height: 100,
    );
  }
}

class _QuadIcon extends StatelessWidget {
  final IconData icon;
  final Color color;
  const _QuadIcon(this.icon, this.color);

  @override
  Widget build(BuildContext context) =>
      Icon(icon, size: 13, color: color);
}

// ─────────────────────────────────────────────────────────────
//  REUSABLE FORM FIELD
// ─────────────────────────────────────────────────────────────
class _FormField extends StatefulWidget {
  final String label;
  final String hint;
  final IconData prefixIcon;
  final bool isPassword;
  final TextInputType keyboardType;
  final TextEditingController? controller;
  final String? Function(String?)? validator;

  const _FormField({
    required this.label,
    required this.hint,
    required this.prefixIcon,
    this.isPassword = false,
    this.keyboardType = TextInputType.text,
    this.controller,
    this.validator,
  });

  @override
  State<_FormField> createState() => _FormFieldState();
}

class _FormFieldState extends State<_FormField> {
  bool _obscure = true;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(widget.prefixIcon, size: 14, color: _C.label),
            const SizedBox(width: 5),
            Text(widget.label,
                style: const TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w600,
                    color: _C.label)),
          ],
        ),
        const SizedBox(height: 6),
        TextFormField(
          controller: widget.controller,
          obscureText: widget.isPassword && _obscure,
          keyboardType: widget.keyboardType,
          validator: widget.validator,
          style: const TextStyle(fontSize: 13.5, color: Color(0xFF1A1A2E)),
          decoration: InputDecoration(
            hintText: widget.hint,
            hintStyle: const TextStyle(color: _C.hint, fontSize: 13),
            suffixIcon: widget.isPassword
                ? IconButton(
                    icon: Icon(
                      _obscure ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                      size: 18,
                      color: _C.hint,
                    ),
                    onPressed: () => setState(() => _obscure = !_obscure),
                  )
                : null,
            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
            filled: true,
            fillColor: const Color(0xFFF8F9FB),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: _C.border),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: _C.border),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: Color(0xFF1565C0), width: 1.5),
            ),
            errorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: Colors.red),
            ),
          ),
        ),
      ],
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  PRIMARY BUTTON
// ─────────────────────────────────────────────────────────────
class _PrimaryButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final VoidCallback? onTap;
  const _PrimaryButton({required this.label, required this.icon, this.onTap});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      height: 50,
      child: ElevatedButton.icon(
        onPressed: onTap,
        icon: Icon(icon, size: 18, color: Colors.white),
        label: Text(label,
            style: const TextStyle(
                fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
        style: ElevatedButton.styleFrom(
          backgroundColor: _C.btn,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          elevation: 0,
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  GOOGLE SIGN-IN BUTTON  (NEW — this was missing, causing the error)
// ─────────────────────────────────────────────────────────────
class _GoogleSignInButton extends StatelessWidget {
  final VoidCallback? onTap;
  const _GoogleSignInButton({required this.onTap});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      height: 50,
      child: OutlinedButton.icon(
        onPressed: onTap,
        icon: const Icon(Icons.g_mobiledata, size: 26, color: Colors.red),
        label: const Text(
          'Sign in with Google',
          style: TextStyle(
            fontSize: 14.5,
            fontWeight: FontWeight.w600,
            color: Color(0xFF1A1A2E),
          ),
        ),
        style: OutlinedButton.styleFrom(
          side: const BorderSide(color: _C.border),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  1.  LOGIN SCREEN  (with inline error message UI)
// ─────────────────────────────────────────────────────────────

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailCtrl    = TextEditingController();
  final _passwordCtrl = TextEditingController();
  bool _rememberMe = false;
  bool _loading = false;
  String? _errorMessage;   // NEW: holds the error text to display inline

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passwordCtrl.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    // Clear any previous error before validating/retrying
    setState(() => _errorMessage = null);

    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _loading = true);

    try {
      final result = await AuthService.login(
        _emailCtrl.text.trim(),
        _passwordCtrl.text,
      );

      if (!mounted) return;
      setState(() => _loading = false);

      if (result['success'] == true) {
        final role = (result['user']?['user_role'] ?? '').toString().toLowerCase();
        _navigateByRole(role);
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _errorMessage = e.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  // NEW: handles the Google Sign-In button tap
  Future<void> _loginWithGoogle() async {
    setState(() {
      _loading = true;
      _errorMessage = null;
    });

    try {
      final result = await AuthService.loginWithGoogle();

      if (!mounted) return;
      setState(() => _loading = false);

      if (result['user'] != null) {
        final role = (result['user']['user_role'] ?? '').toString().toLowerCase();
        _navigateByRole(role);
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _errorMessage = e.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  // NEW: routes admin to DashboardScreen ('/dashboard') and staff to
  // AppointmentsScreen ('/appointments'), matching this app's existing
  // RBAC convention (both routes already registered in main.dart).
  void _navigateByRole(String role) {
    switch (role) {
      case 'admin':
        Navigator.pushReplacementNamed(context, '/dashboard');
        break;
      case 'staff':
        Navigator.pushReplacementNamed(context, '/appointments');
        break;
      default:
        setState(() {
          _errorMessage = 'Unrecognized account role. Please contact your administrator.';
        });
    }
  }

  @override
  Widget build(BuildContext context) {
    return _AuthScaffold(
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Heading ──
            const Row(
              children: [
                Icon(Icons.lock_outline_rounded, size: 20, color: Color(0xFF1A1A2E)),
                SizedBox(width: 8),
                Text('Sign In',
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
              ],
            ),
            const SizedBox(height: 4),
            const Text('Welcome back to PolyClinic',
                style: TextStyle(color: Color(0xFF8A8FA3), fontSize: 13)),
            const SizedBox(height: 18),

            // ── NEW: Inline error banner ──
            if (_errorMessage != null) ...[
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                decoration: BoxDecoration(
                  color: const Color(0xFFFDEAEA),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFF5C6C6)),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.error_outline_rounded,
                        size: 18, color: Color(0xFFD32F2F)),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        _errorMessage!,
                        style: const TextStyle(
                          fontSize: 12.5,
                          color: Color(0xFFD32F2F),
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 14),
            ],

            // ── Email ──
            _FormField(
              label: 'Email Address',
              hint: 'Enter your email',
              prefixIcon: Icons.email_outlined,
              keyboardType: TextInputType.emailAddress,
              controller: _emailCtrl,
              validator: (v) =>
                  (v == null || !v.contains('@')) ? 'Enter a valid email' : null,
            ),
            const SizedBox(height: 16),

            // ── Password ──
            _FormField(
              label: 'Password',
              hint: 'Enter your password',
              prefixIcon: Icons.lock_outline_rounded,
              isPassword: true,
              controller: _passwordCtrl,
              validator: (v) =>
                  (v == null || v.length < 6) ? 'Min. 6 characters' : null,
            ),
            const SizedBox(height: 10),

            // Remember me + Forgot password
            Row(
              children: [
                SizedBox(
                  width: 20,
                  height: 20,
                  child: Checkbox(
                    value: _rememberMe,
                    onChanged: (v) => setState(() => _rememberMe = v ?? false),
                    activeColor: _C.btn,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
                  ),
                ),
                const SizedBox(width: 6),
                const Text('Remember me',
                    style: TextStyle(fontSize: 12.5, color: Color(0xFF4A5568))),
                const Spacer(),
                GestureDetector(
                  onTap: () {
                    Navigator.pushNamed(context, '/reset');
                  },
                  child: const Text('Forgot Password?',
                      style: TextStyle(
                          fontSize: 12.5,
                          color: _C.link,
                          fontWeight: FontWeight.w600)),
                ),
              ],
            ),
            const SizedBox(height: 22),

            // ── Login button (shows spinner while loading) ──
            _loading
                ? const Center(
                    child: Padding(
                      padding: EdgeInsets.symmetric(vertical: 13),
                      child: CircularProgressIndicator(),
                    ),
                  )
                : Column(
                    children: [
                      _PrimaryButton(
                        label: 'Sign In',
                        icon: Icons.login_rounded,
                        onTap: _login,
                      ),

                      const SizedBox(height: 16),

                      const Row(
                        children: [
                          Expanded(child: Divider()),
                          Padding(
                            padding: EdgeInsets.symmetric(horizontal: 12),
                            child: Text(
                              "OR",
                              style: TextStyle(
                                color: Colors.grey,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                          Expanded(child: Divider()),
                        ],
                      ),

                      const SizedBox(height: 16),

                      _GoogleSignInButton(
                        onTap: _loginWithGoogle,
                      ),
                    ],
                  ),
          ],
        ),
      ),
    );
  }
}

//  2.  RESET PASSWORD SCREEN (Step 1 — enter email, send OTP)


class ResetPasswordScreen extends StatefulWidget {
  const ResetPasswordScreen({super.key});

  @override
  State<ResetPasswordScreen> createState() => _ResetPasswordScreenState();
}

class _ResetPasswordScreenState extends State<ResetPasswordScreen> {
  final _formKey  = GlobalKey<FormState>();
  final _emailCtrl = TextEditingController();   // CHANGED: phone → email
  bool _loading = false;
  String? _errorMessage;

  @override
  void dispose() {
    _emailCtrl.dispose();
    super.dispose();
  }

  Future<void> _sendOtp() async {
    setState(() => _errorMessage = null);

    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _loading = true);

    final email = _emailCtrl.text.trim();
    final result = await ForgotPasswordService.sendOtp(email);

    if (!mounted) return;
    setState(() => _loading = false);

    if (result['success'] == true) {
      Navigator.pushNamed(context, '/verify-otp', arguments: email);
    } else {
      setState(() => _errorMessage = result['message'] ?? 'Failed to send code.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return _AuthScaffold(
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Heading ──
            const Row(
              children: [
                Icon(Icons.key_rounded, size: 20, color: Color(0xFF1A1A2E)),
                SizedBox(width: 8),
                Text('Reset Password',
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
              ],
            ),
            const SizedBox(height: 18),

            if (_errorMessage != null) ...[
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                decoration: BoxDecoration(
                  color: const Color(0xFFFDEAEA),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFF5C6C6)),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.error_outline_rounded, size: 18, color: Color(0xFFD32F2F)),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(_errorMessage!,
                          style: const TextStyle(
                              fontSize: 12.5, color: Color(0xFFD32F2F), fontWeight: FontWeight.w500)),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 14),
            ],

            // ── Email field (was phone) ──
            _FormField(
              label: 'Registered Email',
              hint: 'Enter your registered email',
              prefixIcon: Icons.email_outlined,
              keyboardType: TextInputType.emailAddress,
              controller: _emailCtrl,
              validator: (v) =>
                  (v == null || !v.contains('@')) ? 'Enter a valid email' : null,
            ),
            const SizedBox(height: 24),

            // ── Send OTP button ──
            _loading
                ? const Center(
                    child: Padding(
                      padding: EdgeInsets.symmetric(vertical: 13),
                      child: CircularProgressIndicator(),
                    ),
                  )
                : _PrimaryButton(
                    label: 'Send OTP',
                    icon: Icons.send_rounded,
                    onTap: _sendOtp,
                  ),
            const SizedBox(height: 16),

            // ── Back to login ──
            Center(
              child: GestureDetector(
                onTap: () => Navigator.pop(context),
                child: const Text('← Back to Login',
                    style: TextStyle(fontSize: 13, color: _C.link, fontWeight: FontWeight.w500)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  3.  OTP SCREEN  (Step 2 — verify code, then go to new password)
// ─────────────────────────────────────────────────────────────

class OtpScreen extends StatefulWidget {
  const OtpScreen({super.key});

  @override
  State<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends State<OtpScreen> {
  final List<TextEditingController> _otpCtrl =
      List.generate(6, (_) => TextEditingController());
  final List<FocusNode> _focusNodes =
      List.generate(6, (_) => FocusNode());

  static const _totalSeconds = 95;
  int _remaining = _totalSeconds;
  Timer? _timer;
  bool _loading = false;
  bool _resending = false;
  String? _errorMessage;
  String _email = '';   // passed in via Navigator arguments

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final args = ModalRoute.of(context)?.settings.arguments;
    if (args is String) _email = args;
  }

  @override
  void initState() {
    super.initState();
    _startTimer();
  }

  void _startTimer() {
    _timer?.cancel();
    setState(() => _remaining = _totalSeconds);
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (_remaining == 0) {
        t.cancel();
      } else {
        setState(() => _remaining--);
      }
    });
  }

  String get _timerText {
    final m = (_remaining ~/ 60).toString().padLeft(2, '0');
    final s = (_remaining % 60).toString().padLeft(2, '0');
    return '$m:${s}s';
  }

  @override
  void dispose() {
    for (final c in _otpCtrl) {
      c.dispose();
    }
    for (final f in _focusNodes) {
      f.dispose();
    }
    _timer?.cancel();
    super.dispose();
  }

  void _onOtpDigitChanged(String value, int index) {
    if (value.length == 1 && index < 5) {
      _focusNodes[index + 1].requestFocus();
    } else if (value.isEmpty && index > 0) {
      _focusNodes[index - 1].requestFocus();
    }
  }

  Future<void> _verifyOtp() async {
    final code = _otpCtrl.map((c) => c.text).join();
    if (code.length != 6) {
      setState(() => _errorMessage = 'Enter all 6 digits.');
      return;
    }

    setState(() {
      _loading = true;
      _errorMessage = null;
    });

    final result = await ForgotPasswordService.verifyOtp(_email, code);

    if (!mounted) return;
    setState(() => _loading = false);

    if (result['success'] == true) {
      Navigator.pushNamed(
        context,
        '/new-password',
        arguments: {
          'email': _email,
          'reset_token': result['reset_token'],
        },
      );
    } else {
      setState(() => _errorMessage = result['message'] ?? 'Invalid code.');
    }
  }

  Future<void> _resend() async {
    setState(() => _resending = true);
    final result = await ForgotPasswordService.resendOtp(_email);
    if (!mounted) return;
    setState(() => _resending = false);

    if (result['success'] == true) {
      _startTimer();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message'] ?? 'Code resent.')),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message'] ?? 'Could not resend code.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return _AuthScaffold(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Heading ──
          const Text('One-Time-Password (OTP)',
              style: TextStyle(fontSize: 19, fontWeight: FontWeight.bold)),
          const SizedBox(height: 6),
          Text('Sent to $_email',
              style: const TextStyle(fontSize: 12.5, color: Color(0xFF8A8FA3))),
          const SizedBox(height: 18),

          if (_errorMessage != null) ...[
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              decoration: BoxDecoration(
                color: const Color(0xFFFDEAEA),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFF5C6C6)),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.error_outline_rounded, size: 18, color: Color(0xFFD32F2F)),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(_errorMessage!,
                        style: const TextStyle(
                            fontSize: 12.5, color: Color(0xFFD32F2F), fontWeight: FontWeight.w500)),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),
          ],

          // ── Timer ──
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              _timerText,
              style: const TextStyle(fontSize: 13, color: _C.link, fontWeight: FontWeight.w600),
            ),
          ),
          const SizedBox(height: 12),

          // ── 6-digit OTP boxes ──
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: List.generate(6, (i) => _OtpBox(
              controller: _otpCtrl[i],
              focusNode: _focusNodes[i],
              onChanged: (v) => _onOtpDigitChanged(v, i),
            )),
          ),
          const SizedBox(height: 22),

          // ── Verify button ──
          _loading
              ? const Center(
                  child: Padding(
                    padding: EdgeInsets.symmetric(vertical: 13),
                    child: CircularProgressIndicator(),
                  ),
                )
              : _PrimaryButton(
                  label: 'Verify Code',
                  icon: Icons.check_circle_outline_rounded,
                  onTap: _verifyOtp,
                ),
          const SizedBox(height: 10),

          // ── Resend ──
          Center(
            child: _resending
                ? const SizedBox(
                    width: 16, height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : GestureDetector(
                    onTap: _remaining == 0 ? _resend : null,
                    child: Text(
                      'Resend OTP',
                      style: TextStyle(
                        color: _remaining == 0 ? _C.link : _C.hint,
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
          ),
          const SizedBox(height: 6),

          // ── Back to login ──
          Center(
            child: GestureDetector(
              onTap: () => Navigator.popUntil(context, ModalRoute.withName('/')),
              child: const Text('← Back to Login',
                  style: TextStyle(fontSize: 13, color: _C.link, fontWeight: FontWeight.w500)),
            ),
          ),
        ],
      ),
    );
  }
}
// ─────────────────────────────────────────────────────────────
//  4.  NEW PASSWORD SCREEN  (Step 3 — set new password)
// ─────────────────────────────────────────────────────────────

class NewPasswordScreen extends StatefulWidget {
  const NewPasswordScreen({super.key});

  @override
  State<NewPasswordScreen> createState() => _NewPasswordScreenState();
}

class _NewPasswordScreenState extends State<NewPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _passwordCtrl = TextEditingController();
  final _confirmCtrl  = TextEditingController();
  bool _loading = false;
  String? _errorMessage;

  String _email = '';
  String _resetToken = '';

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final args = ModalRoute.of(context)?.settings.arguments;
    if (args is Map) {
      _email = args['email'] ?? '';
      _resetToken = args['reset_token'] ?? '';
    }
  }

  @override
  void dispose() {
    _passwordCtrl.dispose();
    _confirmCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _errorMessage = null);

    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _loading = true);

    final result = await ForgotPasswordService.resetPassword(
      email: _email,
      resetToken: _resetToken,
      password: _passwordCtrl.text,
      passwordConfirmation: _confirmCtrl.text,
    );

    if (!mounted) return;
    setState(() => _loading = false);

    if (result['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message'] ?? 'Password updated.')),
      );
      Navigator.popUntil(context, ModalRoute.withName('/'));
    } else {
      setState(() => _errorMessage = result['message'] ?? 'Could not reset password.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return _AuthScaffold(
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                Icon(Icons.lock_reset_rounded, size: 20, color: Color(0xFF1A1A2E)),
                SizedBox(width: 8),
                Text('Set New Password',
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
              ],
            ),
            const SizedBox(height: 18),

            if (_errorMessage != null) ...[
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                decoration: BoxDecoration(
                  color: const Color(0xFFFDEAEA),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFF5C6C6)),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.error_outline_rounded, size: 18, color: Color(0xFFD32F2F)),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(_errorMessage!,
                          style: const TextStyle(
                              fontSize: 12.5, color: Color(0xFFD32F2F), fontWeight: FontWeight.w500)),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 14),
            ],

            _FormField(
              label: 'New Password',
              hint: 'Enter new password',
              prefixIcon: Icons.lock_outline_rounded,
              isPassword: true,
              controller: _passwordCtrl,
              validator: (v) =>
                  (v == null || v.length < 8) ? 'Min. 8 characters' : null,
            ),
            const SizedBox(height: 16),

            _FormField(
              label: 'Confirm Password',
              hint: 'Re-enter new password',
              prefixIcon: Icons.lock_outline_rounded,
              isPassword: true,
              controller: _confirmCtrl,
              validator: (v) =>
                  (v != _passwordCtrl.text) ? 'Passwords do not match' : null,
            ),
            const SizedBox(height: 22),

            _loading
                ? const Center(
                    child: Padding(
                      padding: EdgeInsets.symmetric(vertical: 13),
                      child: CircularProgressIndicator(),
                    ),
                  )
                : _PrimaryButton(
                    label: 'Update Password',
                    icon: Icons.check_circle_outline_rounded,
                    onTap: _submit,
                  ),
          ],
        ),
      ),
    );
  }

}

// ─────────────────────────────────────────────────────────────
//  OTP DIGIT BOX
// ─────────────────────────────────────────────────────────────
class _OtpBox extends StatelessWidget {
  final TextEditingController controller;
  final FocusNode focusNode;
  final ValueChanged<String> onChanged;
  const _OtpBox({
    required this.controller,
    required this.focusNode,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 44,
      height: 50,
      child: TextFormField(
        controller: controller,
        focusNode: focusNode,
        onChanged: onChanged,
        textAlign: TextAlign.center,
        keyboardType: TextInputType.number,
        maxLength: 1,
        inputFormatters: [FilteringTextInputFormatter.digitsOnly],
        style: const TextStyle(
            fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF1A1A2E)),
        decoration: InputDecoration(
          counterText: '',
          contentPadding: EdgeInsets.zero,
          filled: true,
          fillColor: const Color(0xFFF8F9FB),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
            borderSide: const BorderSide(color: _C.border),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
            borderSide: const BorderSide(color: _C.border),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
            borderSide: const BorderSide(color: Color(0xFF1565C0), width: 2),
          ),
        ),
      ),
    );
  }
}