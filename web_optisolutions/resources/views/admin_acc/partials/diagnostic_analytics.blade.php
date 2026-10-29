<dialog id="diagnostic-modal" class="diagnostic-modal" aria-labelledby="diagnostic-title" aria-describedby="diagnostic-description">
<section class="chart-card diagnostic-panel">
    <div class="diagnostic-modal-header">
        <h3 id="diagnostic-title">Diagnostic Analytics</h3>
        <button type="button" id="diagnostic-close" class="diagnostic-close" aria-label="Close diagnostic analytics" autofocus>&times;</button>
    </div>
    <p class="chart-subtitle" id="diagnostic-description">Compare reported concerns in negative written reviews across two completed weeks, Monday–Sunday (Philippine time).</p>
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
    <p id="diagnostic-status" role="status" hidden></p>
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
        .diagnostic-filters { display:flex; flex-wrap:wrap; align-items:end; gap:1rem; margin:1rem 0; }
        .diagnostic-filters label { display:flex; flex-direction:column; gap:.4rem; font-size:.85rem; max-width:100%; }
        .diagnostic-filters input,.diagnostic-filters select,.diagnostic-filters button { padding:.6rem; border:1px solid #b7c9d8; border-radius:6px; max-width:100%; background:white; }
        .diagnostic-filters button { background:#0e62aa; color:white; cursor:pointer; }
        .diagnostic-filters button:disabled { opacity:.65; cursor:wait; }
        .diagnostic-scroll { overflow-x:auto; }
        .diagnostic-table { width:100%; border-collapse:collapse; font-size:.88rem; }
        .diagnostic-table caption { text-align:left; padding:.6rem 0; }
        .diagnostic-table th,.diagnostic-table td { text-align:left; padding:.65rem; border-bottom:1px solid #dce5ed; }
        .diagnostic-finding { padding:1rem 0; border-top:1px solid #dce5ed; }
        .diagnostic-finding summary { color:#0e62aa; cursor:pointer; padding:.4rem 0; }
        .diagnostic-comment { background:#f5f8fc; padding:.8rem; border-radius:6px; margin:.8rem 0; }
        .diagnostic-comment blockquote { white-space:pre-wrap; margin:.5rem 0; }
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
    const doctors = @json($diagnostic['doctors']);
    const specialties = @json($diagnostic['specialties']);
    let previousOverflow;
    let returnFocus;
    let comparisonController;

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
        status.hidden = false;
        status.textContent = 'Updating comparison…';
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
            status.textContent = 'Comparison updated.';
        } catch (error) {
            if (!controller.signal.aborted) {
                errors.textContent = error instanceof SyntaxError
                    ? 'Could not update the comparison. Refresh the page and try again.' : error.message;
                status.hidden = true;
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
        status.hidden = true;
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

