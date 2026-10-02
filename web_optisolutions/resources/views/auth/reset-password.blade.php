<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password – PolyClinic</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@vite('resources/css/login.css')
</head>
<body>

<div class="login-container">

    <!-- LEFT SIDE: clinic photo -->
    <div class="left-panel">
        <img src="{{ asset('images/Polyclinic.jpg') }}" alt="PolyClinic building">
        <div class="photo-caption"><strong>Create New Password</strong><span>Choose a strong password to keep your account secure.</span></div>
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
                <h1>Set New Password</h1>
                <p class="subtitle">Step 3 of 3 — Almost done!</p>

                @if ($errors->any())
                    <div class="error-message show" role="alert">
                        {{ $errors->first('password') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.reset.update') }}" autocomplete="off">
                    @csrf

                    <div class="field">
                        <label for="password">New Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password"
                                   placeholder="At least 8 characters"
                                   autocomplete="new-password" required>
                            <button type="button" class="toggle-eye" aria-label="Show password" onclick="togglePw('password', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="strength-bar"><div class="strength-bar-fill" id="strength-fill"></div></div>
                        <div class="strength-label" id="strength-label"></div>
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirm New Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   placeholder="Repeat your password"
                                   autocomplete="new-password" required>
                            <button type="button" class="toggle-eye" aria-label="Show password" onclick="togglePw('password_confirmation', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div id="match-msg" class="match-msg"></div>
                    </div>

                    <button type="submit" class="btn-login">Update Password →</button>
                </form>
            </div>
        </main>

        <footer class="page-footer">© {{ date('Y') }} PolyClinic Health · Authorized personnel only</footer>
    </div>
</div>

<script>
function togglePw(id, btn) {
    
    const input = document.getElementById(id);
    const icon = btn.querySelector("i");

    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("bi-eye", "bi-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("bi-eye-slash", "bi-eye");
    
}
}

/*At least 8 characters	+1
Contains at least one uppercase letter (A–Z)	+1
Contains at least one number (0–9)	+1
Contains at least one special character (!@#$%^&*(), etc.)	+1*/
const pwInput   = document.getElementById('password');
const fill      = document.getElementById('strength-fill');
const label     = document.getElementById('strength-label');
const matchMsg  = document.getElementById('match-msg');
const confInput = document.getElementById('password_confirmation');

pwInput.addEventListener('input', () => {
    const v = pwInput.value;
    let score = 0;
    if (v.length >= 8)            score++;
    if (/[A-Z]/.test(v))          score++;
    if (/[0-9]/.test(v))          score++;
    if (/[^A-Za-z0-9]/.test(v))   score++;

    const levels = [
        { w:'0%',   color:'#eee',       text:'' },
        { w:'25%',  color:'#ef5350',    text:'Weak' },
        { w:'50%',  color:'#ff9800',    text:'Fair' },
        { w:'75%',  color:'#42a5f5',    text:'Good' },
        { w:'100%', color:'#43a047',    text:'Strong' },
    ];
    fill.style.width       = levels[score].w;
    fill.style.background  = levels[score].color;
    label.textContent      = levels[score].text;
    label.style.color      = levels[score].color;
});

confInput.addEventListener('input', () => {
    if (!confInput.value) { matchMsg.textContent = ''; return; }
    if (confInput.value === pwInput.value) {
        matchMsg.textContent = '✓ Passwords match';
        matchMsg.style.color = '#43a047';
    } else {
        matchMsg.textContent = '✗ Passwords do not match';
        matchMsg.style.color = '#ef5350';
    }
});
</script>

</body>
</html>