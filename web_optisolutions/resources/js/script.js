// ============ DOCTORS / SERVICES / CLINIC INFO DATA LOADING (UNCHANGED FROM ORIGINAL) ============

let doctorsData = [];
let servicesData = {};   // keyed by service_key, mirrors old `serviceDetails`
let clinicInfo = null;

function genderIcon(gender) {
  return gender === 'female'
    ? '<i class="fas fa-user-nurse"></i>'
    : '<i class="fas fa-user-doctor"></i>';
} //changed to icons (mika)

function doctorPhotoUrl(doc) {
  if (!doc.profile_image) return null;
  return /^https?:\/\//.test(doc.profile_image) ? doc.profile_image : `/storage/${doc.profile_image}`;
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
      gender: doc.gender, // added this line to pass gender to handleDoctorPhotoError
      photo: doctorPhotoUrl(doc), //added this line to display the doctor photo 
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
    setupFilterTabs('filterTabsHome', 'doctorsGridHome', 'doctorsSearchHome');
    setupDoctorSearch('doctorsSearchHome', 'filterTabsHome', 'doctorsGridHome');
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

    renderServiceCards('servicesGrid');       // para sa services.blade.php
    renderServiceCards('serviceCardsHome');   // para sa home.blade.php (kung meron)
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
      <div class="service-page-card" onclick="openServiceModal('${key}')">
        <div class="service-page-icon"><i class="fas ${svc.icon}"></i></div>
        <h3>${svc.title}</h3>
        <p>${svc.description}</p>
        <span class="service-page-link">Learn More →</span>
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
function renderDoctors(filter = 'all', containerId = 'doctorsGrid', searchTerm = '') {
  const grid = document.getElementById(containerId);
  if (!grid) return;
  let filtered = filter === 'all' ? doctorsData : doctorsData.filter(doc => doc.spec === filter);

  const term = searchTerm.trim().toLowerCase();
  if (term) {
    filtered = filtered.filter(doc =>
      doc.name.toLowerCase().includes(term) ||
      doc.spec.toLowerCase().includes(term)
    );
  }

  if (filtered.length === 0) {
    grid.innerHTML = `<div class="no-doctors">No doctors found${term ? ` matching "${searchTerm}"` : ' for this specialty'}.</div>`;
    return;
  }
grid.innerHTML = filtered.map(doc => `
  <div class="doctor-card" onclick="showDoctorDetails(${doc.id})">
    <div class="doctor-card-photo">
      ${doc.photo 
      
  ? `<img src="${doc.photo}" alt="${doc.name}" loading="lazy" onerror="handleDoctorPhotoError(this,'${doc.gender}')">`
  : `<div class="doctor-photo-fallback">${doc.icon}</div>`}
    </div>
    <h3>${doc.name}</h3>
    <span class="doctor-spec">${doc.spec}</span>
    <p>${doc.yearsExp} years of experience</p>
  </div>`).join('');
}

function setupFilterTabs(containerId = 'filterTabs', gridId = 'doctorsGrid', searchInputId = null) {
  const tabsContainer = document.getElementById(containerId);
  if (!tabsContainer) return;
  const tabs = tabsContainer.querySelectorAll('.filter-tab');
  tabs.forEach(tab => {
    tab.addEventListener('click', function() {
      tabs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      const searchInput = searchInputId ? document.getElementById(searchInputId) : null;
      renderDoctors(this.dataset.filter, gridId, searchInput ? searchInput.value : '');
    });
  });
}

// ============ DOCTOR SEARCH BAR (HOME) ============
function setupDoctorSearch(searchInputId, tabsContainerId, gridId) {
  const input = document.getElementById(searchInputId);
  if (!input) return;
  input.addEventListener('input', () => {
    const tabsContainer = document.getElementById(tabsContainerId);
    const activeTab = tabsContainer ? tabsContainer.querySelector('.filter-tab.active') : null;
    const filter = activeTab ? activeTab.dataset.filter : 'all';
    renderDoctors(filter, gridId, input.value);
  });
}

// ============ DOCTOR MODAL ============

// Maps a doctor's specialty string (as used by the filter tabs) to the
// matching service_key in servicesData, so the modal can reuse the real
// "conditions" list already maintained for that department as the doctor's
// "Clinical Focus Areas" — no separate per-doctor tagging needed.
const doctorSpecialtyToServiceKey = {
  'Pediatrics': 'pediatrics',
  'OB-Gyne': 'obgyne',
  'Surgery': 'surgery',
  'IM-Pulmonology': 'pulmonology',
  'Ophthalmology': 'ophthalmology',
  'IM-Cardiology': 'cardiology',
  'General / Adult Medicine': 'adultmedicine',
  'Medical Oncology': 'oncology'
};

function getClinicalFocusAreas(spec) {
  const key = doctorSpecialtyToServiceKey[spec];
  if (key && servicesData[key] && Array.isArray(servicesData[key].conditions)) {
    return servicesData[key].conditions.slice(0, 6);
  }
  return [];
}

function showDoctorDetails(id) {
  const doc = doctorsData.find(d => d.id === id);
  if (!doc) return;

  const phone = (clinicInfo && clinicInfo.contact_no) ? clinicInfo.contact_no : '0985 475 5511';
  const spec = doc.spec || 'General Practice';
  const focusAreas = getClinicalFocusAreas(doc.spec);
  const firstSchedule = doc.schedule[0];

  const subtitle = doc.fellowship
    ? `Fellow, ${doc.fellowship}`
    : (doc.education || spec);

  const scheduleRows = doc.schedule.length
    ? doc.schedule.map(s => `
        <div class="schedule-row-card">
          <span class="schedule-day-badge">${s.day.slice(0, 3).toUpperCase()}</span>
          <div class="schedule-row-info">
            <strong>${s.day} Schedule</strong>
            <span>Outpatient Consultation</span>
          </div>
          <span class="schedule-row-time">${s.time}</span>
        </div>`).join('')
    : `<p class="bio-text">No fixed clinic schedule listed yet — please call to confirm availability.</p>`;

  document.getElementById('modalContent').innerHTML = `
    <div class="modal-header">
      <div class="doctor-modal-tags">
        <span class="doctor-modal-tag">${spec.toUpperCase()}</span>
        ${doc.license ? `<span class="doctor-modal-tag certified"><i class="fas fa-check-circle"></i> Board Certified</span>` : ''}
      </div>
      <h2>${doc.name}</h2>
    </div>

    <div class="doctor-modal-summary-card">
      <div class="doctor-modal-summary-photo">
        ${doc.photo
          ? `<img src="${doc.photo}" alt="${doc.name}" onerror="handleDoctorPhotoError(this,'${doc.gender}')">`
          : `<div class="doctor-photo-fallback">${doc.icon}</div>`}
        ${doc.license ? `<span class="doctor-modal-verified-badge"><i class="fas fa-check"></i></span>` : ''}
      </div>
      <div class="doctor-modal-summary-info">
        <h3>${doc.name}</h3>
        <p>${subtitle}</p>
      </div>
      <div class="doctor-modal-summary-stats">
        <div class="summary-stat"><span class="summary-stat-value">${doc.yearsExp ? doc.yearsExp + '+ Yrs' : '—'}</span><span class="summary-stat-label">Experience</span></div>
        <div class="summary-stat"><span class="summary-stat-value">${doc.fellowship || '—'}</span><span class="summary-stat-label">Fellowship</span></div>
        <div class="summary-stat"><span class="summary-stat-value">${doc.license || '—'}</span><span class="summary-stat-label">License No.</span></div>
      </div>
    </div>

    <div class="doctor-modal-tabbar" id="doctorModalTabbar">
      <button class="doctor-modal-tab active" data-tab="overview"><i class="fas fa-user"></i> Overview &amp; Biography</button>
      <button class="doctor-modal-tab" data-tab="schedule"><i class="fas fa-calendar-check"></i> Schedule &amp; Clinic</button>
      <button class="doctor-modal-tab" data-tab="credentials"><i class="fas fa-graduation-cap"></i> Credentials &amp; Affiliations</button>
    </div>

    <div class="doctor-modal-scrollarea">
    <div class="doctor-modal-tabpanels">
      <div class="doctor-modal-tabpanel active" data-panel="overview">
        <p class="bio-text">${orNA(doc.bio)}</p>
        <div class="doctor-modal-grid">
          <div class="doctor-modal-box">
            <div class="doctor-modal-box-label"><i class="fas fa-hospital"></i> Primary Consultation</div>
            <div class="info-item-value" style="margin-bottom:6px;">PolyClinic Lipa</div>
            <div class="sub-value-light"><i class="fas fa-door-open"></i> ${doc.clinic ? 'Room ' + doc.clinic : 'Room TBA'}</div>
            ${firstSchedule ? `<div class="sub-value-light"><i class="fas fa-clock"></i> ${firstSchedule.day}: ${firstSchedule.time}</div>` : ''}
          </div>
          <div class="doctor-modal-box">
            <div class="doctor-modal-box-label"><i class="fas fa-stethoscope"></i> Clinical Focus Areas</div>
            <div class="focus-tags">
              ${focusAreas.length
                ? focusAreas.map(c => `<span class="focus-tag">${c}</span>`).join('')
                : `<span class="focus-tag">${spec}</span>`}
            </div>
          </div>
        </div>
      </div>

      <div class="doctor-modal-tabpanel" data-panel="schedule">
        <div class="doctor-modal-box schedule-box">
          <div class="schedule-box-header">
            <div>
              <h4>Clinic Availability</h4>
              <p>Direct consultation schedule for PolyClinic Lipa</p>
            </div>
            <span class="room-pill">${doc.clinic ? 'Room ' + doc.clinic : 'Room TBA'}</span>
          </div>
          ${scheduleRows}
          <div class="schedule-note"><i class="fas fa-circle-info"></i> For urgent concerns, please contact the main clinic desk.</div>
        </div>
      </div>

      <div class="doctor-modal-tabpanel" data-panel="credentials">
        <div class="doctor-modal-grid">
          <div class="doctor-modal-box credential-box">
            <div class="credential-icon"><i class="fas fa-graduation-cap"></i></div>
            <h4>Medical Education</h4>
            <p class="credential-highlight">${orNA(doc.education)}</p>
          </div>
          <div class="doctor-modal-box credential-box">
            <div class="credential-icon accent"><i class="fas fa-award"></i></div>
            <h4>Fellowship &amp; Certification</h4>
            <p class="credential-highlight">${orNA(doc.fellowship)}</p>
            ${doc.license ? `<p class="credential-sub">License No. ${doc.license}</p>` : ''}
          </div>
        </div>
      </div>
    </div>
    </div>`;

  setupDoctorModalTabs();
  document.getElementById('doctorModal').classList.add('active');
  lockBodyScroll();
}

function setupDoctorModalTabs() {
  const tabbar = document.getElementById('doctorModalTabbar');
  if (!tabbar) return;
  const tabs = tabbar.querySelectorAll('.doctor-modal-tab');
  const panels = document.querySelectorAll('#modalContent .doctor-modal-tabpanel');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const target = tab.dataset.tab;
      panels.forEach(p => p.classList.toggle('active', p.dataset.panel === target));
      const scrollArea = document.querySelector('#modalContent .doctor-modal-scrollarea');
      if (scrollArea) scrollArea.scrollTop = 0;
    });
  });
}

