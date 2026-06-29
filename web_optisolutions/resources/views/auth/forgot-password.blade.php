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

    <!-- LEFT SIDE (same as login) -->
    <div class="left-panel">
        <div class="circle circle-top"></div>
        <div class="circle circle-left"></div>
        <div class="branding">
            <img src="{{ asset('images/polyclinic_logo.png') }}" class="logo" alt="PolyClinic Logo"
                 onerror="this.onerror=null;this.style.display='none'">
            <div><h2>PolyClinic</h2><p>Inquiry System · Smart Care</p></div>
        </div>
        <div class="welcome">
            <h1>Reset Your<span> Password</span></h1>
            <p>Enter your institutional email and we'll send you a verification code.</p>
        </div>
        <small>© PolyClinic Health — secure portal</small>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right-panel">
        <div class="form-box">
            <h2>Forgot Password</h2>
            <p class="subtitle">Step 1 of 3 — Enter your email</p>

            @if (session('success'))
                <div class="error-message show" style="background:#e8f5e9;color:#2e7d32;border-color:#a5d6a7;">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="error-message show">
                    {{ $errors->first('email') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.otp.send') }}" autocomplete="off">
                @csrf

                <label style="font-size:13px;color:#4a5568;font-weight:500;">Email Address</label>
                <input type="email"
                       name="email"
                       placeholder="Enter your institutional email"
                       value="{{ old('email') }}"
                       autocomplete="off"
                       required>

                <button type="submit">Send Verification Code →</button>
            </form>

            <p style="text-align:center;margin-top:20px;font-size:13px;color:#8a8fa3;">
                Remember your password?
                <a href="/auth/login" style="color:#1565C0;font-weight:500;">Back to Sign In</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>