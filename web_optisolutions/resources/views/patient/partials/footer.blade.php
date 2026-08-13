<!-- ========== FOOTER ========== -->
<footer class="footer-modern">
  <div class="container footer-modern-inner">
    <div class="footer-modern-top">
      <div class="footer-modern-brand">
        <img src="/images/polyclinic_logo.png" alt="PolyClinic Logo" class="footer-modern-logo">
        <span class="footer-modern-brandname">PolyClinic</span>
      </div>

      <div class="footer-modern-col">
        <h4>About Us</h4>
        <a href="{{ route('about') ?? '#' }}">About PolyClinic</a>
        <a href="{{ route('doctors') }}">Our Doctors</a>
      </div>

      <div class="footer-modern-col">
        <h4>Services</h4>
        <a href="{{ route('services') }}#pediatrics">Pediatrics</a>
        <a href="{{ route('services') }}#obgyne">OB-Gyne</a>
        <a href="{{ route('services') }}#surgery">Surgery</a>
        <a href="{{ route('services') }}">All Services</a>
      </div>

      <div class="footer-modern-col">
        <h4>Clinic</h4>
        <a href="{{ route('contact') }}">Visit Us</a>
        <a href="{{ route('contact') }}">Clinic Hours</a>
        <a href="#" onclick="openChatForSchedule(); return false;">Schedule Visit</a>
      </div>

      <div class="footer-modern-col">
        <h4>Contact Us</h4>
        <a href="tel:{{ $clinicInfo->contact_no ?? '09854755511' }}">{{ $clinicInfo->contact_no ?? '0985 475 5511' }}</a>
        <a href="#" onclick="openFaqModal(); return false;">FAQs</a>
        <a href="#" onclick="openChatForReview(); return false;">Leave Feedback</a>
      </div>

      <div class="footer-modern-social">
        <a href="https://www.facebook.com/polyclinicLipa" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
          <i class="fab fa-facebook"></i>
        </a>
      </div>
    </div>

    <div class="footer-modern-bottom">
      <p>© 2026 PolyClinic Lipa · TM Kalaw St., Lipa City, Batangas 4217</p>
    </div>
  </div>
</footer>

