<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PolyClinic | Home</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  
  @vite(['resources/css/patient_css/home.css', 'resources/css/patient_css/chatbot.css'])

  <style>
    .reviews-banner-footer { cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
    .reviews-banner-footer:hover { transform: scale(1.02); box-shadow: 0 12px 32px rgba(0,0,0,0.2); }
    .reviews-banner-footer:active { transform: scale(0.98); }
  </style>
</head>
<body>

<!-- ========== HERO - FLUTTER STYLE ========== -->
<section class="hero-flutter" id="home">
  <div class="hero-flutter-inner">
    <div class="hero-flutter-content">
      <h1 class="hero-flutter-title">Care You Can<br>Count On</h1>
      <p class="hero-flutter-desc">Experience compassionate, accessible healthcare with our team of board-certified specialists. We're here for every Filipino family.</p>
      <div class="hero-flutter-actions">
        <div class="hero-rating-badge">
          <span class="hero-rating-num">4.9</span>
          <span class="hero-rating-stars">★★★★★</span>
          <span class="hero-rating-label">Patient Rating</span>
        </div>
        <a href="{{ route('about') }}" class="hero-learn-more">Learn More →</a>
      </div>
    </div>
    <div class="hero-flutter-image">
      <img src="{{ str_starts_with($heroImage, 'hero/') ? asset('storage/' . $heroImage) : asset($heroImage) }}" alt="PolyClinic Doctors" class="hero-flutter-img">
    </div>
  </div>
</section>

<!-- ========== STATS - MODERN ========== -->
<section class="stats-modern-section">
  <div class="container">
    <div class="stats-modern-grid">
      <div class="stat-modern-item">
        <span class="stat-modern-number">15+</span>
        <span class="stat-modern-label">Years of Excellence</span>
        <span class="stat-modern-desc">Serving the community since 2011</span>
      </div>
      <div class="stat-modern-item">
        <span class="stat-modern-number">50k+</span>
        <span class="stat-modern-label">Happy Patients</span>
        <span class="stat-modern-desc">Trusted by families across Batangas</span>
      </div>
      <div class="stat-modern-item">
        <span class="stat-modern-number">12+</span>
        <span class="stat-modern-label">Specialists</span>
        <span class="stat-modern-desc">Board-certified medical experts</span>
      </div>
      <div class="stat-modern-item">
        <span class="stat-modern-number">4.9</span>
        <span class="stat-modern-label">Patient Rating</span>
        <span class="stat-modern-desc">★★★★★ from 800+ reviews</span>
      </div>
    </div>
  </div>
</section>

<!-- ========== SERVICES - MODERN ========== -->
<section class="services-modern-section" id="services">
  <div class="container">
    <div class="services-modern-header">
      <span class="services-modern-tag">Our Services</span>
      <h2 class="services-modern-title">Comprehensive Medical Care<br>For You & Your Family</h2>
      <p class="services-modern-subtitle">We offer a wide range of specialized healthcare services delivered with compassion and expertise.</p>
    </div>
    <div class="service-grid-modern">
      <div class="service-card-modern" onclick="openServiceModal('pediatrics')">
        <div class="service-card-icon"><i class="fas fa-child"></i></div>
        <h3>Pediatrics</h3>
        <p>Comprehensive care for children from newborn to adolescent.</p>
        <span class="service-card-link">Learn More →</span>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('obgyne')">
        <div class="service-card-icon"><i class="fas fa-female"></i></div>
        <h3>OB-Gyne</h3>
        <p>Women's health, prenatal care, and reproductive health services.</p>
        <span class="service-card-link">Learn More →</span>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('surgery')">
        <div class="service-card-icon"><i class="fas fa-scalpel"></i></div>
        <h3>Surgery</h3>
        <p>General surgical procedures and consultations.</p>
        <span class="service-card-link">Learn More →</span>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('pulmonology')">
        <div class="service-card-icon"><i class="fas fa-lungs"></i></div>
        <h3>IM-Pulmonology</h3>
        <p>Respiratory and pulmonary care for lung health.</p>
        <span class="service-card-link">Learn More →</span>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('ophthalmology')">
        <div class="service-card-icon"><i class="fas fa-eye"></i></div>
        <h3>Ophthalmology / ENT</h3>
        <p>Eye care, ear, nose, and throat consultations.</p>
        <span class="service-card-link">Learn More →</span>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('cardiology')">
        <div class="service-card-icon"><i class="fas fa-heartbeat"></i></div>
        <h3>IM-Cardiology</h3>
        <p>Heart health and cardiovascular care.</p>
        <span class="service-card-link">Learn More →</span>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('adultmedicine')">
        <div class="service-card-icon"><i class="fas fa-user-md"></i></div>
        <h3>General / Adult Medicine</h3>
        <p>Comprehensive medical care for adults.</p>
        <span class="service-card-link">Learn More →</span>
      </div>
    </div>
    <div class="services-modern-footer">
      <a href="{{ route('services') }}" class="btn-modern-primary">View All Services <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- ========== DOCTORS - MODERN ========== -->
<section class="doctors-modern-section" id="doctors">
  <div class="container">
    <div class="doctors-modern-header">
      <span class="doctors-modern-tag">Our Team</span>
      <h2 class="doctors-modern-title">Meet Our Medical Experts</h2>
      <p class="doctors-modern-subtitle">Our board-certified physicians bring years of specialized experience and compassionate care to help you achieve optimal health.</p>
    </div>

    <div class="doctors-search-wrap">
      <div class="doctors-search-box">
        <i class="fas fa-search"></i>
        <input
          type="text"
          id="doctorSearchInput"
          placeholder="Search doctors, specialties, or services…"
          oninput="filterDoctorsLive(this.value)"
          onkeydown="if(event.key==='Enter') filterDoctorsLive(this.value)"
          autocomplete="off"
        />
        <button onclick="filterDoctorsLive(document.getElementById('doctorSearchInput').value)">
          Search
        </button>
      </div>
      <div id="searchNoResults" class="search-no-results" style="display:none;">
        <i class="fas fa-user-md"></i>
        <p>No doctors or services found for "<span id="searchQuery"></span>"</p>
      </div>
    </div>

    <div class="filter-tabs" id="filterTabsHome">
      <button class="filter-tab active" data-filter="all">All</button>
      <button class="filter-tab" data-filter="Pediatrics">Pediatrics</button>
      <button class="filter-tab" data-filter="OB-Gyne">OB-Gyne</button>
      <button class="filter-tab" data-filter="Surgery">Surgery</button>
      <button class="filter-tab" data-filter="IM-Pulmonology">Pulmonology</button>
      <button class="filter-tab" data-filter="Ophthalmology">Ophthalmology</button>
      <button class="filter-tab" data-filter="IM-Cardiology">Cardiology</button>
      <button class="filter-tab" data-filter="General / Adult Medicine">Adult Medicine</button>
      <button class="filter-tab" data-filter="Internal Medicine">Internal Medicine</button>
      <button class="filter-tab" data-filter="Medical Oncology">Medical Oncology</button>
    </div>

    <div class="doctors-grid-modern" id="doctorsGridHome"></div>
    <div class="doctors-modern-footer">
      <a href="{{ route('doctors') }}" class="btn-modern-outline">View All Doctors <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- ========== CTA BANNER - MODERN ========== -->
<section class="cta-banner">
  <div class="container">
    <div class="cta-banner-inner">
      <div class="cta-banner-content">
        <span class="cta-banner-tag">📍 Lipa City, Batangas</span>
        <h2 class="cta-banner-title">Visit Our Clinic Today</h2>
        <p class="cta-banner-desc">We're conveniently located at TM Kalaw St., Lipa City. Walk-ins are welcome!</p>
        <div class="cta-banner-info">
          <div class="cta-info-item">
            <i class="fas fa-phone-alt"></i>
            <div>
              <span class="cta-info-label">Call Us</span>
              <span class="cta-info-value">0985 475 5511</span>
            </div>
          </div>
          <div class="cta-info-item">
            <i class="fas fa-clock"></i>
            <div>
              <span class="cta-info-label">Clinic Hours</span>
              <span class="cta-info-value">Mon-Fri 8AM-6PM · Sat 9AM-1PM</span>
            </div>
          </div>
        </div>
        <a href="{{ route('contact') }}" class="btn-cta-banner">Get Directions <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="cta-banner-map">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2476.678207385253!2d121.156253!3d13.941975!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd5e6e2c64b2c9%3A0x8c7e5e6e2c64b2c9!2sLipa%20City%2C%20Batangas!5e0!3m2!1sen!2sph!4v1700000000000!5m2!1sen!2sph" allowfullscreen loading="lazy"></iframe>
      </div>
    </div>
  </div>
</section>

<!-- ========== REVIEWS BANNER ========== -->
<div class="container">
  <div class="reviews-banner-footer" onclick="openChatForReview()">
    <div class="stars">★★★★★</div>
    <div class="count">809,000+ Reviews</div>
    <div class="text">⭐ Click here to leave your feedback! ⭐</div>
  </div>
</div>

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

<script>
  function openChatForReview() {
    const panel = document.getElementById('chatbot');
    if (!panel.classList.contains('open')) { toggleChat(); }
    setTimeout(function() {
      if (typeof startReviewRating === 'function') { startReviewRating(); }
    }, 400);
  }

  function filterDoctorsLive(query) {
    const q = query.trim().toLowerCase();
    const cards = document.querySelectorAll('#doctorsGridHome .doctor-card');
    const noResults = document.getElementById('searchNoResults');
    const querySpan = document.getElementById('searchQuery');
    let visibleCount = 0;

    cards.forEach(card => {
      const text = card.innerText.toLowerCase();
      if (!q || text.includes(q)) {
        card.style.display = '';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    if (q && visibleCount === 0) {
      noResults.style.display = 'flex';
      querySpan.textContent = query;
    } else {
      noResults.style.display = 'none';
    }

    if (q) {
      document.querySelectorAll('#filterTabsHome .filter-tab').forEach(t => t.classList.remove('active'));
      document.querySelector('#filterTabsHome .filter-tab[data-filter="all"]').classList.add('active');
    }
  }
</script>
</body>
</html>