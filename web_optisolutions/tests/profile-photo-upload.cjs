const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const script = fs.readFileSync('public/js/profile-photo.js', 'utf8');
function browser({ auto = false, decodeError = false, outputSize = 180000 } = {}) {
    const events = {}, formEvents = {}, revoked = [];
    const input = { files: [], value: '', addEventListener: (name, fn) => events[name] = fn };
    const status = {}, button = { disabled: false }, canvas = {};
    const state = { submissions: 0, encodes: 0, preview: null };
    const form = {
        dataset: { autoUpload: String(auto) },
        querySelector: selector => selector === '[type="submit"]' ? button : input,
        addEventListener: (name, fn) => formEvents[name] = fn,
        requestSubmit: () => state.submissions++,
    };
    canvas.getContext = () => ({ fillRect() {}, drawImage() {}, set fillStyle(value) {} });
    canvas.toBlob = callback => { state.encodes++; callback({ size: outputSize }); };
    const context = {
        document: {
            querySelectorAll: () => [form],
            getElementById: id => id === 'profilePhotoStatus' ? status : { replaceChildren: img => state.preview = img },
            createElement: tag => tag === 'canvas' ? canvas : {},
        },
        URL: { createObjectURL: () => 'blob:photo', revokeObjectURL: url => revoked.push(url) },
        Image: class {
            naturalWidth = 4000;
            naturalHeight = 3000;
            set src(value) { queueMicrotask(() => decodeError ? this.onerror() : this.onload()); }
        },
        File: class { constructor(blobs, name, options) { this.size = blobs[0].size; this.name = name; this.type = options.type; } },
        DataTransfer: class {
            files = [];
            items = { add: file => this.files.push(file) };
        },
    };
    status.style = {};
    vm.runInNewContext(script, context);
    return { input, events, formEvents, status, button, state, canvas, revoked };
}

(async () => {
    for (const auto of [false, true]) {
        for (const type of ['image/jpeg', 'image/png']) {
            const page = browser({ auto });
            const original = { size: 8 * 1024 * 1024, type };
            page.input.files = [original];
            const preparing = page.events.change();
            let blocked = false;
            page.formEvents.submit({ preventDefault() { blocked = true; } });
            assert.ok(blocked, 'Cannot submit original file while resizing');
            assert.ok(page.button.disabled);
            await preparing;
            assert.notEqual(page.input.files[0], original);
            assert.equal(page.input.files[0].type, 'image/jpeg');
            assert.ok(page.input.files[0].size <= 512 * 1024);
            assert.equal(page.canvas.width, 1024);
            assert.equal(page.canvas.height, 768);
            assert.equal(page.state.submissions, auto ? 1 : 0);
            assert.equal(page.input.disabled, false, 'Prepared input must be included in multipart submission');
            assert.equal(page.button.disabled, false);
            assert.ok(page.revoked.length);
        }
    }
    for (const options of [{}, { decodeError: true }, { outputSize: 900000 }]) {
        const page = browser({ auto: true, ...options });
        page.input.value = 'selected-file';
        page.input.files = [{ size: 4000000, type: Object.keys(options).length ? 'image/jpeg' : 'image/gif' }];
        await page.events.change();
        assert.equal(page.state.submissions, 0, 'Invalid/unreadable/oversized photos are never sent');
        assert.equal(page.input.value, '');
        assert.equal(page.status.style.color, '#c0392b');
        assert.equal(page.input.disabled, false);
        assert.ok(page.status.textContent.length);
    }
    console.log('Profile photo upload checks passed (admin/staff, JPG/PNG, pending submit, invalid/decode/size failures).');
})().catch(error => { console.error(error); process.exitCode = 1; });
