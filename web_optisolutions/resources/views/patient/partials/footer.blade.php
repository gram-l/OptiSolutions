<!-- ========== FOOTER ========== -->
@php($publicClinic = $publicClinic ?? \App\Support\PublicClinicDetails::from($clinicInfo ?? null))
<footer class="footer-modern">
  <div class="container footer-modern-inner">
    <div class="footer-modern-top">
      <div class="footer-modern-brand">
        <div class="footer-modern-brandrow">
          <img src="/images/polyclinic_logo.png" alt="PolyClinic Logo" class="footer-modern-logo">
          <span class="footer-modern-brandname" data-clinic-field="clinic_name" data-clinic-empty="Clinic">{{ $publicClinic['clinic_name'] ?: 'Clinic' }}</span>
        </div>
        <div class="footer-modern-social" data-clinic-visible="facebook_url" @if ($publicClinic['facebook_url'] === '') hidden style="display:none" @endif>
          <a href="{{ $publicClinic['facebook_url'] }}" data-clinic-facebook target="_blank" rel="noopener noreferrer" aria-label="Facebook">
            <i class="fab fa-facebook"></i>
          </a>
        </div>
      </div>

      <div class="footer-modern-col">
        <h4>About Us</h4>
        <a href="#about">About PolyClinic</a>
        <a href="#about">Mission &amp; Vision</a>
        <a href="#doctors">Our Doctors</a>
        <a href="#reviews">Patient Voices</a>
        <a href="{{ route('privacy-notice') }}">Privacy Notice</a>
      </div>

      <div class="footer-modern-col">
        <h4>Services</h4>
        <a href="#services" onclick="openServiceModal('pediatrics'); return false;">Pediatrics</a>
        <a href="#services" onclick="openServiceModal('obgyne'); return false;">OB-Gynecology</a>
        <a href="#services" onclick="openServiceModal('surgery'); return false;">General Surgery</a>
        <a href="#services" onclick="openServiceModal('pulmonology'); return false;">IM-Pulmonology</a>
        <a href="#services" onclick="openServiceModal('ophthalmology'); return false;">Ophthalmology / ENT</a>
        <a href="#services" onclick="openServiceModal('cardiology'); return false;">IM-Cardiology</a>
        <a href="#services" onclick="openServiceModal('adultmedicine'); return false;">General / Adult Medicine</a>
        <a href="#services" onclick="openServiceModal('oncology'); return false;">Medical Oncology</a>
      </div>

      <div class="footer-modern-col">
        <h4>Clinic</h4>
        <a href="#contact">Visit Us</a>
        <a href="#contact">Clinic Hours</a>
        <a href="{{ $publicClinic['directions_url'] }}" data-clinic-directions @if ($publicClinic['directions_url'] === '') hidden style="display:none" @endif target="_blank" rel="noopener noreferrer">Get Directions</a>
        <a href="#" onclick="openChatForSchedule(); return false;">Schedule Visit</a>
      </div>

      <div class="footer-modern-col">
        <h4>Contact Us</h4>
        <a href="{{ $publicClinic['phone_url'] }}" data-clinic-phone @if ($publicClinic['phone_url'] === '') hidden style="display:none" @endif><span data-clinic-field="contact_no">{{ $publicClinic['contact_no'] }}</span></a>
        <a href="#" onclick="openFaqModal(); return false;">FAQs</a>
        <a href="#" onclick="openChatForReview(); return false;">Leave Feedback</a>
        <a href="#doctors">Find Your Specialist</a>
      </div>
    </div>

    <div class="footer-modern-bottom">
      <p>© {{ date('Y') }} <span data-clinic-field="clinic_name" data-clinic-empty="Clinic">{{ $publicClinic['clinic_name'] ?: 'Clinic' }}</span><span data-clinic-visible="address" @if ($publicClinic['address'] === '') hidden style="display:none" @endif> · <span data-clinic-field="address">{{ $publicClinic['address'] }}</span></span></p>
      <a href="{{ route('privacy-notice') }}">Privacy Notice</a>
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
          <p data-clinic-field="operating_hours" data-clinic-empty="Clinic hours have not been set yet.">{{ $publicClinic['operating_hours'] ?: 'Clinic hours have not been set yet.' }}</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          Do you accept walk-in patients?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Yes, but we recommend scheduling a visit ahead of time through our chatbot so you can lock in a slot with your preferred doctor.</p>
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
          <p>We offer General / Adult Medicine, Pediatrics, OB-Gynecology, General Surgery, Ophthalmology / ENT, IM-Cardiology, IM-Pulmonology, and Medical Oncology.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          Where is PolyClinic Lipa located?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p><span data-clinic-field="address" data-clinic-empty="Address has not been set yet.">{{ $publicClinic['address'] ?: 'Address has not been set yet.' }}</span> <span data-clinic-visible="address" @if ($publicClinic['address'] === '') hidden style="display:none" @endif>You can check the map or tap "Get Directions" in our contact section.</span></p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          How can I contact the clinic?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p></span>You can also message us through the chatbot inside of this website.</p><span data-clinic-visible="contact_no" @if ($publicClinic['contact_no'] === '') hidden style="display:none" @endif>When it's emergency, please call or text us at <span data-clinic-field="contact_no">{{ $publicClinic['contact_no'] }}</span>.
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

      <div class="faq-item">
        <button class="faq-question" type="button">
          How do I choose the right doctor?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Use <strong>Find Your Specialist</strong> on our website to browse our doctors and specialties, or choose a service when you tap Schedule Visit. If you are not sure which doctor to see, our staff will gladly help. Please note that the chatbot cannot diagnose your condition.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          How do I reschedule my visit?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Please contact our clinic staff to request a new visit date. They will check your doctor's schedule and email you the updated details once the change is made.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          How do I cancel my visit?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>If you can no longer make it, please let our staff know and give them your visit details so they can assist you with the cancellation.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          What should I bring to my visit?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Please have your visit details and any relevant previous medical records ready. For requirements specific to your doctor or service, ask our staff before you come.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          How much is a consultation?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Fees depend on the doctor and service you choose. Please ask our staff for the current fee before your visit. The chatbot does not confirm fees.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          What payment methods do you accept?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Please confirm the accepted payment methods with our staff before your visit.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          Do you accept HMO or insurance?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Please ask our staff to verify whether your HMO or insurance covers your doctor and service, and whether an authorization is required, before your visit.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          Do you offer laboratory or diagnostic tests?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Please ask our staff to confirm whether your specific test is available, along with its schedule, fee and preparation instructions. Do not assume that fasting is required unless you have been told so.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          How do I get my test results?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Please contact our staff about the availability of your results and the correct way to collect them. For your privacy, avoid posting medical results or sensitive personal details in the chat.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          How do I request my medical records or a medical certificate?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Please request these directly from our staff. They will confirm the identity checks, requirements, availability and any applicable fee.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          Can I ask for a prescription refill?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Please ask your doctor or our staff whether a new consultation is needed. The chatbot cannot prescribe medicine or change your dosage.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          Do you have assistance for seniors, PWD or patients with mobility needs?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>Yes, we want every patient to feel comfortable. Please contact our staff before your visit to discuss the assistance you need so we can confirm the available arrangements.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          Where is my review code?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>After our staff marks your visit as completed, a review code is emailed to the address we have on record. Please check your inbox and spam folder, or ask our staff for help. Each code can be used for one review.</p>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          How is my personal information protected?
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="faq-answer">
          <p>We handle your personal and medical information in line with our <a href="{{ route('privacy-notice') }}">Privacy Notice</a>.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  /* Footer styles live here so every page renders the same footer */
  .footer-modern {
    background: var(--primary, #022D67);
    color: #C9D9E8;
    padding: 56px 0 24px;
    margin-top: 48px;
  }
  .footer-modern-inner { width: 100%; }
  .footer-modern-top {
    display: grid;
    grid-template-columns: 1.7fr 1fr 1.3fr 1fr 1fr;
    gap: 40px;
    align-items: start;
    padding-bottom: 36px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
  }
  .footer-modern-brand { display: flex; flex-direction: column; gap: 16px; }
  .footer-modern-brandrow { display: flex; align-items: center; gap: 12px; }
  .footer-modern-logo { height: 64px; width: auto; object-fit: contain; flex-shrink: 0; }
  .footer-modern-brandname {
    font-family: "Inter", sans-serif;
    font-weight: 800;
    font-size: 22px;
    color: #ffffff;
  }
  .footer-modern-tagline { margin: 0; font-size: 14px; line-height: 1.6; color: #C9D9E8; max-width: 320px; }
  .footer-modern-info { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
  .footer-modern-info li { display: flex; align-items: flex-start; gap: 10px; font-size: 14px; line-height: 1.5; color: #C9D9E8; }
  .footer-modern-info i { width: 16px; margin-top: 3px; color: #ffffff; text-align: center; flex-shrink: 0; }
  .footer-modern-info a { color: #C9D9E8; text-decoration: none; }
  .footer-modern-info a:hover { color: #ffffff; }
  .footer-modern-col { display: flex; flex-direction: column; gap: 10px; min-width: 0; }
  .footer-modern-col h4 {
    font-family: "Inter", sans-serif;
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
    color: #C9D9E8;
    text-decoration: none;
    transition: color 0.15s ease;
  }
  .footer-modern-col a:hover { color: #ffffff; }
  .footer-modern-social { display: flex; gap: 12px; align-items: center; }
  .footer-modern-social a {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    text-decoration: none;
    transition: background 0.15s ease;
    flex-shrink: 0;
  }
  .footer-modern-social a:hover { background: rgba(255, 255, 255, 0.22); }
  .footer-modern-bottom {
    padding-top: 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }
  .footer-modern-bottom p { margin: 0; font-family: "Inter", sans-serif; font-size: 13px; color: #93A6BC; }
  .footer-modern-bottom a { font-size: 13px; color: #C9D9E8; text-decoration: none; }
  .footer-modern-bottom a:hover { color: #ffffff; }
  @media (max-width: 1100px) {
    .footer-modern-top { grid-template-columns: 1fr 1fr 1fr; }
    .footer-modern-brand { grid-column: 1 / -1; }
  }
  @media (max-width: 600px) {
    .footer-modern-top { grid-template-columns: 1fr 1fr; gap: 28px; }
    .footer-modern-bottom { flex-direction: column; text-align: center; }
  }

  /* FAQ modal (self-contained) */
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
    font-family: "Inter", sans-serif;
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