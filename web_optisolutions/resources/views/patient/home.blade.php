<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PolyClinic | Home</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  @vite(['resources/css/patient_css/home.css', 'resources/js/script.js', 'resources/js/navbar-loader.js', 'resources/js/privacy-notice.js'])

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
        <span class="stat-modern-number">{{ $yearsOfExcellence }}+</span>
        <span class="stat-modern-label">Years of Excellence</span>
        <span class="stat-modern-desc">Serving the community since 2011</span>
      </div>
      <div class="stat-modern-item">
        <span class="stat-modern-number">{{ $patientsCount >= 1000 ? round($patientsCount / 1000) . 'k+' : $patientsCount . '+' }}</span>
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
          <span class="stat-rating-count">from {{ $feedbackCount }} reviews</span>
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
      <h2 class="doctors-modern-title">Meet Our Medical Experts</h2>
      <p class="doctors-modern-subtitle">Our board-certified physicians bring years of specialized experience and compassionate care to help you achieve optimal health.</p>
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

      // Animate the whole dock as one block using the FLIP technique off the
      // group's bounding box, so all 4 stats fly together as a unit from
      // their old horizontal spot to their new column beside the logo.
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