function closeDoctorModal() {
  document.getElementById('doctorModal').classList.remove('active');
  unlockBodyScroll();
}

// Decorative department labels shown as a tag in the service modal header.
// Purely presentational — not stored in the database.
const serviceDepartmentLabels = {
  pediatrics: 'Department of Pediatric Care',
  obgyne: "Department of Women's Health",
  surgery: 'Department of Surgery',
  pulmonology: 'Department of Pulmonology',
  ophthalmology: 'Department of Eye & ENT Care',
  cardiology: 'Department of Cardiology',
  adultmedicine: 'Department of Adult Medicine',
  oncology: 'Department of Oncology'
};

function openServiceModal(serviceKey) {
  const service = servicesData[serviceKey];
  if (!service) return;

  const deptLabel = serviceDepartmentLabels[serviceKey] || `Department of ${service.title}`;
  const phone = (clinicInfo && clinicInfo.contact_no) ? clinicInfo.contact_no : '0985 475 5511';

  document.getElementById('serviceModalBody').innerHTML = `
    <div class="service-modal-header">
      <button class="service-modal-close" onclick="closeServiceModal()"><i class="fas fa-times"></i></button>
      <div class="service-modal-header-top">
        <div class="service-modal-icon"><i class="fas ${service.icon}"></i></div>
        <div class="service-modal-tags">
          <span class="service-modal-tag dept">${deptLabel}</span>
          <span class="service-modal-tag accredited">DOH Accredited</span>
        </div>
      </div>
      <h2>${service.title}</h2>
    </div>
    <div class="service-modal-body">
      <div class="service-modal-grid">
        <div class="service-modal-col">
          <div class="service-modal-box">
            <div class="service-modal-box-label"><i class="fas fa-circle-info"></i> Clinical Scope &amp; Overview</div>
            <p class="service-modal-desc">${service.description}</p>
          </div>
          <div class="service-modal-box">
            <div class="service-modal-box-label"><i class="fas fa-list-check"></i> Common Conditions &amp; Procedures</div>
            <ul class="service-modal-list">
              ${service.conditions.map(c => `<li>${c}</li>`).join('')}
            </ul>
          </div>
        </div>
        <div class="service-modal-col">
          <div class="service-modal-box dark">
            <div class="service-modal-box-label"><i class="fas fa-clock"></i> Schedule &amp; Location</div>
            <div class="service-modal-schedule-row">
              <div class="sub-label"><i class="fas fa-map-marker-alt"></i> Consultation Room</div>
              <div class="sub-value">${service.room}, PolyClinic Lipa</div>
            </div>
            <div class="service-modal-schedule-row">
              <div class="sub-label"><i class="fas fa-calendar-week"></i> Clinic Schedule</div>
              <div class="sub-value">${service.schedule}</div>
            </div>
          </div>
          <div class="service-modal-box">
            <div class="service-modal-box-label"><i class="fas fa-user-doctor"></i> Attending Specialists</div>
            <div class="service-modal-doctors">
              ${service.doctors.map(d => `
                <div class="service-modal-doctor-tag">
                  <div class="service-modal-doctor-avatar"><i class="fas fa-user-doctor"></i></div>
                  <div class="service-modal-doctor-info">
                    <span class="service-modal-doctor-name">${d}</span>
                    <span class="service-modal-doctor-role">${service.title} Specialist</span>
                  </div>
                </div>`).join('')}
            </div>
          </div>
        </div>
      </div>
    </div>`;
  document.getElementById('serviceModal').classList.add('active');
  lockBodyScroll();
}

