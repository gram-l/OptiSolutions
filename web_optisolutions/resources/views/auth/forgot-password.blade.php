<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password – PolyClinic</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
@vite('resources/css/login.css')
</head>
<body>

<div class="login-container">

    <!-- LEFT SIDE: clinic photo -->
    <div class="left-panel">
        <img src="{{ asset('images/Polyclinic.jpg') }}" alt="PolyClinic building">
        <div class="photo-caption"><strong>Reset Your Password</strong><span>Enter your institutional email and we'll send you a verification code.</span></div>
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
            </div>
        </main>

        <footer class="page-footer">© {{ date('Y') }} PolyClinic Health · Authorized personnel only</footer>
    </div>
</div>

</body>
</html>