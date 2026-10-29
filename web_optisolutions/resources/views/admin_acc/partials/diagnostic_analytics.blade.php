<dialog id="diagnostic-modal" class="diagnostic-modal" aria-labelledby="diagnostic-title" aria-describedby="diagnostic-description">
<section class="chart-card diagnostic-panel">
    <div class="diagnostic-modal-header">
        <h3 id="diagnostic-title">Diagnostic Analytics</h3>
        <button type="button" id="diagnostic-close" class="diagnostic-close" aria-label="Close diagnostic analytics" autofocus>&times;</button>
    </div>
    <aside id="diagnostic-banner" class="diagnostic-banner" aria-label="About weekly comparisons">
        <p id="diagnostic-description">Compare problems mentioned in negative comments across two completed weeks, Monday–Sunday (Philippine time). “This week” means the selected week; “last week” means the week before it.</p>
        <button type="button" id="diagnostic-banner-dismiss" class="diagnostic-banner-dismiss" aria-label="Dismiss comparison explanation">&times;</button>
        <button type="button" id="diagnostic-banner-never" class="diagnostic-banner-never">Don’t show again</button>
    </aside>
    <form method="GET" action="{{ route('admin.dashboard') }}#diagnostic-title" data-compare-url="{{ route('admin.dashboard.diagnostics') }}" class="diagnostic-filters" id="diagnostic-form">
        <label>Week starting (Monday)
            <input type="date" name="diagnostic_week" value="{{ old('diagnostic_week', $diagnostic['week']) }}" min="1970-01-05" step="7" max="{{ $diagnostic['latest_week'] }}" required>
        </label>
        <label>Doctor
            <select name="diagnostic_doctor" id="diagnostic-doctor">
                <option value="">All doctors</option>
                @foreach($diagnostic['doctors'] as $doctor)
                    <option value="{{ $doctor->doctor_id }}" @selected((string)old('diagnostic_doctor', $diagnostic['doctor']) === (string)$doctor->doctor_id)>{{ $doctor->doctor_name }}</option>
                @endforeach
            </select>
        </label>
        <label>Specialty
            <select name="diagnostic_specialty" id="diagnostic-specialty">
                <option value="">All specialties</option>
                @foreach($diagnostic['specialties'] as $specialty)
                    <option value="{{ $specialty }}" @selected(old('diagnostic_specialty', $diagnostic['specialty']) === $specialty)>{{ $specialty }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" id="diagnostic-compare">Compare</button>
    </form>
    <div id="diagnostic-errors" role="alert">
    @foreach(['diagnostic_week', 'diagnostic_doctor', 'diagnostic_specialty'] as $field)
        @error($field)<p role="alert">{{ $message }}</p>@enderror
    @endforeach
    </div>
    <p id="diagnostic-status" class="diagnostic-toast" role="status" aria-atomic="true" hidden></p>
    <div id="diagnostic-results" aria-live="polite" aria-busy="false">
        @include('admin_acc.partials.diagnostic_results')
    </div>
    <style>
        .diagnostic-modal { width:min(1100px, calc(100% - 2rem)); max-height:calc(100vh - 2rem); max-height:calc(100dvh - 2rem); margin:auto; padding:0; border:0; border-radius:15px; background:#fff; color:#062744; overflow-y:auto; overscroll-behavior:contain; box-shadow:0 20px 70px #06274455; }
        .diagnostic-modal::backdrop { background:rgba(6,39,68,.55); backdrop-filter:blur(3px); }
        .diagnostic-modal .diagnostic-panel { margin:0; padding:0 1.5rem 1.5rem; box-shadow:none; }
        .diagnostic-modal-header { position:sticky; top:0; z-index:1; display:flex; align-items:center; justify-content:space-between; gap:1rem; margin:0 -1.5rem; padding:1rem 1.5rem; background:#fff; border-bottom:1px solid #dce5ed; }
        .diagnostic-close { flex-shrink:0; display:grid; place-items:center; width:40px; height:40px; border:0; border-radius:50%; background:#eef3f8; color:#062744; font-size:1.7rem; cursor:pointer; }
        .diagnostic-close:hover { background:#dce8f3; }
        .diagnostic-close:focus-visible { outline:3px solid #0e62aa; outline-offset:2px; }
        @media(max-width:600px) {
            .diagnostic-modal { width:calc(100% - 1rem); max-height:calc(100vh - 1rem); max-height:calc(100dvh - 1rem); }
            .diagnostic-modal .diagnostic-panel { padding:0 1rem 1rem; }
            .diagnostic-modal-header { margin:0 -1rem; padding:.75rem 1rem; }
        }
        .diagnostic-panel { overflow-wrap:anywhere; }
        .diagnostic-panel p { font-size:.88rem; line-height:1.6; margin:.8rem 0; }
        .diagnostic-banner { position:relative; display:flex; flex-direction:column; align-items:flex-start; gap:.25rem; margin:.35rem 0 1rem; padding:1rem; border:1px solid #cbdff1; border-radius:10px; background:#eef5fc; color:#244b6b; }
        .diagnostic-banner[hidden] { display:none; }
        .diagnostic-banner p { margin:0; padding-right:2rem; }
        .diagnostic-banner-dismiss { position:absolute; top:.5rem; right:.5rem; width:32px; height:32px; border:0; border-radius:50%; background:transparent; color:#244b6b; font-size:1.5rem; cursor:pointer; }
        .diagnostic-banner-dismiss:hover { background:#dce8f3; }
        .diagnostic-banner-never { align-self:flex-end; border:1px solid #0e62aa; border-radius:6px; padding:.45rem .8rem; background:#0e62aa; color:#fff; font:inherit; font-size:.85rem; font-weight:600; text-decoration:none; cursor:pointer; }
        .diagnostic-banner-never:hover { background:#094f8b; border-color:#094f8b; }
        .diagnostic-banner button:focus-visible { outline:2px solid #0e62aa; outline-offset:3px; }
        .diagnostic-filters { display:flex; flex-wrap:wrap; align-items:end; gap:1rem; margin:1rem 0; }
        .diagnostic-filters label { display:flex; flex-direction:column; gap:.4rem; font-size:.85rem; flex:0 1 240px; min-width:0; max-width:100%; }
        .diagnostic-filters label:first-child { flex-basis:160px; }
        .diagnostic-filters input,.diagnostic-filters select,.diagnostic-filters button { box-sizing:border-box; height:46px; padding:.6rem; border:1px solid #b7c9d8; border-radius:6px; max-width:100%; background:white; }
        .diagnostic-filters input,.diagnostic-filters select { width:100%; min-width:0; }
        .diagnostic-filters select { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .diagnostic-filters button { flex:0 0 90px; width:90px; background:#0e62aa; color:white; cursor:pointer; }
        @media(max-width:600px) {
            .diagnostic-filters label,.diagnostic-filters label:first-child { flex:0 0 100%; }
        }
        .diagnostic-filters button:disabled { opacity:.65; cursor:wait; }
        .diagnostic-scroll { overflow-x:auto; }
        .diagnostic-table { width:100%; border-collapse:collapse; font-size:.88rem; }
        .diagnostic-table caption { text-align:left; padding:.6rem 0; }
        .diagnostic-table th,.diagnostic-table td { text-align:left; padding:.65rem; border-bottom:1px solid #dce5ed; }
        .diagnostic-finding { padding:1rem 0; border-top:1px solid #dce5ed; }
        .diagnostic-overview { padding:1rem; border-radius:8px; background:#eef5fc; }
        .diagnostic-finding .diagnostic-trend { margin:.3rem 0; font-weight:600; }
        .diagnostic-panel .diagnostic-toast { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); z-index:10; display:flex; align-items:center; gap:.75rem; width:max-content; max-width:calc(100vw - 3rem); box-sizing:border-box; margin:0; padding:1rem 1.75rem; border-radius:14px; background:#0e62aa; color:#fff; box-shadow:0 8px 30px rgba(0,0,0,.2); font-size:1rem; font-weight:600; pointer-events:none; }
        .diagnostic-toast[hidden] { display:none; }
        .diagnostic-toast::before { content:'✓'; font-size:1.2rem; }
        .diagnostic-toast[data-state="loading"]::before { content:''; width:1rem; height:1rem; flex-shrink:0; border:2px solid #ffffff66; border-top-color:#fff; border-radius:50%; animation:diagnostic-spin .8s linear infinite; }
        @keyframes diagnostic-spin { to { transform:rotate(360deg); } }
        @media(prefers-reduced-motion:reduce) { .diagnostic-toast[data-state="loading"]::before { animation:none; } }
        .diagnostic-finding summary { color:#0e62aa; cursor:pointer; padding:.4rem 0; }
        .diagnostic-comment { background:#f5f8fc; padding:.8rem; border-radius:6px; margin:.8rem 0; }
        .diagnostic-comment blockquote { white-space:pre-wrap; margin:.5rem 0; }
        #diagnostic-results { color:#334155; }
        .diagnostic-modal h3,.diagnostic-finding h4,.diagnostic-table th { color:#062744; }
        .diagnostic-table caption,.diagnostic-table tbody td:nth-child(2),#diagnostic-results .diagnostic-note,#diagnostic-results .diagnostic-comment-meta { color:#64748b; }
        .diagnostic-table tbody td:nth-child(3) { color:#062744; font-weight:700; }
        #diagnostic-results .diagnostic-increase { color:#b42318; }
        #diagnostic-results .diagnostic-decrease { color:#166534; }
        #diagnostic-results .diagnostic-unchanged { color:#64748b; }
        #diagnostic-results a,.diagnostic-finding summary { color:#0e62aa; }
        .diagnostic-comment .diagnostic-evidence { color:#6d28d9; font-style:italic; }
    </style>
</section>
</dialog>
<script>
(() => {
    const modal = document.getElementById('diagnostic-modal');
    const opener = document.getElementById('diagnostic-open');
    const closer = document.getElementById('diagnostic-close');
    const form = document.getElementById('diagnostic-form');
    const doctorSelect = document.getElementById('diagnostic-doctor');
    const specialtySelect = document.getElementById('diagnostic-specialty');
    const compareButton = document.getElementById('diagnostic-compare');
    const results = document.getElementById('diagnostic-results');
    const errors = document.getElementById('diagnostic-errors');
    const status = document.getElementById('diagnostic-status');
    const banner = document.getElementById('diagnostic-banner');
    const bannerPreference = 'diagnostic-comparison-explanation-hidden';
    function dismissBanner() {
        banner.hidden = true;
        modal.setAttribute('aria-describedby', '');
    }
    try {
        if (window.localStorage.getItem(bannerPreference) === '1') dismissBanner();
    } catch (error) { /* Keep the banner usable when browser storage is unavailable. */ }
    document.getElementById('diagnostic-banner-dismiss').addEventListener('click', () => {
        dismissBanner();
        form.elements[0].focus({ preventScroll: true });
    });
    document.getElementById('diagnostic-banner-never').addEventListener('click', () => {
        try { window.localStorage.setItem(bannerPreference, '1'); } catch (error) { /* Dismiss for this page even if storage is blocked. */ }
        dismissBanner();
        form.elements[0].focus({ preventScroll: true });
    });
    const doctors = @json($diagnostic['doctors']);
    const specialties = @json($diagnostic['specialties']);
    let previousOverflow;
    let returnFocus;
    let comparisonController;
    let statusTimer;

    function hideStatus() {
        clearTimeout(statusTimer);
        status.hidden = true;
    }

    function showStatus(message, state) {
        clearTimeout(statusTimer);
        status.setAttribute('data-state', state);
        status.textContent = message;
        status.hidden = false;
        if (state === 'success') statusTimer = setTimeout(hideStatus, 3000);
    }

    function renderDoctors() {
        const selected = doctorSelect.value;
        const allowed = doctors.filter(doctor => !specialtySelect.value || doctor.specialty === specialtySelect.value);
        doctorSelect.replaceChildren(new Option('All doctors', ''),
            ...allowed.map(doctor => new Option(doctor.doctor_name, String(doctor.doctor_id))));
        doctorSelect.value = allowed.some(doctor => String(doctor.doctor_id) === selected) ? selected : '';
    }

    function renderSpecialties(selected = specialtySelect.value) {
        const doctor = doctors.find(doctor => String(doctor.doctor_id) === doctorSelect.value);
        const allowed = doctor ? (doctor.specialty ? [doctor.specialty] : []) : specialties;
        specialtySelect.replaceChildren(new Option('All specialties', ''),
            ...allowed.map(specialty => new Option(specialty, specialty)));
        specialtySelect.value = allowed.includes(selected) ? selected : '';
    }

    doctorSelect.addEventListener('change', () => {
        const doctor = doctors.find(doctor => String(doctor.doctor_id) === doctorSelect.value);
        renderSpecialties(doctor?.specialty || specialtySelect.value);
        renderDoctors();
    });
    specialtySelect.addEventListener('change', () => {
        const selected = specialtySelect.value;
        const doctor = doctors.find(doctor => String(doctor.doctor_id) === doctorSelect.value);
        if (doctor && (!selected || doctor.specialty !== selected)) doctorSelect.value = '';
        renderDoctors();
        renderSpecialties(selected);
    });
    const initialDoctor = doctors.find(doctor => String(doctor.doctor_id) === doctorSelect.value);
    renderSpecialties(initialDoctor?.specialty || specialtySelect.value);
    renderDoctors();

    function setLoading(loading) {
        for (const control of form.elements) control.disabled = loading;
        compareButton.textContent = loading ? 'Comparing…' : 'Compare';
        results.setAttribute('aria-busy', String(loading));
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (comparisonController || !form.reportValidity()) return;
        const parameters = new URLSearchParams(new FormData(form));
        const url = new URL(form.dataset.compareUrl, window.location.href);
        url.search = parameters.toString();
        const controller = new AbortController();
        const focusedControl = document.activeElement;
        comparisonController = controller;
        errors.textContent = '';
        showStatus('Updating comparison…', 'loading');
        setLoading(true);
        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
            });
            const payload = await response.json();
            if (!response.ok) {
                const messages = response.status === 422 ? Object.values(payload.errors || {}).flat().join(' ') : '';
                throw new Error(messages || (response.status === 401
                    ? 'Your session expired. Refresh the page and sign in again.'
                    : 'Could not update the comparison. Please try again.'));
            }
            if (typeof payload.html !== 'string') throw new Error('The comparison response was incomplete. Please try again.');
            if (controller.signal.aborted || !modal.open) return;
            // Replace only the results; keep the dialog, controls, and backdrop mounted.
            const scrollPosition = modal.scrollTop;
            results.innerHTML = payload.html;
            modal.scrollTop = scrollPosition;
            const pageUrl = new URL(window.location.href);
            pageUrl.search = parameters.toString();
            pageUrl.hash = 'diagnostic-title';
            history.replaceState(history.state, '', pageUrl);
            showStatus('Comparison updated.', 'success');
        } catch (error) {
            if (!controller.signal.aborted) {
                errors.textContent = error instanceof SyntaxError
                    ? 'Could not update the comparison. Refresh the page and try again.' : error.message;
                hideStatus();
            }
        } finally {
            if (comparisonController === controller) {
                comparisonController = null;
                setLoading(false);
                if (modal.open && document.activeElement === document.body) {
                    (form.contains(focusedControl) ? focusedControl : compareButton).focus({ preventScroll: true });
                }
            }
        }
    });

    function openDiagnostic() {
        if (modal.open) return;
        returnFocus = document.activeElement;
        previousOverflow = document.body.style.overflow;
        modal.showModal();
        modal.scrollTop = 0;
        closer.focus({ preventScroll: true });
        document.body.style.overflow = 'hidden';
        if (window.location.hash !== '#diagnostic-title') {
            history.replaceState(null, '', window.location.pathname + window.location.search + '#diagnostic-title');
        }
    }

    opener?.addEventListener('click', event => {
        event.preventDefault();
        openDiagnostic();
    });
    closer.addEventListener('click', () => modal.close());
    modal.addEventListener('click', event => {
        const rect = modal.getBoundingClientRect();
        if (event.target === modal && (event.clientX < rect.left || event.clientX > rect.right
            || event.clientY < rect.top || event.clientY > rect.bottom)) modal.close();
    });
    modal.addEventListener('close', () => {
        comparisonController?.abort();
        hideStatus();
        document.body.style.overflow = previousOverflow ?? '';
        if (window.location.hash === '#diagnostic-title') {
            history.replaceState(null, '', window.location.pathname + window.location.search);
        }
        (returnFocus === document.body ? opener : returnFocus)?.focus({ preventScroll: true });
    });
    window.addEventListener('hashchange', () => {
        if (window.location.hash === '#diagnostic-title') openDiagnostic();
        else if (modal.open) modal.close();
    });
    // Preserve direct links and the non-JavaScript form fallback.
    const hasDiagnosticErrors = @json($errors->hasAny(['diagnostic_week', 'diagnostic_doctor', 'diagnostic_specialty']));
    if (window.location.hash === '#diagnostic-title' || hasDiagnosticErrors) {
        if (document.readyState === 'complete') openDiagnostic();
        else window.addEventListener('load', openDiagnostic, { once: true });
    }
})();
</script>

