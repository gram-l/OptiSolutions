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

    <!-- LEFT SIDE -->
    <div class="left-panel">
        <div class="circle circle-top"></div>
        <div class="circle circle-left"></div>

        <div class="branding">
            <img src="{{ asset('images/polyclinic_logo.png') }}"
     class="logo"
     alt="PolyClinic Logo"
     onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='<div class=logo>PC</div>'">
            <div>
                <h2>PolyClinic</h2>
                <p>Inquiry System · Smart Care</p>
            </div>
        </div>

        <div class="welcome">
            <h1>
                Connected Care,
                <span>Seamless Access</span>
            </h1>
            <p>Dedicated to delivering exceptional healthcare with compassion and clinical excellence.</p>
        </div>

        <small>© PolyClinic Health — secure portal</small>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right-panel">
        <div class="form-box">
            <h2>Access Account</h2>
            <p class="subtitle">Log in with your institutional account</p>

            {{-- Forced-logout notice (role mismatch / session collision) --}}
            @if (session('unauthorized'))
                <div class="error-message show">
                    {{ session('unauthorized') }}
                </div>
            @endif

            {{-- Error message --}}
            @if ($errors->any())
                <div class="error-message show">
                    {{ $errors->first('email') }}
                </div>
            @endif

            {{-- Google login error (shown via JS) --}}
            <div id="google-error" class="error-message"></div>

            <form method="POST" action="/auth/login" autocomplete="on">
                @csrf

                <input type="email"
                       id="email"
                       name="email"
                       placeholder="Enter your email"
                       value="{{ old('email') }}"
                       autocomplete="email"
                       required>

                <input type="password"
                       id="password"
                       name="password"
                       placeholder="Enter your password"
                       autocomplete="new-password"
                       required>

                <div class="forgot">
                    <a href="{{ route('password.forgot') }}">Forgot password?</a>
                </div>

                <button type="submit">Log In →</button>
            </form>

            <div class="divider"><span>or</span></div>

            <div id="g_id_onload"
                 data-client_id="1041975122502-tjr2cth2cnetpo53o75l4r6gu99tr63g.apps.googleusercontent.com"
                 data-callback="handleGoogleLogin">
            </div>
            <div class="g_id_signin" data-type="standard" data-width="100%"></div>

        </div>
    </div>
</div>

<script>
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