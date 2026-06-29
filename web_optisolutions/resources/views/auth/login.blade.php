<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PolyClinic Staff Login</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            <p class="subtitle">Sign in with your institutional account</p>

            {{-- Error message --}}
            @if ($errors->any())
                <div class="error-message show">
                    {{ $errors->first('email') }}
                </div>
            @endif

            <form method="POST" action="/auth/login" autocomplete="off">
                @csrf

                <input type="email"
                       id="email"
                       name="email"
                       placeholder="Enter your email"
                       value="{{ old('email') }}"
                       autocomplete="off"
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

                <button type="submit">Sign In →</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>