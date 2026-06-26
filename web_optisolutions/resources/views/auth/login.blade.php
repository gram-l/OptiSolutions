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
            <img src="polyclinic_logo.png" class="logo" alt="PolyClinic Logo" onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='<div class=logo>PC</div>'">

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

    <!-- RIGHT SIDE -->
    <div class="right-panel">
        <div class="form-box">

            <h2>Access Account</h2>
            <p class="subtitle">
                Sign in with your institutional account
            </p>
            <div id="errorMessage" class="error-message"></div>

            <form onsubmit="return validateLogin()" autocomplete="off">
                <input type="email" 
                id="email" 
                name="staff_email"
                placeholder="Enter your email"
                autocomplete="off"
                required>

                <input type="password" 
                id="password" 
                name="staff_password"
                placeholder="Enter your password"
                autocomplete="new-password"
                required>

                <div class="forgot">
                    <a href="#">Forgot password?</a>
                </div>

                <button type="submit">
                    Sign In →
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function validateLogin() {
    // Fixed staff credentials
    const correctEmail = "clinicstaff@gmail.com";
    const correctPassword = "polystaff2026";

    // User input
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const errorDiv = document.getElementById("errorMessage");

    // Validate login
    if (email === correctEmail && password === correctPassword) {
        // Remove any existing error messages
        errorDiv.classList.remove("show");
        errorDiv.textContent = "";
        
        // Redirect directly to Staff Dashboard - NO ALERT
        window.location.href = "/admin_acc/dashboard";
        
        return false;
    } else {
        // Show error message without alert
        errorDiv.textContent = "Invalid email or password. Please try again.";
        errorDiv.classList.add("show");
        return false;
    }
}
</script>
</body>
</html>