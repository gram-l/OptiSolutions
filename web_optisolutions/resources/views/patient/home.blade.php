<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PolyClinic | Home</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  @vite(['resources/css/patient_css/home.css', 'resources/css/patient_css/about.css', 'resources/css/patient_css/contact.css', 'resources/js/script.js', 'resources/js/navbar-loader.js', 'resources/js/privacy-notice.js'])

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
        <a href="#" onclick="openChatForReview(); return false;" class="hero-learn-more-wrap">
          <span class="hero-learn-more-title">Leave your Feedback here!</span>
          <span class="hero-learn-more-sub">We're glad to hear your experience with us.</span>
        </a>
      </div>
    </div>
    <div class="hero-flutter-image">
      <img src="/images/polyclinic_logo.png" alt="PolyClinic Logo" class="hero-flutter-img hero-logo-blur" id="heroLogoImg">
      <!-- The stats card flies in here and stacks vertically beside the logo -->
      <div class="hero-stats-dock" id="heroStatsDock" aria-live="polite"></div>
    </div>
  </div>
</section>

<!-- ========== STATS - MODERN ========== -->
<!-- On load this renders in its normal place under the hero (as before). A few seconds
     later, script.js below flies this exact grid up beside the hero logo and restacks it
     vertically, then this section collapses so it doesn't leave behind empty space. -->
<section class="stats-modern-section" id="statsSection">
  <div class="container">
    <div class="stats-modern-grid" id="statsGrid">
      <div class="stat-modern-item">
        <span class="stat-modern-number">{{ $yearsOfExcellence ?? 15 }}+</span>
        <span class="stat-modern-label">Years of Excellence</span>
        <span class="stat-modern-desc">Serving the community since {{ $clinicInfo->founded_year ?? 2011 }}</span>
      </div>
      <div class="stat-modern-item">
        <span class="stat-modern-number">{{ number_format($happyPatientsCount ?? 0) }}+</span>
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
        </span>
      </div>
    </div>
  </div>
</section>

<!-- ========== ABOUT - MODERN ========== -->
<section class="section about-section" id="about">
  <div class="container">
    <div class="about-header">
      <span class="about-tag">About PolyClinic</span>
      <h2 class="about-title">Compassionate Care for Every Filipino Family</h2>
      <p class="about-subtitle">We are a multi-specialty clinic dedicated to providing accessible, high-quality healthcare to the Lipa community and beyond.</p>
    </div>

    <div class="about-grid-modern">
      <div class="about-text">
        <p><strong>{{ $clinicInfo->clinic_name ?? 'PolyClinic' }}</strong> is a multi-specialty clinic located at <strong>{{ $clinicInfo->address ?? 'TM Kalaw St., Lipa City' }}</strong>. We are committed to providing comprehensive and compassionate healthcare to the Lipa community and surrounding areas.</p>
        <p>{{ $clinicInfo->about_us ?? '' }}</p>

        <div class="trust-badges">
          <div class="trust-badge">
            <i class="fas fa-shield-halved"></i>
            <span>DOH Accredited</span>
          </div>
          <div class="trust-badge">
            <i class="fas fa-user-doctor"></i>
            <span>Licensed Specialists</span>
          </div>
          <div class="trust-badge">
            <i class="fas fa-lock"></i>
            <span>Data Privacy Compliant</span>
          </div>
        </div>
      </div>

      <div class="about-image-modern">
        <div class="image-placeholder">
          <i class="fas fa-hospital-user"></i>
          <div class="image-badge">Accredited by DOH</div>
        </div>
      </div>
    </div>

    <div class="mv-modern">
      <div class="mv-top-grid">
        <div class="mv-card-modern">
          <div class="mv-icon-modern"><i class="fas fa-bullseye"></i></div>
          <div class="mv-content-modern">
            <h3>Our Mission</h3>
            <p>{{ $clinicInfo->mission ?? '' }}</p>
          </div>
        </div>

        <div class="mv-card-modern">
          <div class="mv-icon-modern"><i class="fas fa-eye"></i></div>
          <div class="mv-content-modern">
            <h3>Our Vision</h3>
            <p>{{ $clinicInfo->vision ?? '' }}</p>
          </div>
        </div>
      </div>

      <div class="mv-card-modern values-card">
        <div class="mv-icon-modern"><i class="fas fa-hand-holding-heart"></i></div>
        <div class="mv-content-modern">
          <h3>Our Core Values</h3>
          <div class="values-grid">
            @forelse (($clinicInfo->core_values ?? []) as $value)
              <div class="value-item">
                <strong>{{ $value }}</strong>
              </div>
            @empty
              <p class="values-empty">Core values have not been set yet.</p>
            @endforelse
          </div>
        </div>
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
      </div>
      <div class="service-card-modern" onclick="openServiceModal('obgyne')">
        <div class="service-card-icon"><i class="fas fa-person-dress"></i></div>
        <h3>OB-Gyne</h3>
        <p>Women's health, prenatal care, and reproductive health services.</p>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('surgery')">
        <div class="service-card-icon"><i class="fas fa-kit-medical"></i></div>
        <h3>Surgery</h3>
        <p>General surgical procedures and consultations.</p>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('pulmonology')">
        <div class="service-card-icon"><i class="fas fa-lungs"></i></div>
        <h3>IM-Pulmonology</h3>
        <p>Respiratory and pulmonary care for lung health.</p>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('ophthalmology')">
        <div class="service-card-icon"><i class="fas fa-eye"></i></div>
        <h3>Ophthalmology / ENT</h3>
        <p>Eye care, ear, nose, and throat consultations.</p>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('cardiology')">
        <div class="service-card-icon"><i class="fas fa-heartbeat"></i></div>
        <h3>IM-Cardiology</h3>
        <p>Heart health and cardiovascular care.</p>
      </div>
      <div class="service-card-modern" onclick="openServiceModal('adultmedicine')">
        <div class="service-card-icon"><i class="fas fa-user-doctor"></i></div>
        <h3>General / Adult Medicine</h3>
        <p>Comprehensive medical care for adults.</p>
      </div>
    </div>
  </div>
