// ============ DOCTORS / SERVICES / CLINIC INFO DATA LOADING (UNCHANGED FROM ORIGINAL) ============

let doctorsData = [];
let servicesData = {};   // keyed by service_key, mirrors old `serviceDetails`
let clinicInfo = null;

function genderIcon(gender) {
  return gender === 'female' ? '👩‍⚕️' : '👨‍⚕️';
}

function formatTime(t) {
  if (!t) return '';
  let [h, m] = t.split(':');
  h = parseInt(h, 10);
  const period = h >= 12 ? 'pm' : 'am';
  h = h % 12 || 12;
  return `${h}${m !== '00' ? ':' + m : ''}${period}`;
}

// ============ FETCH: DOCTORS (doctors + doctor_schedules) ============
async function loadDoctorsData() {
  try {
    const res = await fetch('/api/doctors');
    const raw = await res.json();

    doctorsData = raw.map(doc => ({
      id: doc.doctor_id,
      name: doc.doctor_name,
      spec: doc.specialty,
      icon: genderIcon(doc.gender),
      yearsExp: doc.years_experience,
      education: doc.education,
      license: doc.license,
      schedule: (doc.schedules || []).map(s => ({
        day: s.day,
        time: `${formatTime(s.start_time)} - ${formatTime(s.end_time)}`
      })),
      bio: doc.description,
      clinic: doc.clinic_room,
      fellowship: doc.fellowship,
      available: doc.available !== 0
    })).filter(d => d.available);

    renderDoctors('all', 'doctorsGrid');
    renderDoctors('all', 'doctorsGridHome');
    setupFilterTabs('filterTabs', 'doctorsGrid');
    setupFilterTabs('filterTabsHome', 'doctorsGridHome');
  } catch (err) {
    console.error('Failed to load doctors:', err);
  }
}

// ============ FETCH: SERVICES (services + service_conditions + service_doctors) ============
async function loadServicesData() {
  try {
    const res = await fetch('/api/services');
    const raw = await res.json();

    servicesData = {};
    raw.filter(svc => svc.available !== 0).forEach(svc => {
      servicesData[svc.service_key] = {
        id: svc.service_id,
        title: svc.title,
        icon: svc.icon,
        description: svc.description,
        conditions: svc.conditions || [],
        doctors: svc.doctors || [],
        room: svc.room,
        schedule: svc.schedule
      };
    });

    renderServiceCards();
  } catch (err) {
    console.error('Failed to load services:', err);
  }
}

function renderServiceCards(containerId = 'servicesGrid') {
  const grid = document.getElementById(containerId);
  if (!grid) return;
  grid.innerHTML = Object.keys(servicesData).map(key => {
    const svc = servicesData[key];
    return `
      <div class="service-card" onclick="openServiceModal('${key}')">
        <div class="service-card-icon"><i class="fas ${svc.icon}"></i></div>
        <h3>${svc.title}</h3>
        <p>${svc.description}</p>
      </div>`;
  }).join('');
}

// ============ FETCH: CLINIC INFO ============
async function loadClinicInfo() {
  try {
    const res = await fetch('/api/clinic-info');
    const raw = await res.json();
    clinicInfo = Array.isArray(raw) ? raw[0] : raw;
    renderClinicInfo();
  } catch (err) {
    console.error('Failed to load clinic info:', err);
  }
}

function renderClinicInfo() {
  if (!clinicInfo) return;
  const map = {
    clinicName: clinicInfo.clinic_name,
    clinicAddress: clinicInfo.address,
    clinicContact: clinicInfo.contact_no,
    clinicEmail: clinicInfo.email,
    clinicHours: clinicInfo.operating_hours,
    clinicAbout: clinicInfo.about_us,
    clinicFacebook: clinicInfo.facebook_link
  };
  Object.entries(map).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el && value) el.textContent = value;
  });
}

// ============ RENDER DOCTORS ============
function renderDoctors(filter = 'all', containerId = 'doctorsGrid') {
  const grid = document.getElementById(containerId);
  if (!grid) return;
  const filtered = filter === 'all' ? doctorsData : doctorsData.filter(doc => doc.spec === filter);
  if (filtered.length === 0) {
    grid.innerHTML = `<div class="no-doctors">No doctors found for this specialty.</div>`;
    return;
  }
  grid.innerHTML = filtered.map(doc => `
    <div class="doctor-card" onclick="showDoctorDetails(${doc.id})">
      <div class="doctor-avatar">${doc.icon}</div>
      <h3>${doc.name}</h3>
      <span class="doctor-spec">${doc.spec}</span>
      <p>${doc.yearsExp} years of experience</p>
    </div>`).join('');
}

function setupFilterTabs(containerId = 'filterTabs', gridId = 'doctorsGrid') {
  const tabsContainer = document.getElementById(containerId);
  if (!tabsContainer) return;
  const tabs = tabsContainer.querySelectorAll('.filter-tab');
  tabs.forEach(tab => {
    tab.addEventListener('click', function() {
      tabs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      renderDoctors(this.dataset.filter, gridId);
    });
  });
}

