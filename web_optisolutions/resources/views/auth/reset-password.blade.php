<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password – PolyClinic</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
@vite('resources/css/login.css')
</head>
<body class="staff-login password-recovery">

<div class="login-container">

    @include('auth.partials.clinic-brand')

    <!-- RIGHT SIDE -->
    <div class="right-panel">

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
                        <div class="input-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><path d="M12 14v3"/></svg>
                            <input type="password" name="password" id="password"
                                   placeholder="At least 8 characters"
                                   autocomplete="new-password" required>
                            <button type="button" class="toggle" aria-label="Show password" onclick="togglePw('password', this)">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <div class="strength-bar"><div class="strength-bar-fill" id="strength-fill"></div></div>
                        <div class="strength-label" id="strength-label"></div>
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirm New Password</label>
                        <div class="input-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><path d="M12 14v3"/></svg>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   placeholder="Repeat your password"
                                   autocomplete="new-password" required>
                            <button type="button" class="toggle" aria-label="Show password" onclick="togglePw('password_confirmation', this)">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <div id="match-msg" class="match-msg"></div>
                    </div>

                    <button type="submit" class="btn-login">Update Password →</button>
                </form>
                @include('auth.partials.portal-note')
            </div>
        </main>

        <footer class="page-footer">© {{ date('Y') }} PolyClinic Health · Authorized personnel only</footer>
    </div>
</div>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    btn.classList.toggle('on', show);
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