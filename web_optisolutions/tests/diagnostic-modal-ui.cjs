const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const doctors = [
    { doctor_id: 1, doctor_name: 'Doctor A', specialty: 'General Medicine' },
    { doctor_id: 2, doctor_name: 'Doctor B', specialty: 'Pediatrics' },
    { doctor_id: 3, doctor_name: 'Doctor C', specialty: 'Pediatrics' },
];
const raw = fs.readFileSync('resources/views/admin_acc/partials/diagnostic_analytics.blade.php', 'utf8');
const script = raw.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace("@json($diagnostic['doctors'])", JSON.stringify(doctors))
    .replace("@json($diagnostic['specialties'])", JSON.stringify(['General Medicine', 'Pediatrics']))
    .replace("@json($errors->hasAny(['diagnostic_week', 'diagnostic_doctor', 'diagnostic_specialty']))", 'false');
const nodes = new Map();
const document = { body: { style: { overflow: '' } }, activeElement: null, getElementById: id => nodes.get(id) };
function element(id, extra = {}) {
    const listeners = {};
    const node = { id, value: '', disabled: false, textContent: '', hidden: false, options: [],
        addEventListener: (name, fn) => (listeners[name] ||= []).push(fn),
        fire(name, event = {}) { let result; for (const fn of listeners[name] || []) result = fn(event); return result; },
        replaceChildren(...options) { this.options = options; },
        setAttribute(name, value) { this[name] = value; },
        focus() { document.activeElement = this; },
        ...extra,
    };
    nodes.set(id, node);
    return node;
}
const modal = element('diagnostic-modal', { open: false, scrollTop: 0,
    showModal() { this.open = true; }, close() { this.open = false; this.fire('close'); },
});
const opener = element('diagnostic-open');
element('diagnostic-close');
const doctor = element('diagnostic-doctor', { name: 'diagnostic_doctor' });
const specialty = element('diagnostic-specialty', { name: 'diagnostic_specialty' });
const week = element('diagnostic-week', { name: 'diagnostic_week', value: '2026-10-19' });
const button = element('diagnostic-compare');
const results = element('diagnostic-results', { innerHTML: '<p>Previous results</p>' });
const errors = element('diagnostic-errors');
const status = element('diagnostic-status', { hidden: true });
const banner = element('diagnostic-banner');
const bannerDismiss = element('diagnostic-banner-dismiss');
const bannerNever = element('diagnostic-banner-never');
const preferences = new Map();
const timers = new Map();
let timerId = 0;
const form = element('diagnostic-form', {
    elements: [week, doctor, specialty, button], dataset: { compareUrl: '/admin/dashboard/diagnostics' },
    reportValidity: () => true,
    contains(candidate) { return this.elements.includes(candidate); },
});
document.activeElement = opener;
const location = new URL('http://localhost/admin_acc/dashboard');
const requests = [];
let respond;
const context = {
    document, window: { location, addEventListener() {}, localStorage: {
        getItem: key => preferences.get(key) ?? null,
        setItem: (key, value) => preferences.set(key, value),
    } },
    history: { state: null, replaceState(state, title, url) { location.href = new URL(url, location).href; } },
    Option: class { constructor(text, value) { this.text = text; this.value = value; } },
    FormData: class extends Array { constructor(form) { super(...form.elements.filter(n => n.name && !n.disabled).map(n => [n.name, n.value])); } },
    URL, URLSearchParams, AbortController,
    setTimeout(callback, delay) { const id = ++timerId; timers.set(id, { callback, delay }); return id; },
    clearTimeout(id) { timers.delete(id); },
    fetch: (url, options) => new Promise((resolve, reject) => {
        requests.push({ url, options }); respond = resolve;
        options.signal.addEventListener('abort', () => reject(new Error('Aborted')));
    }),
};
vm.runInNewContext(script, context);
const submit = () => form.fire('submit', { preventDefault() {} });
const values = select => select.options.map(o => o.value);

(async () => {
    opener.fire('click', { preventDefault() {} });
    assert.equal(modal.open, true);
    specialty.value = 'Pediatrics'; specialty.fire('change');
    assert.deepEqual(values(doctor), ['', '2', '3']);
    doctor.value = '2'; doctor.fire('change');
    assert.deepEqual(values(specialty), ['', 'Pediatrics']);
    assert.equal(specialty.value, 'Pediatrics');
    specialty.value = ''; specialty.fire('change');
    assert.equal(doctor.value, '');
    assert.deepEqual(values(doctor), ['', '1', '2', '3']);
    doctor.value = '1'; doctor.fire('change');
    assert.equal(specialty.value, 'General Medicine');
    assert.deepEqual(values(specialty), ['', 'General Medicine']);

    document.activeElement = button;
    const pending = submit();
    document.activeElement = document.body; // Disabling a focused button releases its focus.
    assert.equal(modal.open, true, 'Overlay remains open while waiting');
    assert.equal(button.disabled, true);
    assert.equal(status.hidden, false);
    assert.equal(status['data-state'], 'loading');
    assert.equal(results.innerHTML, '<p>Previous results</p>');
    assert.equal(requests[0].url.searchParams.get('diagnostic_doctor'), '1');
    assert.equal(requests[0].url.searchParams.get('diagnostic_specialty'), 'General Medicine');
    respond({ ok: true, json: async () => ({ html: '<p>Updated results</p>' }) });
    await pending;
    assert.equal(modal.open, true, 'Success updates the results without closing the overlay');
    assert.equal(results.innerHTML, '<p>Updated results</p>');
    assert.equal(button.disabled, false);
    assert.equal(document.activeElement, button, 'Keyboard focus returns to the comparison control');
    assert.equal(location.hash, '#diagnostic-title');
    assert.equal(status.textContent, 'Comparison updated.');
    assert.equal(status['data-state'], 'success');
    assert.equal(timers.size, 1);
    const dismiss = [...timers.values()][0];
    assert.equal(dismiss.delay, 3000);
    dismiss.callback();
    assert.equal(status.hidden, true, 'Success toast automatically disappears');

    const invalid = submit();
    respond({ ok: false, status: 422, json: async () => ({ errors: { diagnostic_week: ['Select a Monday.'] } }) });
    await invalid;
    assert.equal(modal.open, true);
    assert.equal(results.innerHTML, '<p>Updated results</p>', 'An error preserves previous results');
    assert.equal(errors.textContent, 'Select a Monday.');
    assert.equal(status.hidden, true, 'Errors clear the loading toast');
    assert.equal(button.disabled, false);

    const cancelled = submit();
    modal.close();
    await cancelled;
    assert.equal(modal.open, false);
    assert.equal(status.hidden, true);
    assert.equal(timers.size, 0, 'Closing the modal clears toast timers');
    assert.equal(document.body.style.overflow, '');
    assert.equal(button.disabled, false);
    assert.equal(document.activeElement, opener);
    bannerDismiss.fire('click');
    assert.equal(banner.hidden, true);
    assert.equal(preferences.size, 0, 'Dismiss does not permanently hide the banner');
    bannerNever.fire('click');
    assert.equal(preferences.get('diagnostic-comparison-explanation-hidden'), '1');
    console.log('Dependent specialty filters, in-place comparison, error handling, and cancellation checks passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
