

(function loadNavbar() {
  const navbarHTML = `
  <nav class="navbar">
    <div class="navbar-inner">
      <div class="navbar-logo-wrapper">
        <img src="/images/polyclinic_logo.png" alt="PolyClinic Logo" class="navbar-logo-img">
        <div class="navbar-logo">PolyClinic <small>Lipa City</small></div>
      </div>
      <div class="navbar-nav">
        <a href="/home" class="nav-link" data-page="home">Home</a>
        <a href="/about" class="nav-link" data-page="about">About Us</a>
        <a href="/services" class="nav-link" data-page="services">Services</a>
        <a href="/doctors" class="nav-link" data-page="doctors">Doctors</a>
        <a href="/contact" class="nav-link" data-page="contact">Contact</a>
        <button class="nav-cta" onclick="window.location.href='/auth/login'">Login as Admin/Staff</button>
      </div>
    </div>
  </nav>`;

  // Insert as the very first element in <body>
  document.body.insertAdjacentHTML('afterbegin', navbarHTML);

  // Auto-highlight the active nav link based on current path
  const currentPage = window.location.pathname.replace('/', '') || 'home';

  document.querySelectorAll('.navbar .nav-link[data-page]').forEach(link => {
    if (link.dataset.page === currentPage) {
      link.classList.add('active');
    }
  });
})();