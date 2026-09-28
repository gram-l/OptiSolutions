(function loadPrivacyNotice() {

  const STORAGE_KEY = 'polyclinic_privacy_accepted';
  const COOKIE_KEY = 'polyclinic_cookies_ack';

  // cookieHTML must be declared before the early-return below, since
  // maybeShowCookieBanner() (called from that early return) reads it.
  // Being a `const`, it's in the temporal dead zone until this line runs —
  // declaring it after the early return caused a
  // "Cannot access 'cookieHTML' before initialization" crash for any
  // returning patient who had already accepted the privacy notice.
  const cookieHTML = `
    <div class="cookie-banner" id="cookieBanner">
      <p>
        We use cookies to ensure that we give you the best experience on our
        website. By continuing to browse our site, you are agreeing to our
        use of cookies.
      </p>
      <div class="cookie-actions">
        <button class="cookie-got-it-btn" id="cookieGotItBtn">Got it!</button>
        <a href="/privacy-notice#cookies" class="cookie-read-more">Read more</a>
      </div>
    </div>
  `;

  // Don't show the modal again once the patient has already agreed.
  if (localStorage.getItem(STORAGE_KEY) === 'true') {
    maybeShowCookieBanner();
    return;
  }

  const modalHTML = `
    <div class="privacy-overlay" id="privacyOverlay">
      <div class="privacy-modal" role="dialog" aria-modal="true" aria-labelledby="privacyTitle">

        <h2 class="privacy-title" id="privacyTitle">
          We Have a New General Privacy Notice
        </h2>

        <div class="privacy-body">
          <p>
            PolyClinic respects your privacy. In compliance with the
            <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>,
            our online system collects only the information needed to
            schedule your visit &mdash; your name, contact number, date of
            birth, email, and chosen service/doctor. We do not process
            billing through this system and do not require any
            government-issued ID.
          </p>
          <p>
            Please read the PolyClinic General Privacy Notice to understand
            what information we collect, why we collect it, and your rights
            as a data subject under Philippine law.
          </p>
          <p>
            Click <a href="/privacy-notice" target="_blank" rel="noopener">here</a>
            to read the General Privacy Notice in full.
          </p>
        </div>

        <label class="privacy-checkbox-row">
          <input type="checkbox" id="privacyCheckbox">
          <span>I accept the terms in PolyClinic's General Privacy Notice.</span>
        </label>

        <button class="privacy-agree-btn" id="privacyAgreeBtn" disabled>
          I Agree
        </button>

      </div>
    </div>
  `;

  const styles = `
    <style id="privacy-notice-styles">

      :root {
        --pc-primary: #0F67B3;
        --pc-primary-dark: #0A4B82;
        --pc-text: #2C3E50;
        --pc-muted: #64748B;
        --pc-border: #E4E9F0;
      }

      /* ============ MODAL OVERLAY ============ */

      .privacy-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(3px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px;
        box-sizing: border-box;
        animation: pc-fade-in 0.2s ease;
      }

      @keyframes pc-fade-in {
        from { opacity: 0; }
        to   { opacity: 1; }
      }

      .privacy-modal {
        background: #ffffff;
        width: 100%;
        max-width: 620px;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 16px;
        padding: 40px 44px;
        box-sizing: border-box;
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.25);
        animation: pc-slide-up 0.25s ease;
      }

      @keyframes pc-slide-up {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
      }

      .privacy-title {
        margin: 0 0 20px;
        color: var(--pc-primary);
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -0.2px;
        text-align: center;
      }

      .privacy-body p {
        margin: 0 0 14px;
        color: var(--pc-text);
        font-size: 15px;
        line-height: 1.6;
      }

      .privacy-body a {
        color: var(--pc-primary);
        font-weight: 600;
        text-decoration: none;
      }

      .privacy-body a:hover {
        text-decoration: underline;
      }

      .privacy-checkbox-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin: 22px 0 26px;
        cursor: pointer;
        user-select: none;
      }

      .privacy-checkbox-row input {
        margin-top: 3px;
        width: 16px;
        height: 16px;
        accent-color: var(--pc-primary);
        cursor: pointer;
        flex-shrink: 0;
      }

      .privacy-checkbox-row span {
        color: var(--pc-text);
        font-size: 14px;
        line-height: 1.5;
      }

      .privacy-agree-btn {
        display: block;
        width: 100%;
        background: var(--pc-primary);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        padding: 13px;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 0.3px;
        cursor: pointer;
        transition: background 0.15s ease, transform 0.15s ease, opacity 0.15s ease;
      }

      .privacy-agree-btn:hover:not(:disabled) {
        background: var(--pc-primary-dark);
        transform: translateY(-1px);
      }

      .privacy-agree-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
      }

      /* ============ COOKIE BANNER ============ */

      .cookie-banner {
        position: fixed;
        bottom: 24px;
        right: 24px;
        max-width: 320px;
        background: #1E293B;
        color: #F1F5F9;
        border-radius: 12px;
        padding: 20px 22px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.3);
        z-index: 9998;
        animation: pc-fade-in 0.25s ease;
      }

      .cookie-banner p {
        margin: 0 0 14px;
        font-size: 13.5px;
        line-height: 1.6;
      }

      .cookie-actions {
        display: flex;
        align-items: center;
        gap: 16px;
      }

      .cookie-got-it-btn {
        background: var(--pc-primary);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.15s ease;
      }

      .cookie-got-it-btn:hover {
        background: var(--pc-primary-dark);
      }

      .cookie-read-more {
        color: #CBD5E1;
        font-size: 13px;
        font-weight: 600;
        text-decoration: underline;
      }

      .cookie-read-more:hover {
        color: #ffffff;
      }

      /* ============ RESPONSIVE ============ */

      @media (max-width: 640px) {
        .privacy-modal {
          padding: 28px 24px;
        }

        .privacy-title {
          font-size: 19px;
        }

        .cookie-banner {
          left: 16px;
          right: 16px;
          bottom: 16px;
          max-width: none;
        }
      }

    </style>
  `;

  document.head.insertAdjacentHTML('beforeend', styles);
  document.body.insertAdjacentHTML('beforeend', modalHTML);

  const overlay = document.getElementById('privacyOverlay');
  const checkbox = document.getElementById('privacyCheckbox');
  const agreeBtn = document.getElementById('privacyAgreeBtn');

  checkbox.addEventListener('change', () => {
    agreeBtn.disabled = !checkbox.checked;
  });

  agreeBtn.addEventListener('click', () => {
    if (checkbox.checked) {
      localStorage.setItem(STORAGE_KEY, 'true');
      overlay.remove();
      maybeShowCookieBanner();
    }
  });

  function maybeShowCookieBanner() {
    if (localStorage.getItem(COOKIE_KEY) === 'true') return;

    document.body.insertAdjacentHTML('beforeend', cookieHTML);

    const banner = document.getElementById('cookieBanner');
    const gotItBtn = document.getElementById('cookieGotItBtn');

    gotItBtn.addEventListener('click', () => {
      localStorage.setItem(COOKIE_KEY, 'true');
      banner.remove();
    });
  }

})();