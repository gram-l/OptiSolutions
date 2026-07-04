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
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2476.678207385253!2d121.156253!3d13.941975!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd5e6e2c64b2c9%3A0x8c7e5e6e2c64b2c9!2sLipa%20City%2C%20Batangas!5e0!3m2!1sen!2sph!4v1700000000000!5m2!1sen!2sph" allowfullscreen loading="lazy"></iframe>
      </div>
    </div>
  </div>
</section>

<!-- ========== FOOTER ========== -->
<footer class="footer"><p>© 2026 PolyClinic Lipa · TM Kalaw St., Lipa City, Batangas 4217</p></footer>

<!-- ========== CHATBOT ========== -->
<button class="chatbot-trigger" onclick="toggleChat()"><i class="fas fa-comment-dots"></i></button>
<div class="chatbot-panel" id="chatbot">
  <div class="chat-header">
    <div class="chat-header-left">
      <div class="chat-avatar"><i class="fas fa-headset"></i></div>
      <div class="chat-header-info"><strong>PolyClinic Assistant</strong><span>● Online</span></div>
    </div>
    <button onclick="toggleChat()" style="background:none; border:none; color:var(--gray-500); font-size:22px; cursor:pointer;">✕</button>
  </div>
  <div class="chat-messages" id="chatMessages"></div>
  <div class="chat-input-area">
    <button class="attach-btn" onclick="document.getElementById('fileInput').click()"><i class="fas fa-paperclip"></i></button>
    <input type="text" class="chat-input" id="chatInput" placeholder="Type a message...">
    <button class="chat-send" onclick="sendChatMessage()"><i class="fas fa-paper-plane"></i></button>
    <input type="file" id="fileInput" class="file-input" accept="image/*" onchange="handleImageUpload(event)">
  </div>
</div>

@vite(['resources/js/script.js', 'resources/js/navbar-loader.js'])

<script>
  function openChatForReview() {
    const panel = document.getElementById('chatbot');
    if (!panel.classList.contains('open')) { toggleChat(); }
    setTimeout(function() {
      if (typeof startReviewRating === 'function') { startReviewRating(); }
    }, 400);
  }
</script>
</body>
</html>