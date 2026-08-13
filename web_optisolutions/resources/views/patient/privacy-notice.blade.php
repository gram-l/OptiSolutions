<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>General Privacy Notice | PolyClinic</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <style>
    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
    }

    .privacy-page {
      padding: 60px 20px 80px;
      background: #f8fafc;
    }

    .privacy-page-container {
      max-width: 760px;
      margin: 0 auto;
      background: #ffffff;
      border-radius: 16px;
      padding: 48px 52px;
      box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
    }

    .privacy-page-title {
      color: #0F67B3;
      font-size: 32px;
      font-weight: 800;
      margin: 0 0 6px;
    }

    .privacy-page-updated {
      color: #64748B;
      font-size: 13px;
      margin: 0 0 24px;
    }

    .privacy-page-intro {
      font-size: 15.5px;
      line-height: 1.7;
      color: #2C3E50;
    }

    .privacy-page-divider {
      border: none;
      border-top: 1px solid #E4E9F0;
      margin: 28px 0;
    }

    .privacy-page h2 {
      color: #0F67B3;
      font-size: 19px;
      font-weight: 700;
      margin: 34px 0 12px;
    }

    .privacy-page p {
      font-size: 15px;
      line-height: 1.7;
      color: #2C3E50;
      margin: 0 0 14px;
    }

    .privacy-page ul {
      margin: 0 0 14px;
      padding-left: 22px;
    }

    .privacy-page li {
      font-size: 15px;
      line-height: 1.7;
      color: #2C3E50;
      margin-bottom: 8px;
    }

    .privacy-page a {
      color: #0F67B3;
      font-weight: 600;
      text-decoration: none;
    }

    .privacy-page a:hover {
      text-decoration: underline;
    }

    .privacy-page-contact {
      list-style: none;
      padding-left: 0;
    }

    .privacy-page-back {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #0F67B3;
      font-weight: 600;
      font-size: 14px;
      text-decoration: none;
      margin-bottom: 24px;
    }

    .privacy-page-back:hover {
      text-decoration: underline;
    }

    @media (max-width: 640px) {
      .privacy-page-container {
        padding: 32px 24px;
      }

      .privacy-page-title {
        font-size: 26px;
      }
    }
  </style>
</head>
<body>

