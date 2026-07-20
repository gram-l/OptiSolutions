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

    /* ---------- Hero actions: rating badge + feedback button, same height ---------- */
    .hero-flutter-actions {
      display: flex;
      align-items: stretch;
      gap: 20px;
      flex-wrap: wrap;
    }

    .hero-rating-badge {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      justify-content: center;
      gap: 4px;
      padding: 20px 28px;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      min-width: 190px;
    }

    .hero-rating-num {
      font-size: 30px;
      font-weight: 800;
      color: #1e3a8a;
      line-height: 1;
    }

    .hero-rating-stars {
      display: flex;
      gap: 3px;
      font-size: 18px;
      line-height: 1;
    }
    .hero-rating-stars .star-filled { color: #fbbf24; }
    .hero-rating-stars .star-empty  { color: #d1d5db; }

    .hero-rating-label {
      font-size: 14px;
      color: #6b7280;
    }

    .hero-learn-more-wrap {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
      gap: 6px;
      padding: 20px 32px;
      border: 2px solid #1e3a8a;
      border-radius: 14px;
      min-width: 260px;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s ease, transform 0.15s ease;
    }
    .hero-learn-more-wrap:hover {
      background: #1e3a8a;
      transform: translateY(-2px);
    }
    .hero-learn-more-wrap:hover .hero-learn-more-title,
    .hero-learn-more-wrap:hover .hero-learn-more-sub {
      color: #ffffff;
    }

    .hero-learn-more-title {
      font-size: 20px;
      font-weight: 700;
      color: #1e3a8a;
      transition: color 0.2s ease;
    }

    .hero-learn-more-sub {
      font-size: 14px;
      color: #6b7280;
      transition: color 0.2s ease;
    }

    @media (max-width: 640px) {
      .hero-rating-badge,
      .hero-learn-more-wrap {
        width: 100%;
      }
    }

    /* ---------- Stats section stars (dynamic, colored) ---------- */
    .stat-rating-stars {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      flex-wrap: wrap;
      justify-content: center;
    }
    .stat-rating-stars .star-filled { color: #fbbf24; }
    .stat-rating-stars .star-empty  { color: #d1d5db; }
    .stat-rating-count {
      margin-left: 4px;
      font-size: inherit;
      color: inherit;
    }
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
          <span class="hero-rating-num">{{ number_format($avgRating ?? 4.9, 1) }}</span>
          <span class="hero-rating-stars">
            @php
              $ratingValue = round($avgRating ?? 4.9);
              $ratingValue = max(0, min(5, $ratingValue));
            @endphp
            @for ($i = 1; $i <= 5; $i++)
              <i class="fas fa-star {{ $i <= $ratingValue ? 'star-filled' : 'star-empty' }}"></i>
            @endfor
          </span>
          <span class="hero-rating-label">Patient Rating</span>
        </div>

        <a href="#" onclick="openChatForReview(); return false;" class="hero-learn-more-wrap">
          <span class="hero-learn-more-title">Leave your Feedback here!</span>
          <span class="hero-learn-more-sub">We're glad to hear your experience with us.</span>
        </a>
      </div>
    </div>
    <div class="hero-flutter-image">
  <img src="/images/polyclinic_logo.png" alt="PolyClinic Logo" class="hero-flutter-img hero-logo-blur">
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
        <span class="stat-modern-number">{{ $specialistsCount }}+</span>
        <span class="stat-modern-label">Specialists</span>
        <span class="stat-modern-desc">Board-certified medical experts</span>
      </div>
      <div class="stat-modern-item">
        <span class="stat-modern-number">{{ number_format($avgRating ?? 4.9, 1) }}</span>
        <span class="stat-modern-label">Patient Rating</span>
        <span class="stat-modern-desc stat-rating-stars">
          @php
            $statRatingValue = round($avgRating ?? 4.9);
            $statRatingValue = max(0, min(5, $statRatingValue));
          @endphp
          @for ($i = 1; $i <= 5; $i++)
            <i class="fas fa-star {{ $i <= $statRatingValue ? 'star-filled' : 'star-empty' }}"></i>
          @endfor
          <span class="stat-rating-count">from 800+ reviews</span>
        </span>
      </div>
    </div>
  </div>
</section>

<!-- ========== SERVICES - MODERN ========== -->
<section class="services-modern-section" id="services">
  <div class="container">
    <div class="services-modern-header">
      <h2 class="services-modern-title">Comprehensive Medical Care<br>For You & Your Family</h2>
      <p class="services-modern-subtitle">We offer a wide range of specialized healthcare services delivered with compassion and expertise.</p>
    </div>
    <div class="service-grid-modern">
      <div class="service-card-modern" onclick="openServiceModal('pediatrics')">
        <div class="service-card-icon"><i class="fas fa-child"></i></div>
        <h3>Pediatrics</h3>
        <p>Comprehensive care for children from newborn to adolescent.</p>
        <span class="service-card-link"></span>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('obgyne')">
        <div class="service-card-icon"><i class="fas fa-female"></i></div>
        <h3>OB-Gyne</h3>
        <p>Women's health, prenatal care, and reproductive health services.</p>
        <span class="service-card-link"></span>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('surgery')">
        <div class="service-card-icon"><i class="fas fa-scalpel"></i></div>
        <h3>Surgery</h3>
        <p>General surgical procedures and consultations.</p>
        <span class="service-card-link"></span>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('pulmonology')">
        <div class="service-card-icon"><i class="fas fa-lungs"></i></div>
        <h3>IM-Pulmonology</h3>
        <p>Respiratory and pulmonary care for lung health.</p>
        <span class="service-card-link"></span>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('ophthalmology')">
        <div class="service-card-icon"><i class="fas fa-eye"></i></div>
        <h3>Ophthalmology / ENT</h3>
        <p>Eye care, ear, nose, and throat consultations.</p>
        <span class="service-card-link"></span>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('cardiology')">
        <div class="service-card-icon"><i class="fas fa-heartbeat"></i></div>
        <h3>IM-Cardiology</h3>
        <p>Heart health and cardiovascular care.</p>
        <span class="service-card-link"></span>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('adultmedicine')">
        <div class="service-card-icon"><i class="fas fa-user-md"></i></div>
        <h3>General / Adult Medicine</h3>
        <p>Comprehensive medical care for adults.</p>
        <span class="service-card-link"></span>
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
        <p class="cta-banner-desc">We're conveniently located at {{ $clinicInfo->address ?? 'TM Kalaw St., Lipa City' }}. Walk-ins are welcome!</p>
        <div class="cta-banner-info">
          <div class="cta-info-item">
            <i class="fas fa-phone-alt"></i>
            <div>
              <span class="cta-info-label">Call Us</span>
              <span class="cta-info-value">{{ $clinicInfo->contact_no ?? '0985 475 5511' }}</span>
            </div>
          </div>
          <div class="cta-info-item">
            <i class="fas fa-clock"></i>
            <div>
              <span class="cta-info-label">Clinic Hours</span>
              <span class="cta-info-value">{{ $clinicInfo->operating_hours ?? 'Mon-Fri 8AM-6PM · Sat 9AM-1PM' }}</span>
            </div>
          </div>
        </div>
        <a href="{{ route('contact') }}" class="btn-cta-banner">Get Directions <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="cta-banner-map">
        <iframe src="https://www.google.com/maps?q=PolyClinic+Lipa,+41+TM+Kalaw+St,+Lipa+City,+4217+Batangas&output=embed" allowfullscreen loading="lazy"></iframe>
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

@include('patient.partials.chatbot-widget')

@vite(['resources/js/script.js', 'resources/js/navbar-loader.js'])

<script>
  function openChatForReview() {
    if (window.openChatbotAndSubmitFeedback) {
      window.openChatbotAndSubmitFeedback();
    } else {
      document.getElementById('chatbotTrigger')?.click();
    }
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