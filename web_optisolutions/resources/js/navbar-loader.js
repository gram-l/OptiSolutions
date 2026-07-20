(function loadNavbar() {
  const navbarHTML = `
  <nav class="navbar">
    <div class="navbar-inner">
      <div class="navbar-logo-wrapper">
        <div class="navbar-logo">PolyClinic</div>
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
  </nav>
  <div class="navbar-accent" aria-hidden="true">
    <div class="navbar-accent-green"></div>
    <div class="navbar-accent-white-bar"></div>
  </div>`;

  const navbarStyles = `
  <style id="navbar-accent-styles">
    .navbar-accent {
      position: relative;
      width: 100%;
      height: 72px;
      background: #17275c;
      overflow: hidden;
    }
    .navbar-accent-green {
      position: absolute;
      top: 0;
      left: 0;
      width: 68%;
      height: 100%;
      background: #1f3d16;
      clip-path: polygon(0 0, 100% 0, 62% 100%, 0% 100%);
    }
    .navbar-accent-white-bar {
      position: absolute;
      top: 68%;
      left: 0;
      width: 22%;
      min-width: 200px;
      height: 8px;
      background: #ffffff;
    }
    @media (max-width: 640px) {
      .navbar-accent { height: 36px; }
      .navbar-accent-white-bar { width: 40%; min-width: 100px; height: 5px; top: 65%; }
    }
  </style>`;

  document.head.insertAdjacentHTML('beforeend', navbarStyles);
  document.body.insertAdjacentHTML('afterbegin', navbarHTML);

  const currentPage = window.location.pathname.replace('/', '') || 'home';

  document.querySelectorAll('.navbar .nav-link[data-page]').forEach(link => {
    if (link.dataset.page === currentPage) {
      link.classList.add('active');
    }
  });
})();