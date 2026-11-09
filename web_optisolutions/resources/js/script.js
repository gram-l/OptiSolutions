// ============ DOCTORS / SERVICES / CLINIC INFO DATA LOADING (UNCHANGED FROM ORIGINAL) ============

let doctorsData = [];
let servicesData = Object.create(null);   // keyed by service_key
let clinicData = {};                      // Admin > System Settings (clinic name, contact no, ...)

function genderIcon(gender) {
  return gender === 'female'
    ? '<i class="fas fa-user-nurse"></i>'
    : '<i class="fas fa-user-doctor"></i>';
} //changed to icons (mika)

function doctorPhotoUrl(doc) {
  if (!doc.profile_image) return null;
  const image = String(doc.profile_image);
  if (/^[a-z][a-z0-9+.-]*:/i.test(image) && !/^https?:\/\//i.test(image)) return null;
  return safeImageUrl(/^https?:\/\//i.test(image) ? image : `/storage/${image}`);
}

function safeImageUrl(value) {
  if (!value) return null;
  try {
    const url = new URL(String(value), window.location.href);
    return ['http:', 'https:'].includes(url.protocol) ? url.href : null;
  } catch {
    return null;
  }
}

// Escape database and search text at the HTML boundary, including attributes.
function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[char]);
}

function doctorPhotoMarkup(doc) {
  const photo = safeImageUrl(doc.photo);
  return photo
    ? `<img src="${escapeHtml(photo)}" alt="${escapeHtml(doc.name)}" loading="lazy" data-doctor-photo data-gender="${escapeHtml(doc.gender)}">`
    : `<div class="doctor-photo-fallback">${genderIcon(doc.gender)}</div>`;
}

