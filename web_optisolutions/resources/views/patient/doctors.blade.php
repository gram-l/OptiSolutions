<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PolyClinic Lipa | Doctors</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  
  @vite(['resources/css/patient_css/doctors.css', 'resources/js/script.js', 'resources/js/navbar-loader.js', 'resources/js/privacy-notice.js'])

</head>
<body>

<!-- Navbar is injected by navbar-loader.js -->

<!-- ========== DOCTORS PAGE ========== -->
<section class="section" id="doctors" style="background: var(--gray-50);">
  <div class="container">
    <div class="doctors-header">
      <h2 class="section-title">Meet Our Medical Experts</h2>
      <p class="section-subtitle">Our team of board-certified physicians brings years of specialized experience and compassionate care to help you achieve optimal health.</p>
    </div>
    <div class="filter-tabs" id="filterTabs">
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
    <div class="doctors-grid" id="doctorsGrid"></div>
  </div>
</section>

<!-- ========== DOCTOR MODAL ========== -->
<div id="doctorModal" class="doctor-modal">
  <div class="modal-content">
    <button class="modal-close" onclick="closeDoctorModal()"><i class="fas fa-times"></i></button>
    <div id="modalContent"></div>
  </div>
</div>

@include('patient.partials.footer')

@include('patient.partials.chatbot-widget')

@vite(['resources/js/script.js', 'resources/js/navbar-loader.js'])

<script>
  document.addEventListener('DOMContentLoaded', () => {
    loadDoctorsData();
  });
</script>

</body>
</html>