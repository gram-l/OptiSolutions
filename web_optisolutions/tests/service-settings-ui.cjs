const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const raw = fs.readFileSync('resources/views/admin_acc/system_settings.blade.php', 'utf8');
const script = raw.match(/<script>([\s\S]*?)<\/script>/)[1].split('    // FAQ modal + actions')[0];

function browser(response) {
    const nodes = new Map();
    function node(id) {
        const classes = new Set();
        const item = { id, value: '', style: {}, dataset: {}, disabled: false, listeners: {},
            classList: { add: name => classes.add(name), remove: name => classes.delete(name), contains: name => classes.has(name),
                toggle: (name, value) => value ? classes.add(name) : classes.delete(name) },
            addEventListener(type, fn) { this.listeners[type] = fn; }, focus() {}, reset() {},
        };
        nodes.set(id, item); return item;
    }
    for (const id of ['serviceModal', 'serviceForm', 'serviceAvailable', 'serviceTitle', 'serviceRoom', 'serviceDescription',
        'serviceMethod', 'serviceModalTitle', 'serviceFormError', 'servicesTableContainer', 'addServiceBtn', 'addFaqBtn',
        'serviceFocusAreas', 'serviceIcon', 'serviceColor', 'serviceCardImageCurrent', 'serviceCardImage',
        'serviceRemoveCardImage', 'serviceCardImagePreview']) node(id);
    const tabs = ['hours', 'about', 'contact', 'services'].map(tab => Object.assign(node('button-' + tab), { dataset: { tab } }));
    const panels = ['hours', 'about', 'contact', 'services'].map(tab => node('tab-' + tab));
    const button = node('saveButton');
    const location = { href: 'https://clinic.example/admin_acc/system_settings?tab=services' };
    const state = { requests: 0, toasts: [] };
    const context = {
        document: { getElementById: id => nodes.get(id), addEventListener() {},
            querySelectorAll: selector => selector === '.tab-btn' ? tabs : selector === '.tab-panel' ? panels : [button] },
        window: { location }, history: { replaceState: (a, b, url) => location.href = String(url) },
        URL, FormData: class {}, AbortSignal: { timeout: () => 'timeout' }, confirm: () => true,
        fetch: async () => { state.requests++; return response; },
    };
    vm.runInNewContext(script, context);
    context.ssShowToast = (...args) => state.toasts.push(args);
    return { nodes, context, location, state, button };
}

(async () => {
    const success = browser({ ok: true, json: async () => ({ html: '<table>ENT</table>', message: 'Service added.' }) });
    assert.ok(success.nodes.get('tab-services').classList.contains('active'));
    success.context.openServiceModal();
    assert.equal(success.nodes.get('serviceAvailable').checked, true);
    const pending = success.context.submitService(success.nodes.get('serviceForm'));
    assert.equal(success.button.disabled, true);
    await success.context.submitService(success.nodes.get('serviceForm'));
    assert.equal(success.state.requests, 1, 'Duplicate save clicks do not create another service');
    await pending;
    assert.equal(success.nodes.get('servicesTableContainer').innerHTML, '<table>ENT</table>');
    assert.ok(success.location.href.includes('tab=services'));
    assert.equal(success.nodes.get('serviceModal').classList.contains('open'), false);
    assert.equal(success.button.disabled, false);
    const invalid = browser({ ok: false, status: 422, json: async () => ({ errors: { title: ['Title already exists.'] } }) });
    invalid.context.openServiceModal({ title: 'ENT', available: false, service_id: 1 });
    assert.equal(invalid.nodes.get('serviceAvailable').checked, false);
    await invalid.context.submitService(invalid.nodes.get('serviceForm'));
    assert.equal(invalid.nodes.get('serviceTitle').value, 'ENT');
    assert.equal(invalid.nodes.get('serviceFormError').textContent, 'Title already exists.');
    assert.ok(invalid.nodes.get('serviceModal').classList.contains('open'), 'Validation errors keep the edit form open');
    const oversized = browser({ ok: false, status: 413, json: async () => { throw new SyntaxError('HTML error response'); } });
    oversized.context.openServiceModal();
    await oversized.context.submitService(oversized.nodes.get('serviceForm'));
    assert.match(oversized.nodes.get('serviceFormError').textContent, /picture is too large/);
    await success.context.submitService(success.nodes.get('serviceForm'), true);
    assert.equal(success.state.requests, 2, 'Delete also refreshes in place');
    console.log('Service settings tab, save, edit validation, duplicate submit, and delete UI checks passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
