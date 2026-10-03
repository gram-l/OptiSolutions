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
<body>

<div class="login-container">

    <!-- LEFT SIDE: clinic photo -->
    <div class="left-panel">
        <img src="{{ asset('images/Polyclinic.jpg') }}" alt="PolyClinic building">
        <div class="photo-caption"><strong>Secure staff portal</strong></div>
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
                <h1>Access Account</h1>
                <p class="subtitle">Log in with your institutional account</p>

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

                <form method="POST" action="/auth/login" autocomplete="on">
                    @csrf

                    <div class="field">
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
                            <input type="password"
                                   id="password"
                                   name="password"
                                   placeholder="Enter your password"
                                   aria-label="Password"
                                   autocomplete="new-password"
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

                <div class="divider"><span>or</span></div>

                <div id="g_id_onload"
                     data-client_id="1041975122502-tjr2cth2cnetpo53o75l4r6gu99tr63g.apps.googleusercontent.com"
                     data-auto_prompt="false"
                     data-callback="handleGoogleLogin">
                </div>
                <div class="g_id_signin" data-type="standard" data-width="320"></div>
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
        const res = await fetch('{{ route("auth.google") }}', {
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
