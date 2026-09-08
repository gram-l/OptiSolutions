<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PolyClinic | About Us</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  @vite(['resources/css/patient_css/about.css', 'resources/js/script.js', 'resources/js/navbar-loader.js', 'resources/js/privacy-notice.js'])

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
        <p><strong>{{ $clinicInfo->clinic_name }}</strong> is a multi-specialty clinic located at <strong>{{ $clinicInfo->address }}</strong>. We are committed to providing comprehensive and compassionate healthcare to the Lipa community and surrounding areas.</p>
        <p>{{ $clinicInfo->about_us }}</p>

        <!-- Trust badges -->
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
            <span class="stat-number">{{ $specialistsCount }}+</span>
            <span class="stat-label">Specialists</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">{{ $avgRating }}</span>
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
      <div class="mv-top-grid">
        <div class="mv-card-modern">
          <div class="mv-icon-modern"><i class="fas fa-bullseye"></i></div>
          <div class="mv-content-modern">
            <h3>Our Mission</h3>
            <p>{{ $clinicInfo->mission }}</p>
          </div>
        </div>

        <div class="mv-card-modern">
          <div class="mv-icon-modern"><i class="fas fa-eye"></i></div>
          <div class="mv-content-modern">
            <h3>Our Vision</h3>
            <p>{{ $clinicInfo->vision }}</p>
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

@include('patient.partials.footer')

@include('patient.partials.chatbot-widget')

@vite(['resources/js/script.js', 'resources/js/navbar-loader.js'])

</body>
</html>