// ============ DOCTOR MODAL ============
function showDoctorDetails(id) {
  const doc = doctorsData.find(d => d.id === id);
  if (!doc) return;
  const scheduleHtml = doc.schedule.map(s => `<li><span>${s.day}</span><span>${s.time}</span></li>`).join('');
  document.getElementById('modalContent').innerHTML = `
    <div class="modal-header">
      <div class="modal-avatar">${doc.icon}</div>
      <h2>${doc.name}</h2>
      <div class="modal-spec">${doc.spec}</div>
    </div>
    <div class="modal-body">
      <div class="doctor-info-section">
        <div class="section-label"><i class="fas fa-user-md"></i> Professional Profile</div>
        <div class="info-grid">
          <div class="info-item"><div class="info-item-label">Experience</div><div class="info-item-value">${doc.yearsExp}+ years</div></div>
          <div class="info-item"><div class="info-item-label">License</div><div class="info-item-value">${doc.license}</div></div>
          <div class="info-item"><div class="info-item-label">Medical Degree</div><div class="info-item-value">${doc.education}</div></div>
          <div class="info-item"><div class="info-item-label">Fellowship</div><div class="info-item-value">${doc.fellowship}</div></div>
        </div>
      </div>
      <div class="doctor-info-section">
        <div class="section-label"><i class="fas fa-calendar-week"></i> Clinic Schedule</div>
        <ul class="schedule-list-modal">${scheduleHtml}</ul>
        <div class="info-item" style="margin-top:12px;"><div class="info-item-label">Location</div><div class="info-item-value">${doc.clinic}, PolyClinic Lipa</div></div>
      </div>
      <div class="doctor-info-section">
        <div class="section-label"><i class="fas fa-heartbeat"></i> Biography</div>
        <div class="bio-text">${doc.bio}</div>
      </div>
      <button class="modal-book-btn" onclick="bookDoctorFromModal('${doc.name}')">Schedule Visit with ${doc.name}</button>
    </div>`;
  document.getElementById('doctorModal').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeDoctorModal() {
  document.getElementById('doctorModal').classList.remove('active');
  document.body.style.overflow = '';
}

// ============ SERVICE MODAL ============
function openServiceModal(serviceKey) {
  const service = servicesData[serviceKey];
  if (!service) return;
  document.getElementById('serviceModalBody').innerHTML = `
    <div class="service-modal-header">
      <div class="service-modal-icon"><i class="fas ${service.icon}"></i></div>
      <h2>${service.title}</h2>
    </div>
    <div class="service-modal-body">
      <div class="service-modal-description">
        <h4><i class="fas fa-info-circle"></i> About This Service</h4>
        <p>${service.description}</p>
      </div>
      <div class="service-modal-section">
        <h4><i class="fas fa-list-check"></i> Common Conditions & Services</h4>
        <ul class="service-modal-list">
          ${service.conditions.map(c => `<li><i class="fas fa-check-circle"></i> ${c}</li>`).join('')}
        </ul>
      </div>
      <div class="service-modal-section">
        <h4><i class="fas fa-user-md"></i> Our Specialists</h4>
        <div class="service-modal-doctors">
          ${service.doctors.map(d => `<span class="service-modal-doctor-tag"><i class="fas fa-stethoscope"></i> ${d}</span>`).join('')}
        </div>
      </div>
      <div class="service-modal-section">
        <h4><i class="fas fa-clock"></i> Clinic Schedule</h4>
        <div class="service-modal-info-grid">
          <div class="service-modal-info-item"><span class="label">Location</span><span class="value">${service.room}, PolyClinic Lipa</span></div>
          <div class="service-modal-info-item"><span class="label">Schedule</span><span class="value">${service.schedule}</span></div>
        </div>
      </div>
      <button class="service-modal-book-btn" onclick="bookService('${service.title}')">
        <i class="fas fa-calendar-check"></i> Schedule Visit for ${service.title}
      </button>
    </div>`;
  document.getElementById('serviceModal').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeServiceModal() {
  document.getElementById('serviceModal').classList.remove('active');
  document.body.style.overflow = '';
}

// ============ CLOSE MODALS ============
document.addEventListener('click', function(event) {
  const modal = document.getElementById('serviceModal');
  if (event.target === modal) closeServiceModal();
});
document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') closeServiceModal();
});

// ============ CHATBOT CORE (REWRITTEN FOR BOTMAN) ============

let botUserId = localStorage.getItem('botman_user_id');
if (!botUserId) {
  botUserId = 'user_' + Math.random().toString(36).substring(2) + Date.now();
  localStorage.setItem('botman_user_id', botUserId);
}

let chatStarted = false;

function addMessage(text, sender, isHtml = false) {
  const container = document.getElementById('chatMessages');
  if (!container) return;
  const div = document.createElement('div');
  div.className = `chat-message ${sender}`;
  const bubble = document.createElement('div');
  bubble.className = 'chat-bubble';
  if (isHtml) bubble.innerHTML = text;
  else bubble.textContent = text;
  div.appendChild(bubble);
  container.appendChild(div);
  container.scrollTop = container.scrollHeight;
}

function addUserMessage(text, isHtml = false) {
  addMessage(text, 'user', isHtml);
}

// Renders one or more buttons as a bot bubble, styled like your old menu/service buttons.
function renderButtons(buttons) {
  const html = `<div class="menu-grid"><div class="menu-row">` +
    buttons.map(b =>
      `<button class="menu-btn" data-value="${encodeURIComponent(b.value)}">${b.text}</button>`
    ).join('') +
    `</div></div>`;
  addMessage(html, 'bot', true);

  const container = document.getElementById('chatMessages');
  const lastBubble = container.lastElementChild.querySelector('.chat-bubble');
  lastBubble.querySelectorAll('button[data-value]').forEach(btn => {
    btn.onclick = () => {
      const value = decodeURIComponent(btn.dataset.value);
      sendToBotman(value, btn.textContent.trim());
    };
  });
}

// Core call to BotMan.

// ============ GLOBAL FUNCTIONS ============
window.sendChatMessage      = sendChatMessage;
window.toggleChat           = toggleChat;
window.handleImageUpload    = handleImageUpload;
window.bookDoctorFromModal  = bookDoctorFromModal;
window.bookService          = bookService;
window.showDoctorDetails    = showDoctorDetails;
window.closeDoctorModal     = closeDoctorModal;
window.openServiceModal     = openServiceModal;
window.closeServiceModal    = closeServiceModal;