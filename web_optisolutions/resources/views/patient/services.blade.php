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
    <div class="services-page-grid" id="servicesGrid">
      <!-- Dynamically populated by loadServicesData() / renderServiceCards() sa script.js -->
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
  document.addEventListener('DOMContentLoaded', () => {
    loadServicesData();
  });
</script>

</body>
</html>