function bindDoctorPhotoErrors(container) {
  container.querySelectorAll('[data-doctor-photo]').forEach(img => {
    img.addEventListener('error', () => handleDoctorPhotoError(img, img.dataset.gender), { once: true });
    if (img.complete && img.naturalWidth === 0) handleDoctorPhotoError(img, img.dataset.gender);
  });
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
let _doctorsSig = '';        // to detect whether the list has changed
const DAY_ORDER = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
let _openDoctorId = null;    // doctor currently shown in the modal (kept in sync with the admin)
let _doctorFiltersBound = false;

// The filter tabs mirror the Services list of the admin: one separate tab per available
// service, in the same order as the service cards, with the admin's exact title.
// Services that no doctor is linked to yet still get a tab. Doctors whose service is not
// loaded yet (first paint) fall back to their own service list.
function rebuildFilterTabs(containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;

  const current = (container.querySelector('.filter-tab.active') || { dataset: { filter: 'all' } }).dataset.filter;

  const seen = new Map();
  Object.keys(servicesData).forEach(key => seen.set(key, servicesData[key].title));
  if (seen.size === 0) {
    doctorsData.forEach(d => d.services.forEach(sv => seen.set(sv.service_key, sv.title)));
  }
  const entries = [...seen.entries()];

  const active = current !== 'all' && seen.has(current) ? current : 'all';
  container.innerHTML =
    `<button class="filter-tab ${active === 'all' ? 'active' : ''}" data-filter="all">All</button>` +
    entries.map(([key, title]) =>
      `<button class="filter-tab ${key === active ? 'active' : ''}" data-filter="${escapeHtml(key)}">${escapeHtml(title)}</button>`
    ).join('');
}

function rerenderDoctorGrids() {
  rebuildFilterTabs('filterTabs');
  rebuildFilterTabs('filterTabsHome');

  // Restore the current filter tab and search so the user doesn't lose their selection
  [
    ['doctorsGrid', 'filterTabs', null],
    ['doctorsGridHome', 'filterTabsHome', 'doctorsSearchHome']
  ].forEach(([gridId, tabsId, searchId]) => {
    const tabs = document.getElementById(tabsId);
    const active = tabs ? tabs.querySelector('.filter-tab.active') : null;
    const filter = active ? active.dataset.filter : 'all';
    const input = searchId ? document.getElementById(searchId) : null;
    renderDoctors(filter, gridId, input ? input.value : '');
  });

  // If a doctor's details are open, refresh them too (schedule, room, etc.).
  const modal = document.getElementById('doctorModal');
  if (_openDoctorId !== null && modal && modal.classList.contains('active')) {
    showDoctorDetails(_openDoctorId, true);
  }
}

async function loadDoctorsData() {
  try {
    const res = await fetch('/api/doctors', { cache: 'no-store' });
    const raw = await res.json();

    // Nothing changed? Don't re-render.
    const sig = JSON.stringify(raw);
    if (sig === _doctorsSig) return;
    _doctorsSig = sig;

    doctorsData = raw.map(doc => ({
      id: doc.doctor_id,
      name: doc.doctor_name,
      spec: doc.specialty,
      services: doc.services || [],   // [{service_key, title}] from doctor_service
      icon: genderIcon(doc.gender),
      gender: doc.gender, // added this line to pass gender to handleDoctorPhotoError
      photo: doctorPhotoUrl(doc), //added this line to display the doctor photo 
      yearsExp: doc.years_experience,
      education: doc.education,
      license: doc.license,
      // Everything the admin saved under Manage Doctors > Schedule, Monday to Sunday.
      schedule: (doc.schedules || [])
        .slice()
        .sort((a, b) => (DAY_ORDER.indexOf(a.day) - DAY_ORDER.indexOf(b.day)) || String(a.start_time).localeCompare(String(b.start_time)))
        .map(s => ({
          day: s.day,
          time: `${formatTime(s.start_time)} - ${formatTime(s.end_time)}`
        })),
      bio: doc.description,
      clinic: doc.clinic_room,
      fellowship: doc.fellowship,
      available: doc.available !== 0
    })).filter(d => d.available);

    rerenderDoctorGrids();

    // Bind the click/search listeners only once
    if (!_doctorFiltersBound) {
      setupFilterTabs('filterTabs', 'doctorsGrid');
      setupFilterTabs('filterTabsHome', 'doctorsGridHome', 'doctorsSearchHome');
      setupDoctorSearch('doctorsSearchHome', 'filterTabsHome', 'doctorsGridHome');
      _doctorFiltersBound = true;
    }
  } catch (err) {
    console.error('Failed to load doctors:', err);
  }
}

// ============ FETCH: CLINIC INFO (System Settings) ============
async function loadClinicData() {
  try {
    const res = await fetch('/api/clinic-info', { cache: 'no-store', headers: { Accept: 'application/json' } });
    if (!res.ok) return;
    const payload = await res.json();
    clinicData = (Array.isArray(payload) ? payload[0] : payload) || {};
  } catch (err) {
    // keep the last known details
  }
}

// ============ FETCH: SERVICES (services + service_conditions + service_doctors) ============
let _servicesSig = '';

async function loadServicesData() {
  try {
    const res = await fetch('/api/services', { cache: 'no-store' });
    const raw = await res.json();

    const sig = JSON.stringify(raw);
    if (sig === _servicesSig) return;
    _servicesSig = sig;

    servicesData = Object.create(null);
    raw.filter(svc => svc.available !== 0).forEach(svc => {
      servicesData[svc.service_key] = {
        id: svc.service_id,
        title: svc.title,
        icon: svc.icon,
        description: svc.description,
        conditions: svc.conditions || [],
        doctors: svc.doctors || [],
        room: svc.room,
        schedule: svc.schedule,
        cardImage: svc.card_image,
        cardColor: svc.card_color,
        cardIcon: svc.card_icon
      };
    });

    renderServiceCards('servicesGrid');       // for services.blade.php
    renderHomeServiceCards('serviceCardsHome'); // for home.blade.php (if present)
    rerenderDoctorGrids();                    // Medical Experts tabs follow the services
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
      <div class="service-page-card" data-service-key="${escapeHtml(key)}">
        <div class="service-page-icon"><i class="fas ${escapeHtml(svc.icon)}"></i></div>
        <h3>${escapeHtml(svc.title)}</h3>
        <p>${escapeHtml(svc.description)}</p>
        <span class="service-page-link">Learn More →</span>
      </div>`;
  }).join('');
  grid.querySelectorAll('.service-page-card').forEach(card => {
    card.addEventListener('click', () => openServiceModal(card.dataset.serviceKey));
  });
}

// Home page cards: same markup as the server-rendered ones in home.blade.php,
// so admin edits (title/description/availability) show up without a refresh.
function renderHomeServiceCards(containerId = 'serviceCardsHome') {
  const grid = document.getElementById(containerId);
  if (!grid) return;
  grid.innerHTML = Object.keys(servicesData).map(key => {
    const svc = servicesData[key];
    const img = svc.cardImage
      ? `<img src="${escapeHtml(svc.cardImage)}" alt="${escapeHtml(svc.title)}" loading="lazy">`
      : `<i class="fas ${escapeHtml(svc.cardIcon || svc.icon)}" aria-hidden="true"></i>`;
    return `
      <div class="service-card-modern" data-service-key="${escapeHtml(key)}">
        <div class="service-card-img${svc.cardImage ? '' : ' no-image'}">${img}</div>
        <div class="service-card-body">
          <div class="service-card-icon ${escapeHtml(svc.cardColor || 'icon-blue')}"><i class="fas ${escapeHtml(svc.cardIcon || svc.icon)}"></i></div>
          <div class="service-card-text">
            <h3>${escapeHtml(svc.title)}</h3>
            <p>${escapeHtml(svc.description || '')}</p>
            <button type="button" class="service-learn-more">Learn More <i class="fas fa-chevron-right"></i></button>
          </div>
        </div>
      </div>`;
  }).join('');
  grid.querySelectorAll('.service-card-modern').forEach(card => {
    card.addEventListener('click', () => openServiceModal(card.dataset.serviceKey));
  });
}

// ============ RENDER DOCTORS ============
function renderDoctors(filter = 'all', containerId = 'doctorsGrid', searchTerm = '') {
  const grid = document.getElementById(containerId);
  if (!grid) return;
  let filtered = filter === 'all' ? doctorsData : doctorsData.filter(doc => doc.services.some(sv => sv.service_key === filter));

  const term = searchTerm.trim().toLowerCase();
  if (term) {
    filtered = filtered.filter(doc =>
      doc.name.toLowerCase().includes(term) ||
      (doc.spec || '').toLowerCase().includes(term) ||
      doc.services.some(sv => sv.title.toLowerCase().includes(term))
    );
  }

  if (filtered.length === 0) {
    grid.innerHTML = `<div class="no-doctors">No doctors found${term ? ` matching "${escapeHtml(searchTerm)}"` : ' for this specialty'}.</div>`;
    return;
  }
grid.innerHTML = filtered.map(doc => `
  <div class="doctor-card">
    <div class="doctor-card-photo">
      ${doctorPhotoMarkup(doc)}
    </div>
    <h3>${escapeHtml(doc.name)}</h3>
    <span class="doctor-spec">${escapeHtml(doc.spec)}</span>
    <p>${escapeHtml(doc.yearsExp)} years of experience</p>
  </div>`).join('');
  grid.querySelectorAll('.doctor-card').forEach((card, index) => {
    card.addEventListener('click', () => showDoctorDetails(filtered[index].id));
  });
  bindDoctorPhotoErrors(grid);
}

function setupFilterTabs(containerId = 'filterTabs', gridId = 'doctorsGrid', searchInputId = null) {
  const tabsContainer = document.getElementById(containerId);
  if (!tabsContainer) return;
  // Event delegation: still works even when the tab buttons are rebuilt
  tabsContainer.addEventListener('click', function (e) {
    const tab = e.target.closest('.filter-tab');
    if (!tab || !tabsContainer.contains(tab)) return;
    tabsContainer.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    const searchInput = searchInputId ? document.getElementById(searchInputId) : null;
    renderDoctors(tab.dataset.filter, gridId, searchInput ? searchInput.value : '');
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

// The bio is the admin's Description, but the number of years always follows the doctor's
// "Years of Experience" field, so changing one in Manage Doctors never leaves the other stale.
//  - {years} in the description is replaced by the years of experience.
//  - "over 18 years of experience", "18+ years of practice", ... get the current number.
//  - No description at all: a short line is built from the doctor's own record.
function doctorBio(doc) {
  const years = parseInt(doc.yearsExp, 10);
  const hasYears = Number.isFinite(years) && years > 0;
  let text = String(doc.bio || '').trim();

  if (text === '' || text === 'null') {
    if (!hasYears) return '';
    return `${doc.name} has ${years} ${years === 1 ? 'year' : 'years'} of experience${doc.spec ? ' in ' + doc.spec : ''}.`;
  }

  if (hasYears) {
    const unit = years === 1 ? 'year' : 'years';
    text = text.replace(/\{years\}/gi, String(years));
    text = text.replace(
      /\b\d+(\+?)\s*(?:years?|yrs?)(\s+(?:of\s+)?(?:clinical\s+|medical\s+|professional\s+)?(?:experience|practice))/gi,
      (m, plus, tail) => `${years}${plus} ${unit}${tail}`
    );
  } else {
    text = text.replace(/\{years\}/gi, '');
  }
  return text;
}

// A doctor's "Clinical Focus Areas" = the conditions of their service(s), taken from
// servicesData. No hardcoded mapping; just follow doctor_service.
function getClinicalFocusAreas(doc) {
  const conditions = (doc.services || []).flatMap(sv => {
    const svc = servicesData[sv.service_key];
    return svc && Array.isArray(svc.conditions) ? svc.conditions : [];
  });
  return [...new Set(conditions)].slice(0, 6);
}

function showDoctorDetails(id, refresh = false) {
  const doc = doctorsData.find(d => d.id === id);
  if (!doc) {
    if (refresh) closeDoctorModal();   // the doctor was removed or deactivated in admin
    return;
  }
  const previousTab = refresh
    ? ((document.querySelector('#doctorModalTabbar .doctor-modal-tab.active') || {}).dataset || {}).tab
    : null;
  const previousScroll = refresh ? ((document.querySelector('#modalContent .doctor-modal-scrollarea') || {}).scrollTop || 0) : 0;
  _openDoctorId = id;

  const spec = doc.spec || '';
  // Everything below comes from the doctor's record or from System Settings.
  const clinicName = String(clinicData.clinic_name || '').trim();
  const clinicPhone = String(clinicData.contact_no || '').trim();
  const roomText = doc.clinic
    ? (/^room\b/i.test(String(doc.clinic).trim()) ? String(doc.clinic).trim() : 'Room ' + String(doc.clinic).trim())
    : '';

  const focusAreas = getClinicalFocusAreas(doc);
  // One line per day (several sessions on a day are joined), Monday to Sunday.
  const days = [];
  doc.schedule.forEach(s => {
    const last = days.find(d => d.day === s.day);
    if (last) last.times.push(s.time); else days.push({ day: s.day, times: [s.time] });
  });
  const overviewSchedule = days.map(d =>
    `<div class="sub-value-light"><i class="fas fa-clock"></i> ${escapeHtml(d.day)}: ${escapeHtml(d.times.join(', '))}</div>`
  ).join('');

  const subtitle = doc.fellowship || doc.education || spec;

  const scheduleRows = doc.schedule.length
    ? doc.schedule.map(s => `
        <div class="schedule-row-card">
          <span class="schedule-day-badge">${escapeHtml(s.day.slice(0, 3).toUpperCase())}</span>
          <div class="schedule-row-info">
            <strong>${escapeHtml(s.day)} Schedule</strong>
          </div>
          <span class="schedule-row-time">${escapeHtml(s.time)}</span>
        </div>`).join('')
    : `<p class="bio-text">No fixed clinic schedule listed yet.${clinicPhone ? ' Please call ' + escapeHtml(clinicPhone) + ' to confirm availability.' : ''}</p>`;

  document.getElementById('modalContent').innerHTML = `
    <div class="modal-header">
      <div class="doctor-modal-tags">
        ${spec ? `<span class="doctor-modal-tag">${escapeHtml(spec.toUpperCase())}</span>` : ''}
      </div>
      <h2>${escapeHtml(doc.name)}</h2>
    </div>

    <div class="doctor-modal-summary-card">
      <div class="doctor-modal-summary-photo">
        ${doctorPhotoMarkup(doc)}
      </div>
      <div class="doctor-modal-summary-info">
        <h3>${escapeHtml(doc.name)}</h3>
        <p>${escapeHtml(subtitle)}</p>
      </div>
      <div class="doctor-modal-summary-stats">
        <div class="summary-stat"><span class="summary-stat-value">${escapeHtml(doc.yearsExp ? doc.yearsExp + '+ Yrs' : '—')}</span><span class="summary-stat-label">Experience</span></div>
        <div class="summary-stat"><span class="summary-stat-value">${escapeHtml(doc.fellowship || '—')}</span><span class="summary-stat-label">Fellowship</span></div>
        <div class="summary-stat"><span class="summary-stat-value">${escapeHtml(doc.license || '—')}</span><span class="summary-stat-label">License No.</span></div>
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
        <p class="bio-text">${escapeHtml(orNA(doctorBio(doc)))}</p>
        <div class="doctor-modal-grid">
          <div class="doctor-modal-box">
            <div class="doctor-modal-box-label"><i class="fas fa-hospital"></i> Primary Consultation</div>
            ${clinicName ? `<div class="info-item-value" style="margin-bottom:6px;">${escapeHtml(clinicName)}</div>` : ''}
            ${roomText ? `<div class="sub-value-light"><i class="fas fa-door-open"></i> ${escapeHtml(roomText)}</div>` : ''}
            ${overviewSchedule}
          </div>
          <div class="doctor-modal-box">
            <div class="doctor-modal-box-label"><i class="fas fa-stethoscope"></i> Clinical Focus Areas</div>
            <div class="focus-tags">
              ${focusAreas.length
                ? focusAreas.map(c => `<span class="focus-tag">${escapeHtml(c)}</span>`).join('')
                : (spec ? `<span class="focus-tag">${escapeHtml(spec)}</span>` : '')}
            </div>
          </div>
        </div>
      </div>

      <div class="doctor-modal-tabpanel" data-panel="schedule">
        <div class="doctor-modal-box schedule-box">
          <div class="schedule-box-header">
            <div>
              <h4>Clinic Availability</h4>
              ${clinicName ? `<p>Consultation schedule at ${escapeHtml(clinicName)}</p>` : ''}
            </div>
            ${roomText ? `<span class="room-pill">${escapeHtml(roomText)}</span>` : ''}
          </div>
          ${scheduleRows}
          ${clinicPhone ? `<div class="schedule-note"><i class="fas fa-circle-info"></i> For urgent concerns, please call ${escapeHtml(clinicPhone)}.</div>` : ''}
        </div>
      </div>

      <div class="doctor-modal-tabpanel" data-panel="credentials">
        <div class="doctor-modal-grid">
          <div class="doctor-modal-box credential-box">
            <div class="credential-icon"><i class="fas fa-graduation-cap"></i></div>
            <h4>Medical Education</h4>
            <p class="credential-highlight">${escapeHtml(orNA(doc.education))}</p>
          </div>
          <div class="doctor-modal-box credential-box">
            <div class="credential-icon accent"><i class="fas fa-award"></i></div>
            <h4>Fellowship &amp; Certification</h4>
            <p class="credential-highlight">${escapeHtml(orNA(doc.fellowship))}</p>
            ${doc.license ? `<p class="credential-sub">License No. ${escapeHtml(doc.license)}</p>` : ''}
          </div>
        </div>
      </div>
    </div>
    </div>`;

  bindDoctorPhotoErrors(document.getElementById('modalContent'));
  setupDoctorModalTabs();
  if (refresh) {
    if (previousTab && previousTab !== 'overview') {
      const tab = document.querySelector(`#doctorModalTabbar .doctor-modal-tab[data-tab="${previousTab}"]`);
      if (tab) tab.click();
    }
    const area = document.querySelector('#modalContent .doctor-modal-scrollarea');
    if (area) area.scrollTop = previousScroll;
    return;
  }
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
  _openDoctorId = null;
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

  document.getElementById('serviceModalBody').innerHTML = `
    <div class="service-modal-header">
      <button class="service-modal-close" onclick="closeServiceModal()"><i class="fas fa-times"></i></button>
      <div class="service-modal-header-top">
        <div class="service-modal-icon"><i class="fas ${escapeHtml(service.icon)}"></i></div>
        <div class="service-modal-tags">
          <span class="service-modal-tag dept">${escapeHtml(deptLabel)}</span>
          <span class="service-modal-tag accredited">DOH Accredited</span>
        </div>
      </div>
      <h2>${escapeHtml(service.title)}</h2>
    </div>
    <div class="service-modal-body">
      <div class="service-modal-grid">
        <div class="service-modal-col">
          <div class="service-modal-box">
            <div class="service-modal-box-label"><i class="fas fa-circle-info"></i> Clinical Scope &amp; Overview</div>
            <p class="service-modal-desc">${escapeHtml(service.description || 'Please ask our staff for more details about this service.')}</p>
          </div>
          <div class="service-modal-box">
            <div class="service-modal-box-label"><i class="fas fa-list-check"></i> Common Conditions &amp; Procedures</div>
            <ul class="service-modal-list">
              ${service.conditions.map(c => `<li>${escapeHtml(c)}</li>`).join('')}
            </ul>
          </div>
        </div>
        <div class="service-modal-col">
          <div class="service-modal-box dark">
            <div class="service-modal-box-label"><i class="fas fa-clock"></i> Schedule &amp; Location</div>
            <div class="service-modal-schedule-row">
              <div class="sub-label"><i class="fas fa-map-marker-alt"></i> Consultation Room</div>
              <div class="sub-value">${escapeHtml([service.room, String(clinicData.clinic_name || '').trim()].filter(Boolean).join(', '))}</div>
            </div>
            <div class="service-modal-schedule-row">
              <div class="sub-label"><i class="fas fa-calendar-week"></i> Clinic Schedule</div>
              <div class="sub-value">${escapeHtml(service.schedule || 'Please ask our staff for the schedule.')}</div>
            </div>
          </div>
          <div class="service-modal-box">
            <div class="service-modal-box-label"><i class="fas fa-user-doctor"></i> Attending Specialists</div>
            <div class="service-modal-doctors">
              ${service.doctors.map(d => `
                <div class="service-modal-doctor-tag">
                  <div class="service-modal-doctor-avatar"><i class="fas fa-user-doctor"></i></div>
                  <div class="service-modal-doctor-info">
                    <span class="service-modal-doctor-name">${escapeHtml(d)}</span>
                    <span class="service-modal-doctor-role">${escapeHtml(service.title)} Specialist</span>
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
    // Force an instant jump back, not an animated one. Several pages set
    // `html { scroll-behavior: smooth }` for their own in-page anchor links,
    // but that same CSS rule also hijacks *any* programmatic scroll —
    // including this restore — turning what should be an invisible snap
    // back to where the user was into a visible "dramatic" scroll down/up
    // the page after closing a modal. Passing behavior: 'auto' explicitly
    // overrides the CSS smooth-scroll setting for this one call only.
    window.scrollTo({ top: _scrollLockY, left: 0, behavior: 'auto' });
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

// Image failures use event listeners so database values never become JavaScript.
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
// Without this, loadDoctorsData()/loadServicesData() are defined
// but never called anywhere, so #doctorsGrid, #doctorsGridHome, #servicesGrid,
// #serviceCardsHome, and clinic-info fields all stay empty on page load.
document.addEventListener('DOMContentLoaded', function () {
  loadDoctorsData();
  loadServicesData();
  loadClinicData();
  startLiveSync();
});

// ============ LIVE SYNC (no refresh needed) ============
// When a doctor or service is activated/deactivated in admin/staff, the
// Medical Team and Services sections update automatically within a few seconds.
const LIVE_SYNC_MS = 5000;

function startLiveSync() {
  const tick = () => {
    if (document.hidden) return;
    loadDoctorsData();
    loadServicesData();
    loadClinicData();
  };
  setInterval(tick, LIVE_SYNC_MS);
  // When returning to the tab (browsers throttle background tabs), check immediately
  document.addEventListener('visibilitychange', tick);
  window.addEventListener('focus', tick);
}