// ============ BODY SCROLL LOCK (modal open) ============
// Plain `overflow:hidden` on body isn't enough on mobile: the page can still
// rubber-band / the browser's address bar can collapse while you scroll or
// switch tabs inside the modal, which resizes the viewport and makes the
// fixed-position modal look like it "jumped" or got cut off at the top.
// Locking body to position:fixed at the current scroll offset prevents that.
let _scrollLockY = 0;
let _scrollLockCount = 0;
function lockBodyScroll() {
  if (_scrollLockCount === 0) {
    _scrollLockY = window.scrollY || document.documentElement.scrollTop || 0;
    document.body.style.position = 'fixed';
    document.body.style.top = `-${_scrollLockY}px`;
    document.body.style.left = '0';
    document.body.style.right = '0';
    document.body.style.width = '100%';
    document.body.style.overflow = 'hidden';
  }
  _scrollLockCount++;
}
function unlockBodyScroll() {
  _scrollLockCount = Math.max(0, _scrollLockCount - 1);
  if (_scrollLockCount === 0) {
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.left = '';
    document.body.style.right = '';
    document.body.style.width = '';
    document.body.style.overflow = '';
    window.scrollTo(0, _scrollLockY);
  }
}

function closeServiceModal() {
  document.getElementById('serviceModal').classList.remove('active');
  unlockBodyScroll();
}

