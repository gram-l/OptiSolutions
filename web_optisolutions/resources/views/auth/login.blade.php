<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PolyClinic Staff Login</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://accounts.google.com/gsi/client" async defer></script>
@vite('resources/css/login.css')
</head>
<body class="staff-login">

<div class="login-container">

    <aside class="left-panel">
        <div class="clinic-brand">
            <img class="clinic-mark" src="{{ asset('images/polyclinic_logo.png') }}" alt="PolyClinic logo">
            <h2>PolyClinic</h2>
            <p class="clinic-location">– LIPA –</p>
            <p class="clinic-tagline">Healthier Community. Brighter Tomorrow.</p>
        </div>
        <div class="clinic-photo"><img src="{{ asset('images/Polyclinic.jpg') }}" alt="PolyClinic Lipa building"></div>
    </aside>

    <!-- RIGHT SIDE -->
    <div class="right-panel">

        <main class="form-wrap">
            <div class="form-box">
                <h1>Welcome Back</h1>
                <p class="subtitle">Sign in to your PolyClinic - Lipa staff account.</p>

                {{-- Forced-logout notice (role mismatch / session collision) --}}
                @if (session('unauthorized'))
                    <div class="error-message show" role="alert">
                        {{ session('unauthorized') }}
                    </div>
                @endif

                {{-- Error message --}}
                @if ($errors->any())
                    <div class="error-message show" role="alert">
                        {{ $errors->first('email') }}
                    </div>
                @endif

                {{-- Google login error (shown via JS) --}}
                <div id="google-error" class="error-message" role="alert"></div>

                <div id="g_id_onload"
                     data-client_id="1041975122502-tjr2cth2cnetpo53o75l4r6gu99tr63g.apps.googleusercontent.com"
                     data-auto_prompt="false"
                     data-callback="handleGoogleLogin">
                </div>
                <div class="google-button-wrap">
                    <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="signin_with" data-shape="rectangular" data-width="320"></div>
                </div>
                <div class="divider"><span>OR</span></div>

                <form method="POST" action="/auth/login" autocomplete="on">
                    @csrf

                    <div class="field">
                        <svg class="field-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/></svg>
                        <input type="email"
                               id="email"
                               name="email"
                               placeholder="Enter your email"
                               aria-label="Email address"
                               value="{{ old('email') }}"
                               autocomplete="email"
                               required>
                    </div>

                    <div class="field">
                        <div class="input-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><path d="M12 14v3"/></svg>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   placeholder="Enter your password"
                                   aria-label="Password"
                                   autocomplete="current-password"
                                   required>
                            <button type="button" class="toggle" id="togglePw" aria-label="Show password">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="forgot">
                        <a href="{{ route('password.forgot') }}" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-login">Log In →</button>
                </form>

                <div class="portal-note">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 8 3v6c0 5-4 9-8 11-4-2-8-6-8-11V5l8-3Z"/><path d="m8 12 3 3 5-6"/></svg>
                    <div><strong>Authorized personnel only</strong><span>Staff &amp; Administration Portal</span></div>
                </div>
            </div>
        </main>

        <footer class="page-footer">© {{ date('Y') }} PolyClinic Health · Authorized personnel only</footer>
    </div>
</div>

<script>
document.getElementById('togglePw').addEventListener('click', function () {
    const pw = document.getElementById('password');
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    this.classList.toggle('on', show);
});

async function handleGoogleLogin(response) {
    const errorBox = document.getElementById('google-error');
    errorBox.classList.remove('show');
    errorBox.textContent = '';

    try {
        const res = await fetch('{{ url("/auth/google") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ id_token: response.credential })
        });

        const data = await res.json();

        if (res.ok) {
            window.location.href = data.redirect || '/dashboard';
        } else {
            errorBox.textContent = data.message || 'Google login failed. Please try again.';
            errorBox.classList.add('show');
        }
    } catch (err) {
        errorBox.textContent = 'Something went wrong. Please try again.';
        errorBox.classList.add('show');
    }
}
</script>

</body>
</html>
