<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PolyClinic | About Us</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  @vite(['resources/css/patient_css/about.css', 'resources/css/patient_css/chatbot.css'])
</head>
<body>

<!-- ========== ABOUT PAGE ========== -->
<section class="section about-section" id="about">
  <div class="container">
    <div class="about-header">
      <span class="about-tag">About PolyClinic</span>
      <h2 class="about-title">Compassionate Care for Every Filipino Family</h2>
      <p class="about-subtitle">We are a multi-specialty clinic dedicated to providing accessible, high-quality healthcare to the Lipa community and beyond.</p>
    </div>

    <div class="about-grid-modern">
      <div class="about-text">
        <p><strong>PolyClinic Lipa</strong> is a multi-specialty clinic located at <strong>TM Kalaw St., Lipa City, Batangas, 4217</strong>. We are committed to providing comprehensive and compassionate healthcare to the Lipa community and surrounding areas.</p>
        <p>We offer a wide range of services including <strong>Pediatrics, Adult Medicine, Ophthalmology, OB-GYN, and Surgery</strong> consults. Our team of experienced specialists ensures that every patient receives personalized attention and the highest quality of care.</p>

        <div class="about-stats-modern">
          <div class="stat-item">
            <span class="stat-number">15+</span>
            <span class="stat-label">Years of Excellence</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">50k+</span>
            <span class="stat-label">Happy Patients</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">12+</span>
            <span class="stat-label">Specialists</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">4.9</span>
            <span class="stat-label">Patient Rating</span>
          </div>
        </div>

        <a href="{{ route('contact') }}" class="about-cta-btn">Visit Our Clinic <i class="fas fa-arrow-right"></i></a>
      </div>

      <div class="about-image-modern">
        <div class="image-placeholder">
          <i class="fas fa-hospital-user"></i>
          <div class="image-badge">Accredited by DOH</div>
        </div>
      </div>
    </div>

    <div class="mv-modern">
      <div class="mv-card-modern">
        <div class="mv-number">01</div>
        <div class="mv-icon-modern"><i class="fas fa-bullseye"></i></div>
        <div class="mv-content-modern">
          <h3>Our Mission</h3>
          <p>To provide accessible, compassionate, and high-quality healthcare services to every Filipino family, ensuring holistic wellness and patient satisfaction.</p>
        </div>
      </div>

      <div class="mv-card-modern">
        <div class="mv-number">02</div>
        <div class="mv-icon-modern"><i class="fas fa-eye"></i></div>
        <div class="mv-content-modern">
          <h3>Our Vision</h3>
          <p>To be the leading multi-specialty clinic in Lipa City and Batangas, setting the standard for excellence in patient care and community health.</p>
        </div>
      </div>

      <div class="mv-card-modern values-card">
        <div class="mv-number">03</div>
        <div class="mv-icon-modern"><i class="fas fa-hand-holding-heart"></i></div>
        <div class="mv-content-modern">
          <h3>Our Core Values</h3>
          <div class="values-grid">
            <div class="value-item">
              <span class="value-icon">❤️</span>
              <div><strong>Compassion</strong><p>We treat every patient with empathy and respect.</p></div>
            </div>
            <div class="value-item">
              <span class="value-icon">⭐</span>
              <div><strong>Excellence</strong><p>We strive for the highest standard of medical care.</p></div>
            </div>
            <div class="value-item">
              <span class="value-icon">🤝</span>
              <div><strong>Integrity</strong><p>We uphold honesty and transparency in all we do.</p></div>
            </div>
            <div class="value-item">
              <span class="value-icon">👥</span>
              <div><strong>Teamwork</strong><p>We work together to serve our community better.</p></div>
            </div>
            <div class="value-item">
              <span class="value-icon">💡</span>
              <div><strong>Innovation</strong><p>We embrace technology to improve patient outcomes.</p></div>
            </div>
          </div>
        </div>
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

</body>
</html>