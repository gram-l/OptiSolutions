import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'services/auth_service.dart';

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
//  1.  LOGIN SCREEN
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

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passwordCtrl.dispose();
    super.dispose();
  }

 bool _isLoading = false;

Future<void> _login() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _isLoading = true);

    try {
      final data = await AuthService.login(
        _emailCtrl.text.trim(),
        _passwordCtrl.text,
      );

      final role = data['user']['role'];

      if (!mounted) return;

      if (role == 'Admin') {
        Navigator.pushReplacementNamed(context, '/dashboard');
      } else if (role == 'Staff') {
        Navigator.pushReplacementNamed(context, '/appointments');
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Unknown role. Contact administrator.')),
        );
      }

    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceAll('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => _isLoading = false);
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
            const SizedBox(height: 22),

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

            // ── Remember me + Forgot password ──
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
                  onTap: () => Navigator.pushNamed(context, '/reset'),
                  child: const Text('Forgot Password?',
                      style: TextStyle(
                          fontSize: 12.5,
                          color: _C.link,
                          fontWeight: FontWeight.w600)),
                ),
              ],
            ),
            const SizedBox(height: 22),

            // ── Login button ──
            _PrimaryButton(
              label: 'Sign In',
              icon: Icons.login_rounded,
              onTap: _login,
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  2.  RESET PASSWORD SCREEN
// ─────────────────────────────────────────────────────────────
class ResetPasswordScreen extends StatefulWidget {
  const ResetPasswordScreen({super.key});

  @override
  State<ResetPasswordScreen> createState() => _ResetPasswordScreenState();
}

class _ResetPasswordScreenState extends State<ResetPasswordScreen> {
  final _formKey   = GlobalKey<FormState>();
  final _phoneCtrl = TextEditingController();

  @override
  void dispose() {
    _phoneCtrl.dispose();
    super.dispose();
  }

  void _sendOtp() {
    if (_formKey.currentState?.validate() ?? false) {
      Navigator.pushNamed(context, '/otp');
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
            const SizedBox(height: 22),

            // ── Phone field ──
            _FormField(
              label: 'Registered Phone Number',
              hint: 'Enter your registered phone number',
              prefixIcon: Icons.phone_outlined,
              keyboardType: TextInputType.phone,
              controller: _phoneCtrl,
              validator: (v) =>
                  (v == null || v.isEmpty) ? 'Phone number is required' : null,
            ),
            const SizedBox(height: 24),

            // ── Send OTP button ──
            _PrimaryButton(
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
                    style: TextStyle(
                        fontSize: 13,
                        color: _C.link,
                        fontWeight: FontWeight.w500)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
//  3.  OTP SCREEN
// ─────────────────────────────────────────────────────────────
class OtpScreen extends StatefulWidget {
  const OtpScreen({super.key});

  @override
  State<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends State<OtpScreen> {
  final _emailCtrl = TextEditingController();
  final List<TextEditingController> _otpCtrl =
      List.generate(6, (_) => TextEditingController());
  final List<FocusNode> _focusNodes =
      List.generate(6, (_) => FocusNode());

  static const _totalSeconds = 95;
  int _remaining = _totalSeconds;
  Timer? _timer;
  bool _sent = false;

  @override
  void initState() {
    super.initState();
    _startTimer();
    _sent = true;
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
    _emailCtrl.dispose();
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

  void _verifyOtp() {
    final code = _otpCtrl.map((c) => c.text).join();
    if (code.length == 6) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('OTP Verified!')),
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
          const SizedBox(height: 22),

          // ── Email field ──
          _FormField(
            label: 'Registered Email',
            hint: 'Enter your registered email',
            prefixIcon: Icons.email_outlined,
            keyboardType: TextInputType.emailAddress,
            controller: _emailCtrl,
          ),
          const SizedBox(height: 12),

          // ── Timer ──
          if (_sent)
            Align(
              alignment: Alignment.centerRight,
              child: Text(
                _timerText,
                style: const TextStyle(
                    fontSize: 13,
                    color: _C.link,
                    fontWeight: FontWeight.w600),
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

          // ── Send / Verify OTP button ──
          _PrimaryButton(
            label: 'Send OTP',
            icon: Icons.send_rounded,
            onTap: _remaining > 0 ? _verifyOtp : null,
          ),
          const SizedBox(height: 10),

          // ── Resend ──
          if (_remaining == 0)
            Center(
              child: GestureDetector(
                onTap: _startTimer,
                child: const Text('Resend OTP',
                    style: TextStyle(
                        color: _C.link,
                        fontSize: 13,
                        fontWeight: FontWeight.w600)),
              ),
            ),
          const SizedBox(height: 6),

          // ── Back to login ──
          Center(
            child: GestureDetector(
              onTap: () => Navigator.popUntil(context, ModalRoute.withName('/')),
              child: const Text('← Back to Login',
                  style: TextStyle(
                      fontSize: 13,
                      color: _C.link,
                      fontWeight: FontWeight.w500)),
            ),
          ),
        ],
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