<!-- ========== FAQ MODAL ========== -->
<div id="faqModal" class="faq-modal">
  <div class="faq-modal-content">
    <button class="faq-modal-close" onclick="closeFaqModal()" aria-label="Close"><i class="fas fa-times"></i></button>
    <h3 class="faq-modal-title">Frequently Asked Questions</h3>

    <div class="faq-list">
      <div class="faq-item">
        <button class="faq-question" type="button">
          What are your clinic's operating hours?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>We're open Monday to Friday, 8:00 AM – 6:00 PM, and Saturday, 9:00 AM – 1:00 PM. We're closed on Sundays.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          Do you accept walk-in patients?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Yes, walk-ins are welcome! That said, we recommend scheduling a visit ahead of time through our chatbot so you can lock in a slot with your preferred doctor.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          How do I schedule a visit?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Click "Schedule Visit" here in the footer, or open the chat widget and tap "Schedule Visit" — you'll be guided step by step through choosing a service, doctor, and time slot, no phone call needed.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          What services and specialties do you offer?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>We offer Pediatrics, OB-Gyne, Surgery, IM-Pulmonology, Ophthalmology/ENT, IM-Cardiology, and General/Adult Medicine.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          Where is PolyClinic Lipa located?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>We're at TM Kalaw St., Lipa City, Batangas 4217. You can check the map or tap "Get Directions" on our contact section.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          How can I contact the clinic?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Call or text us at 0985 475 5511, or message us anytime through the chat widget — our assistant can answer most questions right away.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          How do I leave feedback or file a complaint?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Click "Leave Feedback" here in the footer, or type "Submit Review/Rating" or "Submit Complaint" directly in the chatbot.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  /* Self-contained footer styling. Previously the .footer-modern-*
     classes were only styled inside home.css (and partly about.css),
     so pages loading a different stylesheet (doctors.css, services.css)
     rendered this same markup completely unstyled — the logo image at
     full native size, links as default browser blue/purple text, no
     background box, columns not laid out. Defining every rule this
     partial needs right here means the footer looks identical on
     every page no matter which page-specific CSS file is loaded. */
  .footer-modern {
    background: #0f1f3d;
    color: #cbd5e1;
    padding: 56px 0 24px;
    margin-top: 48px;
  }
  .footer-modern-inner { width: 100%; }
  .footer-modern-top {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
    justify-content: space-between;
    padding-bottom: 32px;
  }
  .footer-modern-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1 1 220px;
  }
  .footer-modern-logo {
    width: 40px;
    height: 40px;
    object-fit: contain;
    flex-shrink: 0;
  }
  .footer-modern-brandname {
    font-family: "Plus Jakarta Sans", sans-serif;
    font-weight: 800;
    font-size: 20px;
    color: #ffffff;
  }
  .footer-modern-col {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 140px;
  }
  .footer-modern-col h4 {
    font-family: "Plus Jakarta Sans", sans-serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #ffffff;
    margin: 0 0 4px;
  }
  .footer-modern-col a {
    font-family: "Inter", sans-serif;
    font-size: 14px;
    color: #94a3b8;
    text-decoration: none;
    transition: color 0.15s ease;
  }
  .footer-modern-col a:hover { color: #ffffff; }
  .footer-modern-social {
    display: flex;
    gap: 12px;
    align-items: flex-start;
  }
  .footer-modern-social a {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    text-decoration: none;
    transition: background 0.15s ease;
    flex-shrink: 0;
  }
  .footer-modern-social a:hover { background: rgba(255, 255, 255, 0.2); }
  .footer-modern-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 20px;
    text-align: center;
  }
  .footer-modern-bottom p {
    margin: 0;
    font-family: "Inter", sans-serif;
    font-size: 13px;
    color: #64748b;
  }

  /* Kept scoped/self-contained here rather than added to home.css,
     so this partial can be dropped into any page without depending
     on page-specific styles. */
  .faq-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }
  .faq-modal.open { display: flex; }
  .faq-modal-content {
    background: #fff;
    border-radius: 16px;
    max-width: 640px;
    width: 100%;
    max-height: 80vh;
    overflow-y: auto;
    padding: 32px;
    position: relative;
  }
  .faq-modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: none;
    border: none;
    font-size: 18px;
    color: #64748b;
    cursor: pointer;
    padding: 8px;
    line-height: 1;
  }
  .faq-modal-close:hover { color: #0f172a; }
  .faq-modal-title {
    font-family: "Plus Jakarta Sans", sans-serif;
    font-weight: 800;
    font-size: 22px;
    color: #0f172a;
    margin: 0 0 20px;
  }
  .faq-item {
    border-bottom: 1px solid #e2e8f0;
  }
  .faq-item:last-child { border-bottom: none; }
  .faq-question {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: none;
    border: none;
    text-align: left;
    padding: 16px 4px;
    font-family: "Inter", sans-serif;
    font-weight: 600;
    font-size: 15px;
    color: #0f172a;
    cursor: pointer;
  }
  .faq-question i { transition: transform 0.2s ease; color: #64748b; flex-shrink: 0; }
  .faq-item.open .faq-question i { transform: rotate(180deg); }
  .faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.25s ease;
  }
  .faq-answer p {
    margin: 0 4px 16px;
    font-family: "Inter", sans-serif;
    font-size: 14px;
    line-height: 1.6;
    color: #475569;
  }
  .faq-item.open .faq-answer { max-height: 200px; }
</style>

<script>
  // ---- Chatbot shortcuts used throughout the site (hero card + footer) ----

  function openChatForReview() {
    if (window.openChatbotAndSubmitFeedback) {
      window.openChatbotAndSubmitFeedback();
    } else {
      document.getElementById('chatbotTrigger')?.click();
    }
  }

  function openChatForSchedule() {
    if (window.openChatbotAndScheduleVisit) {
      window.openChatbotAndScheduleVisit();
    } else {
      document.getElementById('chatbotTrigger')?.click();
    }
  }

  // ---- FAQ modal ----

  function openFaqModal() {
    document.getElementById('faqModal')?.classList.add('open');
  }

  function closeFaqModal() {
    document.getElementById('faqModal')?.classList.remove('open');
  }

  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('faqModal');

    // Close when clicking the dark backdrop (not the card itself).
    modal?.addEventListener('click', (e) => {
      if (e.target === modal) closeFaqModal();
    });

    // Close on Escape.
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeFaqModal();
    });

    // Accordion toggle for each question.
    document.querySelectorAll('.faq-item .faq-question').forEach(btn => {
      btn.addEventListener('click', () => {
        btn.closest('.faq-item').classList.toggle('open');
      });
    });
  });
</script>