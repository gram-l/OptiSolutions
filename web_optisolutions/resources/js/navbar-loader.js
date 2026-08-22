(function loadNavbar() {

  const navbarHTML = `
    <nav class="navbar">
      <div class="navbar-inner">

        <div class="navbar-logo-wrapper">
          <span class="navbar-logo">PolyClinic</span>
          <span class="navbar-location-badge">Lipa City</span>
        </div>

        <div class="navbar-nav">
          <a href="/home" class="nav-link" data-page="home">Home</a>
          <a href="/about" class="nav-link" data-page="about">About Us</a>
          <a href="/services" class="nav-link" data-page="services">Services</a>
          <a href="/doctors" class="nav-link" data-page="doctors">Doctors</a>
          <a href="/contact" class="nav-link" data-page="contact">Contact</a>

          <button class="nav-cta" onclick="window.location.href='/auth/login'">
            Login as Admin/Staff
          </button>
        </div>

      </div>

      <div class="navbar-accent">
        <img src="/images/navbar-accent.png" alt="" class="navbar-accent-image">
      </div>
    </nav>
  `;

  const navbarStyles = `
    <style id="navbar-accent-styles">

      :root {
        --nav-primary: #0F67B3;
        --nav-primary-dark: #0A4B82;
        --nav-primary-tint: #EFF7FF;
        --nav-text: #2C3E50;
        --nav-border: #E4E9F0;
      }

      /* ============ GLOBAL RESET (so the accent strip is always full-bleed,
         even on pages that don't already reset body margin) ============ */

      html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        overflow-x: hidden;
      }

      /* ============ NAVBAR SHELL ============ */

      .navbar {
        width: 100%;
        background: #ffffff;
        border-bottom: 1px solid var(--nav-border);
        box-sizing: border-box;
      }

      .navbar-inner {
        width: 100%;
        padding: 16px 0 16px 40px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: nowrap;
        white-space: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
        box-sizing: border-box;
      }

      .navbar-inner::-webkit-scrollbar {
        display: none;
      }

      /* ============ LOGO ============ */

      .navbar-logo-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
      }

      .navbar-logo {
        color: var(--nav-primary);
        font-weight: 800;
        font-size: 23px;
        letter-spacing: -0.3px;
      }

      .navbar-location-badge {
        background: var(--nav-primary-tint);
        color: var(--nav-primary);
        font-size: 12.5px;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
      }

      /* ============ NAV LINKS ============ */

      .navbar-nav {
        display: flex;
        align-items: center;
        gap: 30px;
        margin-left: auto;
        flex-shrink: 0;
        height: 100%;
      }

      .nav-link {
        position: relative;
        color: var(--nav-text);
        font-size: 15px;
        font-weight: 500;
        text-decoration: none;
        line-height: 1;
        padding: 6px 0;
        display: inline-flex;
        align-items: center;
        transition: color 0.15s ease;
      }

      /* Single underline, drawn as a pseudo-element so it never
         doubles up with the link's own box or wraps to a second line. */
      .nav-link::after {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        bottom: -1px;
        height: 2px;
        background: var(--nav-primary);
        border-radius: 1px;
        transform: scaleX(0);
        transform-origin: center;
        transition: transform 0.2s ease;
      }

      .nav-link:hover {
        color: var(--nav-primary);
      }

      .nav-link:hover::after {
        transform: scaleX(1);
      }

      .nav-link.active {
        color: var(--nav-primary);
        font-weight: 700;
      }

      .nav-link.active::after {
        transform: scaleX(1);
      }

      /* ============ LOGIN BUTTON ============ */

      .nav-cta {
        background: var(--nav-primary);
        color: #ffffff;
        border: none;
        border-radius: 30px 0 0 30px;
        padding: 11px 24px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        flex-shrink: 0;
        transition: background 0.15s ease, transform 0.15s ease;
      }

      .nav-cta:hover {
        background: var(--nav-primary-dark);
        transform: translateY(-1px);
      }

      /* ============ ACCENT STRIP (full-bleed, no side white space) ============ */

      .navbar-accent {
        width: 100vw;
        max-width: 100vw;
        margin-left: calc(-50vw + 50%);
        height: 49px;
        overflow: hidden;
        line-height: 0;
        font-size: 0;
      }

      .navbar-accent-image {
        width: 100%;
        height: 49px;
        display: block;
        object-fit: cover;
        object-position: center;
      }

      /* ============ RESPONSIVE ============ */

      @media (max-width: 1100px) {
        .navbar-inner { padding-left: 25px; }
        .navbar-nav { gap: 20px; }
        .navbar-logo { font-size: 21px; }
      }

      @media (max-width: 900px) {
        .navbar-inner { padding-left: 20px; }
        .navbar-nav { gap: 18px; }
        .navbar-logo { font-size: 20px; }
        .nav-link { font-size: 14px; }
        .nav-cta { font-size: 13px; padding: 9px 18px; }
      }

      @media (max-width: 640px) {
        .navbar-inner { padding-left: 15px; }
        .navbar-nav { gap: 14px; }
        .navbar-logo { font-size: 18px; }
        .navbar-location-badge { font-size: 11px; padding: 3px 9px; }
        .nav-link { font-size: 13px; }
        .nav-cta { font-size: 12px; padding: 8px 15px; }
        .navbar-accent, .navbar-accent-image { height: 35px; }
      }

    </style>
  `;

  document.head.insertAdjacentHTML('beforeend', navbarStyles);
  document.body.insertAdjacentHTML('afterbegin', navbarHTML);

  const currentPage = window.location.pathname.replace('/', '') || 'home';

  document
    .querySelectorAll('.navbar .nav-link[data-page]')
    .forEach(link => {
      if (link.dataset.page === currentPage) {
        link.classList.add('active');
      }
    });

})();