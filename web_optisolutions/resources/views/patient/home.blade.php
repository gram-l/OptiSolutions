<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PolyClinic</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  @vite(['resources/css/patient_css/home.css', 'resources/css/patient_css/about.css', 'resources/css/patient_css/contact.css', 'resources/js/script.js', 'resources/js/clinic-info.js', 'resources/js/navbar-loader.js', 'resources/js/privacy-notice.js'])

  <style>
    .reviews-banner-footer { cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
    .reviews-banner-footer:hover { transform: scale(1.02); box-shadow: 0 12px 32px rgba(0,0,0,0.2); }
    .reviews-banner-footer:active { transform: scale(0.98); }

    /* Doctor grid: cards keep a fixed size (4 per row) and are centered when only 1-2 match */
    .doctors-grid-modern { display: flex !important; flex-wrap: wrap; justify-content: center; gap: 28px; }
    .doctors-grid-modern > .doctor-card { flex: 0 1 calc((100% - 84px) / 4); min-width: 240px; max-width: 100%; }
    .doctors-grid-modern > .no-doctors { flex: 1 1 100%; }
    @media (max-width: 480px) { .doctors-grid-modern > .doctor-card { flex-basis: 100%; } }

    /* Doctor card photos - inline so it works without rebuilding Vite assets */
    .doctor-card {
      padding: 0 0 24px !important;
      overflow: hidden !important;
    }
    .doctor-card .doctor-card-photo {
      width: 100% !important;
      height: 270px !important;
      margin: 0 0 16px !important;
      border-radius: 0 !important;
      border: none !important;
      overflow: hidden !important;
      background: linear-gradient(145deg, var(--primary-light, #E0F2FE), #F0F9FF);
      display: flex !important;
      align-items: center;
      justify-content: center;
    }
    .doctor-card .doctor-card-photo img {
      width: 100% !important;
      height: 100% !important;
      max-width: 100% !important;
      object-fit: cover !important;
      object-position: center top !important;
      display: block;
    }
    .doctor-card .doctor-card-photo .doctor-photo-fallback {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 64px;
      color: var(--primary, #0284C7);
    }
    .doctor-card h3,
    .doctor-card .doctor-spec,
    .doctor-card p { margin-left: 16px; margin-right: 16px; }

    /* Mission / Vision / Core Values: same card layout (icon left, text right), stacked */
    .mv-modern { display: flex; flex-direction: column; gap: 24px; }
    .mv-modern .mv-top-grid {
      display: flex !important;
      flex-direction: column;
      gap: 24px;
    }
    .mv-modern .mv-card-modern,
    .mv-modern .mv-top-grid .mv-card-modern {
      flex-direction: row !important;
      align-items: center !important;
      gap: 20px !important;
      padding: 28px 32px !important;
      width: 100%;
      height: auto !important;
      background: var(--white, #fff);
      border: 1px solid var(--gray-200, #E5E7EB);
      border-radius: 20px;
    }
    .mv-modern .mv-top-grid .mv-card-modern:hover { transform: translateX(6px); }
    .mv-modern .mv-icon-modern {
      width: 56px; height: 56px; min-width: 56px;
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px;
    }
    .mv-modern .mv-content-modern { flex: 1; min-width: 0; }
    .mv-modern .mv-content-modern h3 { margin: 0 0 6px; }
    .mv-modern .values-empty { color: var(--gray-500, #6B7280); font-size: 15px; margin: 0; }
    @media (max-width: 600px) {
      .mv-modern .mv-card-modern,
      .mv-modern .mv-top-grid .mv-card-modern { padding: 22px 20px !important; gap: 16px !important; }
    }

    /* ===== NAVBAR + ACCENT PICTURE ALWAYS LOCKED AT THE TOP =====
       about.css / contact.css put overflow-x:hidden on <body>, which turns body
       into its own scroll container and silently breaks position:sticky.
       Clipping is already handled on <html>, so body must stay visible. */
    html { overflow-x: hidden; }
    body { overflow-x: visible !important; }
    .navbar { position: -webkit-sticky; position: sticky; top: 0; z-index: 100; }

    /* ==========================================================
       TYPOGRAPHY SYSTEM - one font, one heading colour, one size per level
       Heading colour : #01204C        Body text colour : #3D4A5C
       Section titles : --fs-section   Sub-headings : --fs-sub   Body : --fs-body
       ========================================================== */
    :root {
      --brand-navy: #01204C;
      --ty-heading: #01204C;
      --ty-body: #3D4A5C;
      --fs-section: clamp(28px, 3vw, 36px);
      --fs-sub: 22px;
      --fs-card: 18px;
      --fs-body: 16px;
    }
    body, button, input, textarea, select { font-family: 'Inter', sans-serif; }

    /* Section titles (all the same size) */
    .hero-flutter-title { color: var(--ty-heading) !important; font-family: 'Inter', sans-serif !important; }
    .about-v2 .about-title,
    .about-v2 .about-v2-mvv .about-v2-mvv-title,
    .about-v2-guide-head h3,
    .services-v2 .services-modern-title,
    .doctors-v2 .doctors-modern-title,
    .cta-banner-title,
    .reviews-live-title {
      font-family: 'Inter', sans-serif !important;
      font-size: var(--fs-section) !important;
      font-weight: 800 !important;
      line-height: 1.25 !important;
      letter-spacing: 0 !important;
      text-transform: none !important;
    }
    .about-v2 .about-title, .about-v2 .about-title-line,
    .about-v2 .about-v2-mvv .about-v2-mvv-title,
    .about-v2-guide-head h3,
    .services-v2 .services-modern-title, .services-v2 .services-title-accent,
    .doctors-v2 .doctors-modern-title, .doctors-v2 .doctors-title-accent,
    .cta-banner-title { color: var(--ty-heading) !important; }
    /* Reviews sits on the dark-blue band, so its title stays white */
    .reviews-live-title { color: #fff !important; }

    /* Sub-headings (Mission, Vision, Core Values, visit guide) */
    .about-v2 .about-v2-mv .about-v2-mv-body h3,
    .about-v2 .about-v2-values-title,
    .about-v2-guide-text h4,
    .about-v2-guide-text h4 span {
      font-family: 'Inter', sans-serif !important;
      font-size: var(--fs-sub) !important;
      font-weight: 800 !important;
      line-height: 1.3 !important;
      color: var(--ty-heading) !important;
    }

    /* Card titles */
    .services-v2 .service-card-modern h3,
    .doctor-card h3 {
      font-family: 'Inter', sans-serif !important;
      font-size: var(--fs-card) !important;
      font-weight: 700 !important;
      color: var(--ty-heading) !important;
    }
    .about-v2 .about-v2-value-card strong {
      font-size: 16px !important; font-weight: 700 !important; color: var(--ty-heading) !important;
    }

    /* Body text (all the same size and colour) */
    .about-v2 .about-text p,
    .about-v2 .about-v2-mv .about-v2-mv-body p,
    .about-v2-guide-head p,
    .about-v2-guide-text p,
    .about-v2-guide-text li,
    .services-v2 .services-modern-subtitle,
    .doctors-v2 .doctors-modern-subtitle,
    .cta-banner-desc {
      font-family: 'Inter', sans-serif !important;
      font-size: var(--fs-body) !important;
      line-height: 1.7 !important;
      color: var(--ty-body) !important;
    }
    .hero-flutter-desc { color: var(--ty-body) !important; }
    .about-v2 .about-v2-value-card small {
      font-size: 15px !important; line-height: 1.55 !important; color: var(--ty-body) !important;
    }
    .about-v2-guide-text strong { color: var(--ty-heading) !important; }

    /* Clinic name / address in the intro: plain text, no highlight */
    .about-v2 .about-text .about-hl { color: inherit !important; font-weight: inherit !important; }

    /* ===== No circles behind the Mission / Vision / Core Values icons ===== */
    .about-v2 .about-v2-icon,
    .about-v2 .about-v2-mv-item.vision .about-v2-icon,
    .about-v2 .about-v2-value-icon,
    .about-v2 .about-v2-value-card:nth-child(n) .about-v2-value-icon {
      background: none !important; border-radius: 0 !important; box-shadow: none !important;
    }
    .about-v2 .about-v2-icon::before, .about-v2 .about-v2-icon::after { display: none !important; }

    /* ===== Mission / Vision / Core Values: compact spacing ===== */
    .about-v2 .about-v2-mvv { padding: 36px 32px 0; }
    .about-v2 .about-v2-mvv .about-v2-mvv-title { margin-bottom: 24px; }
    .about-v2 .about-v2-mvv .about-v2-mv { column-gap: 40px; row-gap: 20px; }
    .about-v2 .about-v2-mv .about-v2-mv-item { row-gap: 8px; }
    .about-v2 .about-v2-values { margin-top: 4px; }
    .about-v2 .about-v2-values-title { margin-bottom: 12px; }
    .about-v2 .about-v2-values-grid { grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 8px; }
    .about-v2 .about-v2-value-card { padding: 4px 8px; gap: 3px; }
    .about-v2 .about-v2-value-card small {
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .about-v2 .about-v2-value-icon .asset-icon { width: 26px; height: 26px; }
    @media (max-width: 992px) { .about-v2 .about-v2-mvv { padding: 28px 20px 0; } }

    /* ===== Before You Visit — now its own section above Contact ===== */
    .visit-section { padding: 24px 0 72px; background: var(--white, #fff); }
    .visit-section .about-v2-guide { margin-top: 48px; }
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
          <i class="fas fa-heart"></i> Leave Patient Feedback
        </a>
        <a href="#doctors" class="hero-find-specialist-btn">
          <i class="fas fa-user-doctor"></i> Find Your Specialist
        </a>
      </div>

      <!-- ========== LIVE PATIENT FEEDBACK — mini widget ========== -->
      <div class="hero-live-feedback" id="heroLiveFeedback" aria-live="polite">
        <div class="hero-live-feedback-head">
          <span class="hero-live-dot"></span>
          <span class="hero-live-feedback-label">Live Patient Feedback</span>
          <span class="hero-live-feedback-tag">Real-Time Updates</span>
        </div>
        <div class="hero-live-feedback-body" id="heroLiveFeedbackBody">
          @forelse (($recentFeedback ?? [])->take(8) as $i => $review)
            @php
              $lfFname = trim($review->patient_fname ?? '');
              $lfLname = trim($review->patient_lname ?? '');
              $lfMasked = '';
              if ($lfFname !== '') {
                  $lfChars = mb_str_split($lfFname);
                  $lfLen = count($lfChars);
                  if ($lfLen === 1) {
                      $lfMasked = '*';
                  } else {
                      $lfReveal = [0, max(0, $lfLen - 2)];
                      foreach ($lfChars as $lfIdx => $lfCh) {
                          $lfMasked .= in_array($lfIdx, $lfReveal, true) ? $lfCh : '*';
                      }
                  }
              }
              $lfName = $lfMasked !== ''
                  ? $lfMasked . ($lfLname !== '' ? ' ' . strtoupper(substr($lfLname, 0, 1)) . '.' : '')
                  : 'Anonymous Patient';
            @endphp
            <div class="hero-live-review{{ $i === 0 ? ' is-active' : '' }}" data-slide="{{ $i }}">
              <span class="hero-live-review-name">{{ $lfName }}:</span>
              <span class="hero-live-review-quote">&quot;{{ \Illuminate\Support\Str::limit($review->feedback_text, 70) }}&quot;</span>
              <span class="hero-live-review-rating"><i class="fas fa-star"></i> {{ number_format($review->star_rating, 1) }}</span>
            </div>
          @empty
            <div class="hero-live-review is-active" data-slide="0">
              <span class="hero-live-review-name">Kenneth R.:</span>
              <span class="hero-live-review-quote">&quot;Very accommodating staff and clean consultation area.&quot;</span>
              <span class="hero-live-review-rating"><i class="fas fa-star"></i> 5.0</span>
            </div>
          @endforelse
        </div>
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
<section class="section about-section about-v2" id="about">

  <div class="about-v2-hero">
    <div class="about-v2-photo-wrap">
      <div class="about-v2-photo">
        <img src="/images/Polyclinic.jpg" alt="PolyClinic building" loading="lazy">
      </div>
    </div>

    <div class="about-v2-content">
      <h2 class="about-title"><span class="about-title-line">Compassionate Care for Every</span><br>Filipino Family</h2>

      <div class="about-text">
        <p><span class="about-hl" data-clinic-field="clinic_name" data-clinic-empty="Our Clinic">{{ $publicClinic['clinic_name'] ?: 'Our Clinic' }}</span> is a multi-specialty outpatient clinic at <span class="about-hl" data-clinic-field="address" data-clinic-empty="Address has not been set yet.">{{ $publicClinic['address'] ?: 'Address has not been set yet.' }}</span>, serving Lipa City and nearby communities since {{ $clinicInfo->founded_year ?? 2011 }}.</p>
        <p data-clinic-field="about_us" data-clinic-empty="" @if($publicClinic['about_us'] === '') hidden @endif>{{ $publicClinic['about_us'] }}</p>
      </div>

      <div class="trust-badges">
        <div class="trust-badge"><i class="fas fa-circle-check"></i><span>DOH Accredited</span></div>
        <div class="trust-badge"><i class="fas fa-users"></i><span>Licensed Specialists</span></div>
        <div class="trust-badge"><i class="fas fa-lock"></i><span>Data Privacy Compliant</span></div>
      </div>

    </div>

    <svg class="about-v2-wave" viewBox="0 0 1440 220" preserveAspectRatio="none" aria-hidden="true">
      <defs><path id="aboutWaveCurve" d="M0 150 C260 205 560 120 860 88 C1120 60 1300 40 1440 18 L1440 260 L0 260Z"/></defs>
      <use href="#aboutWaveCurve" fill="#1494E3"/>
      <use href="#aboutWaveCurve" fill="#77AD19" transform="translate(0 15)"/>
      <use href="#aboutWaveCurve" fill="#F6200D" transform="translate(0 30)"/>
      <use href="#aboutWaveCurve" fill="#EFC813" transform="translate(0 44)"/>
    </svg>
  </div>

  <div class="about-v2-mvv">

  <div class="about-v2-mv">
    <div class="about-v2-mv-item mission" data-clinic-visible="mission" @if($publicClinic['mission'] === '') hidden style="display:none" @endif>
      <div class="about-v2-icon"><span class="asset-icon" style="--icon:url('/images/icons/mission.svg')" aria-hidden="true"></span></div>
      <div class="about-v2-mv-body">
        <h3>Our Mission</h3>
        <p data-clinic-field="mission" data-clinic-empty="">{{ $publicClinic['mission'] }}</p>
      </div>
    </div>

    <div class="about-v2-mv-item vision" data-clinic-visible="vision" @if($publicClinic['vision'] === '') hidden style="display:none" @endif>
      <div class="about-v2-icon"><span class="asset-icon" style="--icon:url('/images/icons/vision.svg')" aria-hidden="true"></span></div>
      <div class="about-v2-mv-body">
        <h3>Our Vision</h3>
        <p data-clinic-field="vision" data-clinic-empty="">{{ $publicClinic['vision'] }}</p>
      </div>
    </div>

    <div class="about-v2-values" data-clinic-values-wrap @if(empty($publicClinic['core_values'])) hidden style="display:none" @endif>
      <h3 class="about-v2-values-title">Our Core Values</h3>
      {{-- Each value may be "Name" or "Name|Short description". --}}
      <div class="about-v2-values-grid" data-clinic-values>
        @php $valueIcons = ['heart-pulse', 'shield-check', 'star', 'handshake', 'lock', 'users']; @endphp
        @foreach ($publicClinic['core_values'] as $i => $value)
          @php [$valueName, $valueDesc] = array_map('trim', array_pad(explode('|', $value, 2), 2, '')); @endphp
          <div class="about-v2-value-card">
            <span class="about-v2-value-icon"><span class="asset-icon" style="--icon:url('/images/icons/{{ $valueIcons[$i % count($valueIcons)] }}.svg')" aria-hidden="true"></span></span>
            <strong>{{ $valueName }}</strong>
            @if ($valueDesc !== '')<small>{{ $valueDesc }}</small>@endif
          </div>
        @endforeach
      </div>
    </div>
  </div>
  </div>
</section>

<!-- ========== SERVICES - MODERN ========== -->
<section class="services-modern-section services-v2" id="services">
  <img class="services-v2-cross" src="/images/medical-cross.svg" alt="" aria-hidden="true">
  <img class="services-v2-leaves services-v2-leaves-left" src="/images/leaves.svg" alt="" aria-hidden="true">
  <img class="services-v2-leaves services-v2-leaves-right" src="/images/leaves.svg" alt="" aria-hidden="true">
  <div class="container">
    <div class="services-modern-header">
      <h2 class="services-modern-title">Comprehensive Medical Care<br><span class="services-title-accent">For You &amp; Your Family</span></h2>
      <p class="services-modern-subtitle">We offer a wide range of specialized healthcare services delivered with compassion and expertise.</p>
    </div>
    {{-- Cards come from Admin > System Settings > Services (services table). --}}
    <div class="service-grid-modern" id="serviceCardsHome">
      @foreach ($services as $service)
        @php $card = \App\Support\ServiceCardStyle::for($service); @endphp
        <div class="service-card-modern" data-service-key="{{ $service->service_key }}" onclick="openServiceModal('{{ $service->service_key }}')">
          <div class="service-card-img @if (!$card['image']) no-image @endif">
            @if ($card['image'])
              <img src="{{ $card['image'] }}" alt="{{ $service->title }}" loading="lazy">
            @else
              <i class="fas {{ $card['icon'] }}" aria-hidden="true"></i>
            @endif
          </div>
          <div class="service-card-body">
            <div class="service-card-icon {{ $card['color'] }}"><i class="fas {{ $card['icon'] }}"></i></div>
            <div class="service-card-text">
              <h3>{{ $service->title }}</h3>
              <p>{{ $service->description }}</p>
              <button type="button" class="service-learn-more">Learn More <i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- ========== DOCTORS - MODERN ========== -->
<section class="doctors-modern-section doctors-v2" id="doctors">
  <div class="container">
    <div class="doctors-modern-header">
      <h2 class="doctors-modern-title"><span class="doctors-title-accent">Medical Experts</span></h2>
      <p class="doctors-modern-subtitle">Our board-certified physicians bring years of specialized experience and compassionate care to help you achieve optimal health.</p>
    </div>

    <div class="doctor-search-wrap">
      <i class="fas fa-search doctor-search-icon"></i>
      <input type="text" id="doctorsSearchHome" class="doctor-search-input" placeholder="Search doctors by name or specialty...">
    </div>

    <!-- The tabs are built by script.js from the services of the displayed doctors -->
    <div class="filter-tabs" id="filterTabsHome">
      <button class="filter-tab active" data-filter="all">All</button>
    </div>

    <div class="doctors-grid-modern" id="doctorsGridHome"></div>
  </div>
</section>

<!-- ========== LIVE FEEDBACK & REVIEWS ========== -->
<section class="reviews-live-section" id="reviews">
  <button type="button" class="reviews-live-arrow reviews-live-arrow-prev" id="reviewsPrevBtn" onclick="scrollReviews(-1)" aria-label="Previous reviews">
    <i class="fas fa-chevron-left"></i>
  </button>
  <button type="button" class="reviews-live-arrow reviews-live-arrow-next" id="reviewsNextBtn" onclick="scrollReviews(1)" aria-label="Next reviews">
    <i class="fas fa-chevron-right"></i>
  </button>
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

    <div class="reviews-live-grid" id="reviewsLiveGrid">
      @forelse ($recentFeedback ?? [] as $review)
        @php
          // Patients who chose "post anonymously" never show any part of their name.
          $isAnonymousReview = !empty($review->is_anonymous);
          $reviewerFname = $isAnonymousReview ? '' : trim($review->patient_fname ?? '');
          $reviewerLname = $isAnonymousReview ? '' : trim($review->patient_lname ?? '');
          $reviewerInitial = $reviewerFname !== '' ? strtoupper(substr($reviewerFname, 0, 1)) : 'A';

          // Pseudonymize the first name for display: keep the first letter and the
          // second-to-last letter visible, mask every other letter (e.g. "Naomi" -> "N**m*").
          $maskedFname = '';
          if ($reviewerFname !== '') {
              $nameChars = mb_str_split($reviewerFname);
              $nameLen = count($nameChars);
              if ($nameLen === 1) {
                  $maskedFname = '*';
              } else {
                  $revealIndexes = [0, max(0, $nameLen - 2)];
                  foreach ($nameChars as $i => $ch) {
                      $maskedFname .= in_array($i, $revealIndexes, true) ? $ch : '*';
                  }
              }
          }

          if ($maskedFname !== '') {
              $reviewerName = $maskedFname . ($reviewerLname !== '' ? ' ' . strtoupper(substr($reviewerLname, 0, 1)) . '.' : '');
          } else {
              $reviewerName = 'Anonymous Patient';
          }
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
          </div>
        </div>
      @empty
        <p class="reviews-live-empty">No positive reviews yet — be the first to share your experience!</p>
      @endforelse
    </div>
  </div>
</section>

<!-- ========== BEFORE YOU VISIT (visit guide + FAQ) ========== -->
<section class="visit-section" id="visit">
  <div class="about-v2-guide">
    <div class="about-v2-guide-head">
      <h3>Everything You Need to Know Before You Visit</h3>
      <p>From scheduling your visit to what to expect when you arrive, here is how we make getting care simple for you and your family.</p>
    </div>

    <div class="about-v2-guide-body">
      <div class="about-v2-guide-media" aria-hidden="false">
        <div class="about-v2-guide-back"><img src="/images/Polyclinic.jpg" alt="" loading="lazy"></div>
        <div class="about-v2-guide-front"><img src="/images/walkin.png" alt="A PolyClinic staff member assisting a patient at the front desk" loading="lazy"></div>
      </div>

      <div class="about-v2-guide-text">
        <h4>Visiting <span data-clinic-field="clinic_name" data-clinic-empty="Our Clinic">{{ $publicClinic['clinic_name'] ?: 'Our Clinic' }}</span>, Made Simple</h4>

        <p>Scheduling a visit takes just a few minutes. Open <strong>Schedule Visit</strong> in our chatbot, or browse our doctors through <a href="#doctors">Find Your Specialist</a>, then choose your service, doctor and preferred schedule. Your visit details are sent to you by email, and once your visit is completed, a review code is emailed so you can share your feedback.</p>

        <p>Our staff is always ready to help with rescheduling, cancellations and any questions, so you know what to expect before you arrive.</p>

        <ul>
          <li><strong>Walk-ins are welcome.</strong> Scheduling ahead through our chatbot lets you secure a slot with your preferred doctor. Availability can vary by doctor and day, so please confirm with our staff.</li>
          <li><strong>Fees, payment and HMO.</strong> Please ask our staff for current fees, accepted payment methods and HMO or insurance coverage before your visit.</li>
          <li><strong>Laboratory and diagnostic tests.</strong> Ask our staff to confirm availability, schedule and preparation for your specific test.</li>
          <li><strong>Records and certificates.</strong> Request medical records or certificates directly from our staff.</li>
          <li><strong>Extra assistance.</strong> Seniors, PWD and patients with mobility needs may tell us ahead of time so we can help.</li>
          <li><strong>Your privacy.</strong> We handle your information in line with our <a href="{{ route('privacy-notice') }}">Privacy Notice</a>.</li>
        </ul>

        <button type="button" class="about-v2-faq-btn" onclick="openFaqModal()">
          <span class="asset-icon" style="--icon:url('/images/icons/circle-help.svg')" aria-hidden="true"></span>
          Frequently Asked Questions
        </button>
      </div>
    </div>
  </div>
</section>

<!-- ========== CTA BANNER - MODERN ========== -->
<section class="cta-banner" id="contact">
  <div class="container">
    <div class="cta-banner-inner">
      <div class="cta-banner-content">
        <span class="cta-banner-tag">📍 Clinic Location</span>
        <h2 class="cta-banner-title">Visit Our Clinic Today</h2>
        <p class="cta-banner-desc" data-clinic-field="address" data-clinic-empty="Address has not been set yet.">{{ $publicClinic['address'] ?: 'Address has not been set yet.' }}</p>
        <div class="cta-banner-info">
          <div class="cta-info-item">
            <i class="fas fa-phone-alt"></i>
            <div>
              <span class="cta-info-label">Call Us</span>
              <span class="cta-info-value" data-clinic-field="contact_no" data-clinic-empty="Phone number has not been set yet.">{{ $publicClinic['contact_no'] ?: 'Phone number has not been set yet.' }}</span>
            </div>
          </div>
          <div class="cta-info-item">
            <i class="fas fa-clock"></i>
            <div>
              <span class="cta-info-label">Clinic Hours</span>
              <span class="cta-info-value" data-clinic-field="operating_hours" data-clinic-empty="Clinic hours have not been set yet.">{{ $publicClinic['operating_hours'] ?: 'Clinic hours have not been set yet.' }}</span>
            </div>
          </div>
        </div>
        <a href="{{ $publicClinic['directions_url'] }}" data-clinic-directions @if ($publicClinic['directions_url'] === '') hidden style="display:none" @endif target="_blank" rel="noopener noreferrer" class="btn-cta-banner">Get Directions <i class="fas fa-arrow-right"></i></a>
      </div>
      <figure class="cta-banner-photo">
        <figcaption class="cta-photo-caption"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Lipa City, Batangas</figcaption>
        <img src="/images/Polyclinic2.jpg" alt="PolyClinic Lipa building front" loading="lazy">
      </figure>
      <div class="cta-banner-map" data-clinic-visible="address" @if ($publicClinic['address'] === '') hidden style="display:none" @endif>
        <iframe @if ($publicClinic['map_url'] !== '') src="{{ $publicClinic['map_url'] }}" @endif data-clinic-map title="Clinic location" allowfullscreen loading="lazy"></iframe>
      </div>
    </div>
  </div>
</section>

<!-- ========== SERVICE MODAL ========== -->
<div id="serviceModal" class="service-modal">
  <div class="service-modal-content">
    <div id="serviceModalBody"></div>
  </div>
</div>

<!-- ========== DOCTOR MODAL ========== -->
<div id="doctorModal" class="doctor-modal">
  <div class="modal-content">
    <button class="modal-close" onclick="closeDoctorModal()"><i class="fas fa-times"></i></button>
    <div id="modalContent"></div>
  </div>
</div>

@include('patient.partials.footer')

@include('patient.partials.chatbot-widget')


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

  /* ==========================================================
     PATIENT VOICES — horizontal scroll arrows
     ========================================================== */
  function scrollReviews(direction) {
    const grid = document.getElementById('reviewsLiveGrid');
    if (!grid) return;
    const card = grid.querySelector('.review-live-card');
    const step = card ? card.offsetWidth + 24 : 340; // card width + gap
    grid.scrollBy({ left: direction * step, behavior: 'smooth' });
  }

  document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('reviewsLiveGrid');
    const prevBtn = document.getElementById('reviewsPrevBtn');
    const nextBtn = document.getElementById('reviewsNextBtn');
    const section = document.getElementById('reviews');
    if (!grid || !prevBtn || !nextBtn || !section) return;

    function updateReviewArrows() {
      const maxScroll = grid.scrollWidth - grid.clientWidth - 2;
      prevBtn.disabled = grid.scrollLeft <= 0;
      nextBtn.disabled = grid.scrollLeft >= maxScroll;
    }

    function positionReviewArrows() {
      const sectionRect = section.getBoundingClientRect();
      const gridRect = grid.getBoundingClientRect();
      const centerY = (gridRect.top - sectionRect.top) + (gridRect.height / 2);
      prevBtn.style.top = centerY + 'px';
      nextBtn.style.top = centerY + 'px';
    }

    grid.addEventListener('scroll', updateReviewArrows);
    window.addEventListener('resize', function () {
      updateReviewArrows();
      positionReviewArrows();
    });
    updateReviewArrows();
    positionReviewArrows();
    // Recalculate after images/fonts settle layout.
    setTimeout(positionReviewArrows, 400);
  });

  /* HERO — LIVE PATIENT FEEDBACK mini widget: auto-rotate */
  document.addEventListener('DOMContentLoaded', function () {
    const body = document.getElementById('heroLiveFeedbackBody');
    if (!body) return;
    const slides = body.querySelectorAll('.hero-live-review');
    if (slides.length <= 1) return;

    let current = 0;
    let timer = setInterval(advance, 4000);

    function advance() {
      slides[current].classList.remove('is-active');
      current = (current + 1) % slides.length;
      slides[current].classList.add('is-active');
    }

    // Pause the rotation while the user is reading/hovering, resume after.
    const widget = document.getElementById('heroLiveFeedback');
    if (widget) {
      widget.addEventListener('mouseenter', () => clearInterval(timer));
      widget.addEventListener('mouseleave', () => { timer = setInterval(advance, 4000); });
    }
  });
</script>

</body>
</html>