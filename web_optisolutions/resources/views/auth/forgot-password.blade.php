<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password – PolyClinic</title>
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
                <h1>Forgot Password</h1>
                <p class="subtitle">Step 1 of 3 — Enter your email</p>

                @if (session('success'))
                    <div class="error-message success show" role="status">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="error-message show" role="alert">
                        {{ $errors->first('email') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.otp.send') }}" autocomplete="off">
                    @csrf

                    <div class="field">
                        <svg class="field-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/></svg>
                        <input type="email"
                               name="email"
                               placeholder="Enter your institutional email"
                               aria-label="Institutional email"
                               value="{{ old('email') }}"
                               autocomplete="off"
                               required>
                    </div>

                    <button type="submit" class="btn-login">Send Verification Code →</button>
                </form>

                <p class="back-link">
                    Remember your password?
                    <a href="/auth/login">Back to Sign In</a>
                </p>
                @include('auth.partials.portal-note')
            </div>
        </main>

        <footer class="page-footer">© {{ date('Y') }} PolyClinic Health · Authorized personnel only</footer>
    </div>
</div>

</body>
</html>