// ============ CLOSE MODALS ============
document.addEventListener('click', function(event) {
  const modal = document.getElementById('serviceModal');
  if (event.target === modal) closeServiceModal();
});
document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') closeServiceModal();
});

// New global helper — called from onerror instead of building HTML inline
function handleDoctorPhotoError(imgEl, gender) {
  const fallback = document.createElement('div');
  fallback.className = 'doctor-photo-fallback';
  fallback.innerHTML = genderIcon(gender);
  imgEl.replaceWith(fallback);
}
function orNA(v) {
  return v && v !== 'null' ? v : 'Not specified';
}
// ============ GLOBAL FUNCTIONS ============
window.showDoctorDetails    = showDoctorDetails;
window.closeDoctorModal     = closeDoctorModal;
window.openServiceModal     = openServiceModal;
window.closeServiceModal    = closeServiceModal;
window.loadServicesData     = loadServicesData;
window.loadDoctorsData      = loadDoctorsData;
window.handleDoctorPhotoError = handleDoctorPhotoError;

// ============ AUTO-LOAD ON PAGE READY (THE FIX) ============
// Without this, loadDoctorsData()/loadServicesData()/loadClinicInfo() are defined
// but never called anywhere, so #doctorsGrid, #doctorsGridHome, #servicesGrid,
// #serviceCardsHome, and clinic-info fields all stay empty on page load.
document.addEventListener('DOMContentLoaded', function () {
  loadDoctorsData();
  loadServicesData();
  loadClinicInfo();
});