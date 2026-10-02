<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Code – PolyClinic</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
@vite('resources/css/login.css')
</head>
<body>

<div class="login-container">

    <!-- LEFT SIDE: clinic photo -->
    <div class="left-panel">
        <img src="{{ asset('images/Polyclinic.jpg') }}" alt="PolyClinic building">
        <div class="photo-caption"><strong>Check Your Email</strong><span>We've sent a 6-digit verification code to your email address.</span></div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right-panel">

        <header class="branding">
            <img src="{{ asset('images/polyclinic_logo.png') }}"
                 class="logo"
                 alt="PolyClinic Logo"
                 onerror="this.onerror=null; this.style.display='none';">
            <div>
                <h2>PolyClinic</h2>
                <p>Inquiry System · Smart Care</p>
            </div>
        </header>

        <main class="form-wrap">
            <div class="form-box">
                <h1>Enter Verification Code</h1>
                <p class="subtitle">Step 2 of 3 — Sent to {{ session('reset_email') }}</p>

                @if (session('success'))
                    <div class="error-message success show" role="status">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="error-message show" role="alert">
                        {{ $errors->first('otp') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.otp.verify') }}" id="otp-form">
                    @csrf

                    {{-- Hidden field that holds the combined OTP --}}
                    <input type="hidden" name="otp" id="otp-hidden">

                    {{-- 6 individual digit boxes --}}
                    <div class="otp-inputs">
                        @for ($i = 1; $i <= 6; $i++)
                            <input type="text" maxlength="1" inputmode="numeric"
                                   class="otp-digit" id="d{{ $i }}" autocomplete="off"
                                   aria-label="Digit {{ $i }}">
                        @endfor
                    </div>

                    <button type="submit" id="verify-btn" class="btn-login">Verify Code →</button>
                </form>

                <div class="resend-row">
                    Didn't receive it?
                    <form method="POST" action="{{ route('password.otp.resend') }}" style="display:inline">
                        @csrf
                        <button type="submit" id="resend-btn" disabled>
                            Resend in <span id="countdown">60</span>s
                        </button>
                    </form>
                </div>

                <p class="back-link">
                    <a href="{{ route('password.forgot') }}">← Use a different email</a>
                </p>
            </div>
        </main>

        <footer class="page-footer">© {{ date('Y') }} PolyClinic Health · Authorized personnel only</footer>
    </div>
</div>

<script>
// ── OTP digit boxes: auto-advance & combine into hidden field ──
const digits = document.querySelectorAll('.otp-digit');
const hidden  = document.getElementById('otp-hidden');

digits.forEach((input, i) => {
    input.addEventListener('input', () => {
        input.value = input.value.replace(/\D/, '');
        if (input.value && i < digits.length - 1) digits[i + 1].focus();
        hidden.value = [...digits].map(d => d.value).join('');
    });
    input.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !input.value && i > 0) digits[i - 1].focus();
    });
    input.addEventListener('paste', e => {
        const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        [...digits].forEach((d, j) => d.value = paste[j] || '');
        hidden.value = paste.slice(0, 6);
        digits[Math.min(paste.length, 5)].focus();
        e.preventDefault();
    });
});

// ── Resend countdown ──
let seconds = 60;
const countdown  = document.getElementById('countdown');
const resendBtn  = document.getElementById('resend-btn');

const timer = setInterval(() => {
    seconds--;
    countdown.textContent = seconds;
    if (seconds <= 0) {
        clearInterval(timer);
        resendBtn.disabled = false;
        resendBtn.innerHTML = 'Resend code';
    }
}, 1000);
</script>

</body>
</html>