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
        <p><strong>{{ $clinicInfo->clinic_name ?? 'PolyClinic Lipa' }}</strong> is a multi-specialty clinic located at <strong>{{ $clinicInfo->address ?? 'TM Kalaw St., Lipa City, Batangas, 4217' }}</strong>. We are committed to providing comprehensive and compassionate healthcare to the Lipa community and surrounding areas.</p>
        <p>{{ $clinicInfo->about_us ?? 'We offer a wide range of services including Pediatrics, Adult Medicine, Ophthalmology, OB-GYN, and Surgery consults. Our team of experienced, board-certified specialists ensures that every patient receives personalized attention and the highest quality of care — from your first consultation to every follow-up after.' }}</p>

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
            <span class="stat-number">{{ $avgRating ?? '4.9' }}</span>
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
            <p>To deliver accessible, compassionate, and high-quality healthcare to every Filipino family — provided by licensed, board-certified specialists who uphold the highest standards of patient safety, transparency, and ethical care, so every patient can trust us with their health and their family's wellbeing.</p>
          </div>
        </div>

        <div class="mv-card-modern">
          <div class="mv-icon-modern"><i class="fas fa-eye"></i></div>
          <div class="mv-content-modern">
            <h3>Our Vision</h3>
            <p>To be the most trusted multi-specialty clinic in Lipa City and Batangas — recognized for clinical excellence, honest and transparent patient care, and a lasting commitment to the health of every community we serve.</p>
          </div>
        </div>
      </div>

      <div class="mv-card-modern values-card">
        <div class="mv-icon-modern"><i class="fas fa-hand-holding-heart"></i></div>
        <div class="mv-content-modern">
          <h3>Our Core Values</h3>
          <div class="values-grid">
            <div class="value-item">
              <strong>Compassion</strong>
              <p>We treat every patient with empathy, dignity, and respect.</p>
            </div>
            <div class="value-item">
              <strong>Excellence</strong>
              <p>We uphold the highest clinical standards in every consultation.</p>
            </div>
            <div class="value-item">
              <strong>Integrity</strong>
              <p>We practice honesty and transparency in diagnosis, treatment, and billing.</p>
            </div>
            <div class="value-item">
              <strong>Patient Safety</strong>
              <p>We follow strict protocols to protect every patient's health and privacy.</p>
            </div>
            <div class="value-item">
              <strong>Teamwork</strong>
              <p>Our doctors and staff work together to serve our community better.</p>
            </div>
            <div class="value-item">
              <strong>Innovation</strong>
              <p>We embrace modern technology to improve patient outcomes.</p>
            </div>
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