<section class="privacy-page">
  <div class="privacy-page-container">

    <a href="{{ route('home') }}" class="privacy-page-back">
      <i class="fas fa-arrow-left"></i> Back to PolyClinic
    </a>

    <h1 class="privacy-page-title">General Privacy Notice</h1>
    <p class="privacy-page-updated">Last updated: August 2026</p>

    <p class="privacy-page-intro">
      PolyClinic ("PolyClinic," "we," "us," or "our") values your privacy and
      is committed to protecting your personal information. This Privacy
      Notice is issued in compliance with the
      <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>, its
      Implementing Rules and Regulations, and the issuances of the National
      Privacy Commission (NPC). It explains what information our online
      scheduling system collects, why we collect it, how we protect it, and
      what rights you have as a patient using our system.
    </p>

    <hr class="privacy-page-divider">

    <h2>1. What Is Data Privacy?</h2>
    <p>
      Data privacy is your right to control how your personal information is
      collected, used, stored, and shared. Under Philippine law, any
      organization that collects personal data &mdash; including clinics like
      PolyClinic &mdash; is required to handle that information responsibly,
      securely, and only for purposes you have been informed of.
    </p>

    <h2>2. What Personal Data We Collect</h2>
    <p>
      PolyClinic's online system is currently used for <strong>scheduling
      visits only</strong>. We do not process billing or payment through the
      system, and we do not require you to submit any government-issued ID.
      When you schedule a visit through our chatbot, we collect only what is
      needed to book and confirm your appointment:
    </p>
    <ul>
      <li><strong>Name</strong> &mdash; your full name (first and last name).</li>
      <li><strong>Contact number</strong> &mdash; your mobile or landline number.</li>
      <li><strong>Date of birth</strong> &mdash; used to help our staff and doctors identify and prepare for your visit.</li>
      <li><strong>Email address</strong> &mdash; used to send your appointment confirmation and, if you request it, a copy of your conversation transcript.</li>
      <li><strong>Service and doctor selected</strong> &mdash; the type of consultation and specialist you choose to book with.</li>
      <li><strong>Complaint or feedback details</strong> &mdash; only if you choose to file a complaint or leave a review/rating through the chatbot after your appointment is scheduled.</li>
    </ul>

    <h2>3. Why We Collect Your Data</h2>
    <p>We collect and process this information only to:</p>
    <ul>
      <li>Schedule and confirm your appointment with the doctor and service you selected;</li>
      <li>Match you with an available specialist and time slot;</li>
      <li>Send you an appointment confirmation and, if requested, a transcript of your scheduling conversation;</li>
      <li>Record and respond to complaints or feedback you choose to submit; and</li>
      <li>Maintain accurate scheduling records for the clinic's internal operations.</li>
    </ul>
    <p>
      We do <strong>not</strong> use your personal data for advertising,
      profiling, or any purpose unrelated to scheduling your visit and
      operating the PolyClinic system.
    </p>

    <h2>4. How We Collect Your Data</h2>
    <p>
      We collect your information directly from you, through the answers you
      provide to our scheduling chatbot when booking a visit, filing a
      complaint, or leaving a review. We may also collect limited technical
      data automatically through cookies when you browse our website (see
      Section 8).
    </p>

    <h2>5. How We Use and Protect Your Data</h2>
    <p>
      Your personal data is used strictly for the purposes stated above and
      is only accessed by authorized PolyClinic staff and doctors who need it
      to prepare for and manage your appointment. We apply reasonable
      organizational, physical, and technical safeguards (including secure
      servers and access controls) to protect your data against unauthorized
      access, alteration, disclosure, or destruction.
    </p>

    <h2>6. Doctor and Clinic Information</h2>
    <p>
      Doctor profiles (such as name, specialty, and schedule) shown on our
      website and chatbot are displayed only to help patients choose and book
      an appointment with the right specialist. This information &mdash; and
      any other personal information belonging to PolyClinic's doctors and
      staff &mdash; is likewise protected and is only to be used for
      legitimate purposes directly related to PolyClinic and its scheduling
      system. It may not be used, copied, or repurposed for anything outside
      of that.
    </p>

    <h2>7. Data Sharing and Disclosure</h2>
    <p>
      PolyClinic does not sell, rent, or share your personal data with third
      parties for marketing or any other unrelated purpose. Your information
      is used internally by PolyClinic for scheduling and clinic operations,
      and may only be disclosed to government agencies when required by law,
      court order, or public health reporting requirements.
    </p>

    <h2>8. Retention Period</h2>
    <p>
      We retain your scheduling information for as long as necessary to
      fulfill the purposes stated in this notice, or as required by
      applicable Philippine record-keeping laws and regulations. Once no
      longer needed, your data is securely disposed of or anonymized.
    </p>

    <h2>9. Cookies</h2>
    <p>
      Our website uses cookies to keep you logged in, remember your
      preferences, and understand how our site is used so we can improve it.
      You may disable cookies through your browser settings, though some
      features of the website may not function properly without them.
    </p>

    <h2>10. Your Rights as a Data Subject</h2>
    <p>Under the Data Privacy Act of 2012, you have the right to:</p>
    <ul>
      <li><strong>Be informed</strong> that your personal data will be, is being, or has been processed;</li>
      <li><strong>Access</strong> your personal data that PolyClinic holds about you;</li>
      <li><strong>Correct</strong> any inaccurate or outdated personal data;</li>
      <li><strong>Object</strong> to the processing of your personal data, subject to certain exceptions;</li>
      <li><strong>Erasure or blocking</strong> of your data under certain conditions provided by law;</li>
      <li><strong>Data portability</strong>, or the right to obtain a copy of your data in electronic format;</li>
      <li><strong>File a complaint</strong> with PolyClinic or with the National Privacy Commission (NPC) if you believe your data privacy rights have been violated; and</li>
      <li><strong>Be indemnified</strong> for damages sustained due to inaccurate, incomplete, or unlawfully obtained personal data.</li>
    </ul>
    <p>
      Please note that exercising some of these rights (such as erasure) may
      affect our ability to schedule or manage your appointment, since
      accurate scheduling records are needed for your visit.
    </p>

    <h2>11. Your Responsibilities</h2>
    <ul>
      <li>Provide accurate information when scheduling a visit through our chatbot;</li>
      <li>Keep your contact information updated so we can reach you about your appointment;</li>
      <li>Report any suspected misuse of your information immediately; and</li>
      <li>Respect the privacy of PolyClinic's doctors and staff when using our system.</li>
    </ul>

    <h2>12. Changes to This Notice</h2>
    <p>
      PolyClinic may update this Privacy Notice from time to time to reflect
      changes in our practices or legal requirements. Any updates will be
      posted on this page with a revised "Last updated" date.
    </p>

    <h2>13. Contact Us</h2>
    <p>
      For questions, concerns, or to exercise any of your rights under the
      Data Privacy Act of 2012, you may contact PolyClinic through:
    </p>
    <ul class="privacy-page-contact">
      <li><strong>Clinic Address:</strong> TM Kalaw St., Lipa City, Batangas 4217</li>
      <li><strong>Phone:</strong> 0985 475 5511</li>
      <li><strong>Email:</strong> [insert your official data privacy / DPO email here]</li>
    </ul>
    <p>
      You may also file a complaint with the
      <a href="https://privacy.gov.ph" target="_blank" rel="noopener">National Privacy Commission (NPC)</a>
      if your concern is not resolved.
    </p>

  </div>
</section>

<footer class="footer" style="text-align:center; padding:24px; color:#64748B; font-size:13px;">
  <p>&copy; 2026 PolyClinic Lipa &middot; TM Kalaw St., Lipa City, Batangas 4217</p>
</footer>

@vite(['resources/js/script.js', 'resources/js/navbar-loader.js'])

</body>
</html>