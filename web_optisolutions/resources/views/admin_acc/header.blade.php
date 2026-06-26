<div class="header">
    <div class="logo-section">
        <img src="{{ asset('images/polyclinic_logo.png') }}" 
             alt="logo" 
             style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
        <h1>Polyclinic Admin</h1>
    </div>

    <div class="user-info">
        <div class="user-avatar">DL</div>
        <div>
            <div style="font-weight: 600;">Dr. Lara</div>
            <div style="font-size: 0.85rem; color: #7f8c8d;">Administrator</div>
        </div>
        <button class="logout-btn"
    onclick="window.location.href='/auth/login'">
    Logout
</button>
    </div>
</div>