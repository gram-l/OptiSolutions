<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PolyClinic Lipa | Contact</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  @vite(['resources/css/patient_css/contact.css', 'resources/css/patient_css/chatbot.css'])

  <style>
    .reviews-banner-footer { cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
    .reviews-banner-footer:hover { transform: scale(1.02); box-shadow: 0 12px 32px rgba(0,0,0,0.2); }
    .reviews-banner-footer:active { transform: scale(0.98); }
  </style>
</head>
<body>


<!-- ========== CONTACT PAGE ========== -->
<section class="section" id="contact">
  <div class="container">
    <h2 class="section-title">Visit Our Clinic</h2>
    <div class="contact-wrapper">
      <div class="contact-info-grid">
        <div class="contact-info-card">
          <div class="contact-info-icon"><i class="fas fa-map-marker-alt"></i></div>
          <div><h4>Address</h4><p>TM Kalaw St., Lipa City, Batangas, 4217</p></div>
        </div>
        <div class="contact-info-card">
          <div class="contact-info-icon"><i class="fas fa-phone-alt"></i></div>
          <div><h4>Phone</h4><p>0985 475 5511 · (043) 123-4567</p></div>
        </div>
        <div class="contact-info-card">
          <div class="contact-info-icon"><i class="fas fa-envelope"></i></div>
          <div><h4>Email</h4><p>info@polycliniclipa.ph</p></div>
        </div>
        <div class="contact-info-card">
          <div class="contact-info-icon"><i class="fas fa-clock"></i></div>
          <div><h4>Hours</h4><p>Mon-Fri: 8AM-6PM · Sat: 9AM-1PM</p></div>
        </div>
      </div>
      <div class="map-container">
        <iframe src="https://www.google.com/maps?q=PolyClinic+Lipa,+41+TM+Kalaw+St,+Lipa+City,+4217+Batangas&output=embed" allowfullscreen loading="lazy"></iframe>
      </div>
    </div>
  </div>
</section>

<!-- ========== FOOTER ========== -->
<footer class="footer"><p>© 2026 PolyClinic Lipa · TM Kalaw St., Lipa City, Batangas 4217</p></footer>

<script>
    var botmanWidget = {
        aboutText: 'PolyClinic Assistant',
        introMessage: "✋ Hi! I'm your PolyClinic Assistant."
    };
</script>
<script src='https://cdn.jsdelivr.net/npm/botman-web-widget@0/build/js/widget.js'></script>

@vite(['resources/js/script.js', 'resources/js/navbar-loader.js'])

<script>
  function openChatForReview() {
    document.querySelector('.desktop-closed-message-avatar')?.click();
  }
</script>
</body>
</html>