</section>

<!-- ========== DOCTORS - MODERN ========== -->
<section class="doctors-modern-section" id="doctors">
  <div class="container">
    <div class="doctors-modern-header">
      <span class="doctors-modern-tag">Medical Team</span>
      <h2 class="doctors-modern-title">Meet Our Medical Experts</h2>
      <p class="doctors-modern-subtitle">Our board-certified physicians bring years of specialized experience and compassionate care to help you achieve optimal health.</p>
    </div>

    <div class="doctor-search-wrap">
      <i class="fas fa-search doctor-search-icon"></i>
      <input type="text" id="doctorsSearchHome" class="doctor-search-input" placeholder="Search doctors by name or specialty...">
    </div>

    <div class="filter-tabs" id="filterTabsHome">
      <button class="filter-tab active" data-filter="all">All</button>
      <button class="filter-tab" data-filter="Pediatrics">Pediatrics</button>
      <button class="filter-tab" data-filter="OB-Gyne">OB-Gyne</button>
      <button class="filter-tab" data-filter="Surgery">Surgery</button>
      <button class="filter-tab" data-filter="IM-Pulmonology">Pulmonology</button>
      <button class="filter-tab" data-filter="General / Adult Medicine">Adult Medicine</button>
      <button class="filter-tab" data-filter="Internal Medicine">Internal Medicine</button>
      <button class="filter-tab" data-filter="Medical Oncology">Medical Oncology</button>
    </div>

    <div class="doctors-grid-modern" id="doctorsGridHome"></div>
  </div>
</section>

<!-- ========== LIVE FEEDBACK & REVIEWS ========== -->
<section class="reviews-live-section" id="reviews">
  <div class="container">
    <div class="reviews-live-header">
      <div class="reviews-live-heading">
        <span class="reviews-live-tag">Patient Voices</span>
        <h2 class="reviews-live-title">Live Feedback &amp; Reviews</h2>
        <p class="reviews-live-subtitle">Real-time experiences shared by our patients. We value every feedback to continually elevate our healthcare quality.</p>
      </div>
      <div class="reviews-live-actions">
        <div class="reviews-live-score">
          <div class="reviews-live-score-top">
            <span class="reviews-live-score-value">{{ number_format($avgRating ?? 4.9, 1) }} / 5.0</span>
            <span class="reviews-live-score-stars">
              @php
                $liveRatingValue = round($avgRating ?? 4.9);
                $liveRatingValue = max(0, min(5, $liveRatingValue));
              @endphp
              @for ($i = 1; $i <= 5; $i++)
                <i class="fas fa-star {{ $i <= $liveRatingValue ? 'star-filled' : 'star-empty' }}"></i>
              @endfor
            </span>
          </div>
          <span class="reviews-live-score-label">Average Rating</span>
        </div>
        <a href="#" onclick="openChatForReview(); return false;" class="btn-submit-feedback">
          <i class="fas fa-pen"></i> Submit Feedback
        </a>
      </div>
    </div>

    <div class="reviews-live-divider"></div>

    <div class="reviews-live-grid">
      @forelse ($recentFeedback ?? [] as $review)
        @php
          $reviewerName = trim(($review->patient_fname ?? '') . ' ' . ($review->patient_lname ?? ''));
          $reviewerName = $reviewerName !== '' ? $reviewerName : 'Anonymous Patient';
          $reviewerInitial = strtoupper(substr($reviewerName, 0, 1));
          $reviewStars = max(0, min(5, (int) $review->star_rating));
        @endphp
        <div class="review-live-card">
          <div class="review-live-top">
            <div class="review-live-who">
              <span class="review-live-avatar">{{ $reviewerInitial }}</span>
              <div>
                <span class="review-live-name">{{ $reviewerName }}</span>
                <span class="review-live-sub">Verified Patient</span>
              </div>
            </div>
            <span class="review-live-time">{{ \Carbon\Carbon::parse($review->submitted_at)->diffForHumans() }}</span>
          </div>
          <p class="review-live-text">&quot;{{ $review->feedback_text }}&quot;</p>
          <div class="review-live-bottom">
            <span class="review-live-stars">
              @for ($i = 1; $i <= 5; $i++)
                <i class="fas fa-star {{ $i <= $reviewStars ? 'star-filled' : 'star-empty' }}"></i>
              @endfor
            </span>
            <span class="review-live-verified"><i class="fas fa-circle-check"></i> Verified Patient</span>
          </div>
        </div>
      @empty
        <p class="reviews-live-empty">No positive reviews yet — be the first to share your experience!</p>
      @endforelse
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
        <a href="#contact" class="btn-cta-banner">Get Directions <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="cta-banner-map">
        <iframe src="https://www.google.com/maps?q=PolyClinic+Lipa,+41+TM+Kalaw+St,+Lipa+City,+4217+Batangas&output=embed" allowfullscreen loading="lazy"></iframe>
      </div>
    </div>
  </div>
