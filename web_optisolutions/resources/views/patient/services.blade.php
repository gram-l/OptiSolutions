<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PolyClinic Lipa | Services</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  @vite(['resources/css/patient_css/services.css', 'resources/css/patient_css/chatbot.css'])
</head>
<body>


<!-- ========== SERVICES PAGE - MODERN ========== -->
<section class="services-page-section" id="services">
  <div class="container">
    <div class="services-page-header">
      <span class="services-page-tag">✦ Our Services</span>
      <h1 class="services-page-title">Comprehensive Medical Care<br>For You & Your Family</h1>
      <p class="services-page-subtitle">We offer a wide range of specialized healthcare services delivered with compassion and expertise. Click on any service to learn more.</p>
    </div>
    <div class="services-page-grid">
      <div class="service-page-card" onclick="openServiceModal('pediatrics')">
        <div class="service-page-icon"><i class="fas fa-child"></i></div>
        <h3>Pediatrics</h3>
        <p>Comprehensive care for children from newborn to adolescent.</p>
        <span class="service-page-link">Learn More →</span>
      </div>
      <div class="service-page-card" onclick="openServiceModal('obgyne')">
        <div class="service-page-icon"><i class="fas fa-female"></i></div>
        <h3>OB-Gyne</h3>
        <p>Women's health, prenatal care, and reproductive health services.</p>
        <span class="service-page-link">Learn More →</span>
      </div>
      <div class="service-page-card" onclick="openServiceModal('surgery')">
        <div class="service-page-icon"><i class="fas fa-scalpel"></i></div>
        <h3>Surgery</h3>
        <p>General surgical procedures and consultations.</p>
        <span class="service-page-link">Learn More →</span>
      </div>
      <div class="service-page-card" onclick="openServiceModal('pulmonology')">
        <div class="service-page-icon"><i class="fas fa-lungs"></i></div>
        <h3>IM-Pulmonology</h3>
        <p>Respiratory and pulmonary care for lung health.</p>
        <span class="service-page-link">Learn More →</span>
      </div>
      <div class="service-page-card" onclick="openServiceModal('ophthalmology')">
        <div class="service-page-icon"><i class="fas fa-eye"></i></div>
        <h3>Ophthalmology / ENT</h3>
        <p>Eye care, ear, nose, and throat consultations.</p>
        <span class="service-page-link">Learn More →</span>
      </div>
      <div class="service-page-card" onclick="openServiceModal('cardiology')">
        <div class="service-page-icon"><i class="fas fa-heartbeat"></i></div>
        <h3>IM-Cardiology</h3>
        <p>Heart health and cardiovascular care.</p>
        <span class="service-page-link">Learn More →</span>
      </div>
      <div class="service-page-card" onclick="openServiceModal('adultmedicine')">
        <div class="service-page-icon"><i class="fas fa-user-md"></i></div>
        <h3>General / Adult Medicine</h3>
        <p>Comprehensive medical care for adults.</p>
        <span class="service-page-link">Learn More →</span>
      </div>
    </div>
  </div>
</section>

<!-- ========== FOOTER ========== -->
<footer class="footer"><p>© 2026 PolyClinic Lipa · TM Kalaw St., Lipa City, Batangas 4217</p></footer>

<!-- ========== SERVICE MODAL ========== -->
<div id="serviceModal" class="service-modal">
  <div class="service-modal-content">
    <button class="service-modal-close" onclick="closeServiceModal()"><i class="fas fa-times"></i></button>
    <div id="serviceModalBody"></div>
  </div>
</div>

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

</body>
</html>