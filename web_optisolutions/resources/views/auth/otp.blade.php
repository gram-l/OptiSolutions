<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Code – PolyClinic</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
@vite('resources/css/login.css')
<style>
  .otp-inputs { display:flex; gap:10px; justify-content:center; margin:24px 0; 
}
  .otp-inputs input {
    width:48px; height:56px; text-align:center; font-size:22px; font-weight:700;
    border:2px solid #dde1e8; border-radius:10px; outline:none;
    font-family:'Poppins',sans-serif; color:#0D3B72; transition:.2s;
   
    line-height: 64px;
    padding: 0;

  }
  .otp-inputs input:focus { border-color:#0D3B72; box-shadow:0 0 0 3px rgba(13,59,114,.1); }
  .resend-row { text-align:center; font-size:13px; color:#8a8fa3; margin-top:12px; }
  .resend-row button { background:none; border:none; color:#1565C0; font-weight:500;
    cursor:pointer; font-family:inherit; font-size:13px; padding:0; }
  .resend-row button:disabled { color:#8a8fa3; cursor:default; }
  #countdown { font-weight:600; color:#0D3B72; }
  .branding .logo {
    width:70px;
    height:70px;
    object-fit:contain;
    border:none;
    border-radius:0;
    background:none;
    padding:0;
}

.logo {
    background: transparent;
    display: flex;
}
</style>
</head>
<body>

<div class="login-container">

    <div class="left-panel">
        <div class="circle circle-top"></div>
        <div class="circle circle-left"></div>
        <div class="branding">
            <img src="{{ asset('images/polyclinic_logo.png') }}" class="logo" alt="PolyClinic Logo"
                 onerror="this.onerror=null;this.style.display='none'">
            <div><h2>PolyClinic</h2><p>Inquiry System · Smart Care</p></div>
        </div>
        <div class="welcome">
            <h1>Check Your<span> Email</span></h1>
            <p>We've sent a 6-digit verification code to your email address.</p>
        </div>
        <small>© PolyClinic Health — secure portal</small>
    </div>

    <div class="right-panel">
        <div class="form-box">
            <h2>Enter Verification Code</h2>
            <p class="subtitle">Step 2 of 3 — Sent to {{ session('reset_email') }}</p>

            @if (session('success'))
                <div class="error-message show" style="background:#e8f5e9;color:#2e7d32;border-color:#a5d6a7;">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="error-message show">
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
                               class="otp-digit" id="d{{ $i }}" autocomplete="off">
                    @endfor
                </div>

                <button type="submit" id="verify-btn">Verify Code →</button>
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

            <p style="text-align:center;margin-top:20px;font-size:13px;color:#8a8fa3;">
                <a href="{{ route('password.forgot') }}" style="color:#1565C0;font-weight:500;">← Use a different email</a>
            </p>
        </div>
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