</section>

<!-- ========== SERVICE MODAL ========== -->
<div id="serviceModal" class="service-modal">
  <div class="service-modal-content">
    <button class="service-modal-close" onclick="closeServiceModal()"><i class="fas fa-times"></i></button>
    <div id="serviceModalBody"></div>
  </div>
</div>

@include('patient.partials.footer')

@include('patient.partials.chatbot-widget')

@vite(['resources/js/script.js', 'resources/js/navbar-loader.js'])

<script>
  /* ==========================================================
     STATS -> HERO DOCKING ANIMATION (FLIP technique)
     1) Stats grid renders normally under the hero on load.
     2) After a short delay, we record its current position,
        physically move it beside the hero logo, restyle it to
        a vertical stack, then animate FROM the old position
        TO the new one so it visibly "flies" into place.
     3) The now-empty stats section collapses smoothly so it
        doesn't leave a gap behind.
     ========================================================== */
  document.addEventListener('DOMContentLoaded', function () {
    const statsGrid = document.getElementById('statsGrid');
    const statsSection = document.getElementById('statsSection');
    const dock = document.getElementById('heroStatsDock');

    if (!statsGrid || !statsSection || !dock) return;

    // Respect users who prefer reduced motion: just dock instantly, no flight.
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function dockStats() {
      // The dock's space beside the logo has been reserved since page load
      // (see CSS: .hero-stats-dock has a fixed width, just hidden via
      // opacity/visibility). We only reveal it here — its size never
      // changes, so there's nothing for the flight animation to race against.
      const firstRect = statsGrid.getBoundingClientRect();

      dock.classList.add('active');
      dock.appendChild(statsGrid);
      statsGrid.classList.add('docked');

      // Force layout to flush immediately so the very next measurement
      // reflects each item's real, final 2x2 position — not a stale one.
      void dock.offsetHeight;

      if (prefersReducedMotion) {
        collapseOriginalSection();
        return;
      }

      const lastRect = dock.getBoundingClientRect();
      const dx = firstRect.left - lastRect.left;
      const dy = firstRect.top - lastRect.top;
      const scaleX = firstRect.width / lastRect.width;
      const scaleY = firstRect.height / lastRect.height;

      dock.style.willChange = 'transform, opacity';
      dock.style.transformOrigin = 'top left';
      dock.style.transition = 'none';
      dock.style.transform = `translate(${dx}px, ${dy}px) scale(${scaleX}, ${scaleY})`;
      dock.style.opacity = '0';
      dock.style.visibility = 'visible';

      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          dock.style.transition = 'transform 1100ms cubic-bezier(0.16, 1, 0.3, 1), opacity 700ms ease-out';
          dock.style.transform = 'translate(0px, 0px) scale(1, 1)';
          dock.style.opacity = '1';
        });
      });

      dock.addEventListener('transitionend', function handler(e) {
        if (e.propertyName === 'transform') {
          dock.style.transition = '';
          dock.style.transform = '';
          dock.style.willChange = '';
          dock.removeEventListener('transitionend', handler);
        }
      });

      collapseOriginalSection();
    }

    function collapseOriginalSection() {
      const startHeight = statsSection.scrollHeight;
      statsSection.style.overflow = 'hidden';
      statsSection.style.maxHeight = startHeight + 'px';
      statsSection.style.transition = 'max-height 800ms cubic-bezier(0.16, 1, 0.3, 1), opacity 600ms ease, padding 800ms ease, margin 800ms ease';

      requestAnimationFrame(() => {
        statsSection.style.maxHeight = '0px';
        statsSection.style.opacity = '0';
        statsSection.style.paddingTop = '0px';
        statsSection.style.paddingBottom = '0px';
        statsSection.style.marginTop = '0px';
        statsSection.style.marginBottom = '0px';
        statsSection.style.borderBottom = 'none';
      });

      setTimeout(() => { statsSection.style.display = 'none'; }, 850);
    }

    setTimeout(dockStats, 2200);
  });
</script>

</body>
</html>