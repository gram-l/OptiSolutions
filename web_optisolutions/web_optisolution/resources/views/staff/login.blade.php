<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PolyClinic Staff Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
     @vite(['resources/css/app.css'])
</head>
<body style="min-height: 100vh; background: #edf4f7; display: flex; justify-content: center; align-items: center; padding: 20px;">

<div class="login-container">
    <!-- LEFT PANEL -->
    <div class="left-panel">
        <div class="circle circle-top"></div>
        <div class="circle circle-left"></div>

        <div class="branding">
            <img src="{{ asset('polyclinic_logo.png') }}" alt="PolyClinic Logo" style="width: 55px; height: 55px; border-radius: 50%; object-fit: cover;">
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
            <p>
                Dedicated to delivering exceptional healthcare with compassion and clinical excellence.
            </p>
        </div>

        <small>© PolyClinic Health — secure portal</small>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
        <div class="form-box">
            <h2>Access Account</h2>
            <p class="subtitle">Sign in with your institutional account</p>

            @if($errors->any())
                <div class="error-message show">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('staff.login') }}">
                @csrf
                <input type="email" 
                    name="staff_email" 
                    placeholder="Enter your email" value="{{ old('staff_email') }}" required>

                <input type="password" 
                       id="password" 
                       name="staff_password" 
                       placeholder="Enter your password" 
                       autocomplete="new-password"
                       required>

                <div class="forgot">
                    <a href="#">Forgot password?</a>
                </div>

                <button type="submit">Sign In →</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>