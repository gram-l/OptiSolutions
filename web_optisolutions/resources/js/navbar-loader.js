(function loadNavbar() {

  const navbarHTML = `
    <nav class="navbar">
      <div class="navbar-inner">

        <div class="navbar-logo-wrapper">
          <span class="navbar-logo">PolyClinic</span>
          <span class="navbar-location-badge">Lipa City</span>
        </div>

        <button class="navbar-toggle" id="navbarToggle" type="button" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="navbarMenu">
          <span class="navbar-toggle-bar"></span>
          <span class="navbar-toggle-bar"></span>
          <span class="navbar-toggle-bar"></span>
        </button>

        <div class="navbar-nav" id="navbarMenu">
          <a href="/home" class="nav-link" data-page="home">Home</a>
          <a href="/about" class="nav-link" data-page="about">About Us</a>
          <a href="/services" class="nav-link" data-page="services">Services</a>
          <a href="/doctors" class="nav-link" data-page="doctors">Doctors</a>
          <a href="/contact" class="nav-link" data-page="contact">Contact</a>

          <!-- <button class="nav-cta" onclick="window.location.href='/auth/login'">
            Login as Admin/Staff
          </button> -->
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

      /* IMPORTANT: overflow-x: hidden goes on <html> ONLY, never on <body>.
         Setting overflow-x (without overflow-y) on an element forces the
         browser to compute that element's overflow-y as "auto" too. On
         <html> this is harmless — the root element's overflow is applied
         to the viewport itself instead of creating a real inner scroll box.
         But on <body>, that forced overflow-y:auto turns <body> into its
         own scroll container. The sticky .navbar (a child of body) then
         sticks relative to THAT container instead of the real viewport —
         and since body never actually scrolls on its own, the navbar just
         scrolls away like a normal element. This was the exact cause of
         the navbar "disappearing" on scroll. Do not add overflow-x back
         onto body. */
      html {
        overflow-x: hidden;
      }

      html, body {
        margin: 0;
        padding: 0;
        width: 100%;
      }

      /* ============ NAVBAR SHELL ============ */

      .navbar {
        width: 100%;
        background: #ffffff;
        border-bottom: 1px solid var(--nav-border);
        box-sizing: border-box;
        position: sticky;
        top: 0;
        z-index: 100;
        padding: 14px 32px;
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

      /* ============ MOBILE MENU TOGGLE (hidden on desktop) ============ */

      .navbar-toggle {
        display: none;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 5px;
        width: 42px;
        height: 42px;
        margin-left: auto;
        padding: 0;
        background: transparent;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        flex-shrink: 0;
        transition: background 0.15s ease;
      }

      .navbar-toggle:hover {
        background: var(--nav-primary-tint);
      }

      .navbar-toggle-bar {
        display: block;
        width: 22px;
        height: 2px;
        background: var(--nav-primary);
        border-radius: 2px;
        transition: transform 0.25s ease, opacity 0.2s ease;
      }

      .navbar-toggle.is-open .navbar-toggle-bar:nth-child(1) {
        transform: translateY(7px) rotate(45deg);
      }

      .navbar-toggle.is-open .navbar-toggle-bar:nth-child(2) {
        opacity: 0;
      }

      .navbar-toggle.is-open .navbar-toggle-bar:nth-child(3) {
        transform: translateY(-7px) rotate(-45deg);
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
        margin-top: 16px;
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

      /* ============ HAMBURGER MODE (phones & small tablets only) ============
         Below this width the links no longer fit in one row, so instead of
         letting them get clipped/scroll sideways, they collapse into a
         dropdown opened by the hamburger button. Nothing above 768px is
         touched, so laptop/desktop layout is unaffected. */

      @media (max-width: 768px) {
        .navbar-inner {
          flex-wrap: wrap;
          overflow: visible;
          white-space: normal;
          position: relative;
        }

        .navbar-toggle {
          display: flex;
        }

        .navbar-nav {
          order: 3;
          width: 100%;
          flex-direction: column;
          align-items: stretch;
          gap: 0;
          margin-left: 0;
          max-height: 0;
          opacity: 0;
          overflow: hidden;
          padding: 0 4px;
          transition: max-height 0.3s ease, opacity 0.2s ease, padding 0.3s ease;
        }

        .navbar-nav.is-open {
          max-height: 500px;
          opacity: 1;
          padding: 10px 4px 18px;
        }

        .nav-link {
          width: 100%;
          padding: 13px 8px;
          border-bottom: 1px solid var(--nav-border);
        }

        .nav-link::after {
          display: none;
        }

        .nav-cta {
          width: 100%;
          margin-top: 12px;
          border-radius: 10px;
          padding: 13px;
          text-align: center;
        }
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

  /* ============ MOBILE MENU TOGGLE BEHAVIOR ============
     Wires up the hamburger button so it actually opens/closes the
     collapsed nav on phones/tablets (<=768px). Has no effect on
     desktop, where .navbar-toggle stays display:none. */

  const toggleBtn = document.getElementById('navbarToggle');
  const menu = document.getElementById('navbarMenu');

  if (toggleBtn && menu) {
    const closeMenu = () => {
      toggleBtn.classList.remove('is-open');
      menu.classList.remove('is-open');
      toggleBtn.setAttribute('aria-expanded', 'false');
    };

    const openMenu = () => {
      toggleBtn.classList.add('is-open');
      menu.classList.add('is-open');
      toggleBtn.setAttribute('aria-expanded', 'true');
    };

    toggleBtn.addEventListener('click', () => {
      const isOpen = menu.classList.contains('is-open');
      isOpen ? closeMenu() : openMenu();
    });

    // Tapping a link (or the login button) inside the open mobile
    // menu should close it before navigating away.
    menu.querySelectorAll('a, button').forEach(el => {
      el.addEventListener('click', closeMenu);
    });

    // If the viewport is resized/rotated back to desktop width while
    // the menu happens to be open, reset it so it doesn't get stuck
    // open underneath the now-hidden hamburger button.
    window.addEventListener('resize', () => {
      if (window.innerWidth > 768) closeMenu();
    });
  }

  /* ============ SCROLLSPY: keep the active tab in sync while scrolling ============
     On one-page layouts (like /home) built from stacked <section id="..."> blocks
     that match the nav's data-page values (home, about, services, doctors), the
     highlighted tab should follow whichever section is currently under the sticky
     navbar as the user scrolls — not stay stuck on whichever page first loaded.
     On pages that don't have multiple matching sections, this simply does nothing
     and the normal per-page "active" class set above is left alone. */

  const spyLinks = Array.from(document.querySelectorAll('.navbar .nav-link[data-page]'));
  const spySections = spyLinks
    .map(link => document.getElementById(link.dataset.page))
    .filter(Boolean);

  if (spySections.length > 1) {
    const setActiveLink = (page) => {
      spyLinks.forEach(link => {
        link.classList.toggle('active', link.dataset.page === page);
      });
    };

    const onScrollSpy = () => {
      const navbarEl = document.querySelector('.navbar');
      const offset = (navbarEl ? navbarEl.offsetHeight : 0) + 10;

      let current = spySections[0].id;
      spySections.forEach(section => {
        if (section.getBoundingClientRect().top - offset <= 0) {
          current = section.id;
        }
      });
      setActiveLink(current);
    };

    let ticking = false;
    window.addEventListener('scroll', () => {
      if (!ticking) {
        window.requestAnimationFrame(() => {
          onScrollSpy();
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });

    onScrollSpy();
  }

})();