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
<style>
.password-wrapper{
    position: relative;
}

.password-wrapper input{
    width: 100%;
    padding-right: 50px;
}

.toggle-eye{
    
    position: absolute;
    top: 40%;
    right: 14px;
    transform: translateY(-50%);

    display: flex;
    align-items: center;
    justify-content: center;

    width: 24px;
    height: 24px;

    padding: 0;
    margin: 0;

    border: none;
    background: transparent;
    outline: none;
    box-shadow: none;

    color: #8a8fa3;
    cursor: pointer;
    font-size: 18px;
    transition: color .2s ease;
}

.toggle-eye i{
    font-size: 18px;
    line-height: 1;
}

.toggle-eye:hover{
    color: #0D3B72;
    background: transparent;
}

.toggle-eye:focus,
.toggle-eye:active{
    outline: none;
    box-shadow: none;
    background: transparent;
}

.strength-bar{
    height: 4px;
    border-radius: 4px;
    margin: 8px 0 4px;
    background: #eee;
}

.strength-bar-fill{
    height: 100%;
    border-radius: 4px;
    transition: .3s;
    width: 0;
}

.strength-label{
    font-size: 11px;
    color: #8a8fa3;
}
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
            <div class="branding-text">
                <h2>PolyClinic</h2>
                <p>Inquiry System · Smart Care</p>
            </div>
        </div>
        <div class="welcome">
            <h1>Create New<span> Password</span></h1>
            <p>Choose a strong password to keep your account secure.</p>
        </div>
        <small>© PolyClinic Health — secure portal</small>
    </div>

    <div class="right-panel">
        <div class="form-box">
            <h2>Set New Password</h2>
            <p class="subtitle">Step 3 of 3 — Almost done!</p>

            @if ($errors->any())
                <div class="error-message show">
                    {{ $errors->first('password') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.reset.update') }}" autocomplete="off">
                @csrf

                <label style="font-size:13px;color:#4a5568;font-weight:500;">New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password"
                           placeholder="At least 8 characters"
                           autocomplete="new-password" required>
                    <button type="button" class="toggle-eye" onclick="togglePw('password', this)">
    <i class="bi bi-eye"></i>
</button>
                </div>
                <div class="strength-bar"><div class="strength-bar-fill" id="strength-fill"></div></div>
                <div class="strength-label" id="strength-label"></div>

                <label style="font-size:13px;color:#4a5568;font-weight:500;margin: top 14px;px;display:block;">
                    Confirm New Password
                </label>
                <div class="password-wrapper">
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           placeholder="Repeat your password"
                           autocomplete="new-password" required>
                    <button type="button" class="toggle-eye" onclick="togglePw('password_confirmation', this)">
    <i class="bi bi-eye"></i>
</button>
                </div>
                <div id="match-msg" style="font-size:11px;margin-top:4px;"></div>

                <button type="submit" style="margin-top:20px;">Update Password →</button>
            </form>
        </div>
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