const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const script = fs.readFileSync('public/js/user-management-access.js', 'utf8');
function browser(initial = { verified: false, admin_name: 'Admin', masked_email: 'a***@example.test' }) {
    let time = Date.parse('2026-11-06T00:00:00Z');
    const nodes = new Map(), requests = [], responses = [], events = [], timers = [], windowEvents = {};
    for (const id of ['userManagementVerificationData', 'userManagementGate', 'userManagementContent', 'userManagementVerifyForm',
        'userManagementOtp', 'userManagementSendCode', 'userManagementVerifyCode', 'userManagementGateError',
        'userManagementGateStatus', 'userModal', 'userConfirm', 'userManagementCancelVerification']) {
        nodes.set(id, { open: false, value: '', style: {}, hidden: true, listeners: {},
            addEventListener(type, fn) { this.listeners[type] = fn; },
            showModal() { this.open = true; }, close() { this.open = false; }, focus() {}, contains() { return true; },
        });
    }
    nodes.get('userManagementVerificationData').textContent = JSON.stringify(initial);
    const context = {
        document: { getElementById: id => nodes.get(id), querySelector: () => ({ content: 'csrf' }) },
        window: { addEventListener(type, fn) { windowEvents[type] = fn; }, dispatchEvent: event => events.push(event) },
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options?.detail; } },
        Date: class extends Date { static now() { return time; } },
        setTimeout(fn) { timers.push(fn); return timers.length; }, clearTimeout() {}, setInterval() {}, AbortSignal: { timeout() {} },
        fetch: async (url, options) => { requests.push({ url, options }); const next = responses.shift(); return { ok: next.ok ?? true, status: next.status ?? 200, json: async () => next }; },
    };
    vm.runInNewContext(script, context);
    return { nodes, requests, responses, events, context, windowEvents, expire: () => timers.at(-1)(), advance: ms => time += ms };
}
const flush = () => new Promise(setImmediate);
(async () => {
    const page = browser();
    const node = id => page.nodes.get(id);
    assert.equal(node('userManagementGate').open, false, 'Browsing the page does not show verification');
    assert.equal(node('userManagementContent').inert, false);
    assert.equal(page.requests.length, 0, 'Opening the gate does not send an email without a click');
    page.windowEvents.focus();
    assert.equal(node('userManagementGate').open, false, 'Refocusing an unverified page does not open the modal');
    let resumed = 0, prevented = false;
    const button = { click() { resumed++; } };
    node('userManagementContent').listeners.click({ target: { closest: () => button }, preventDefault() { prevented = true; }, stopImmediatePropagation() {} });
    assert.equal(prevented, true);
    assert.equal(node('userManagementGate').open, true, 'An account action opens verification before acting');
    page.responses.push({ success: true, challenge_id: 'challenge', retry_after: 60, message: 'Code sent.' });
    node('userManagementSendCode').listeners.click(); await flush();
    assert.ok(page.requests[0].url.endsWith('/verification-code'));
    assert.equal(node('userManagementVerifyForm').hidden, false);
    page.windowEvents.focus();
    assert.equal(node('userManagementVerifyForm').hidden, false, 'Returning from email preserves the pending code');
    node('userManagementOtp').value = '111111';
    page.responses.push({ ok: false, status: 422, success: false, message: 'Incorrect code.' });
    node('userManagementVerifyForm').listeners.submit({ preventDefault() {} }); await flush();
    assert.equal(node('userManagementGateError').textContent, 'Incorrect code.');
    assert.equal(node('userManagementGate').open, true);
    page.responses.push({ success: true, verified: true, expires_at: '2026-11-07T00:00:00Z' }, { success: true, users: [{ user_id: 1 }] });
    node('userManagementVerifyForm').listeners.submit({ preventDefault() {} }); await flush();
    assert.equal(node('userManagementGate').open, false);
    assert.equal(node('userManagementContent').inert, false);
    assert.equal(resumed, 1, 'The original clicked action resumes after successful OTP');
    assert.ok(page.events.some(event => event.type === 'user-management-unlocked' && event.detail.users.length === 1));
    const count = page.requests.length;
    for (let i = 0; i < 5; i++) assert.equal(page.context.window.userManagementAccess.ensure(), true);
    page.advance(23 * 3600000);
    assert.equal(page.context.window.userManagementAccess.ensure(), true);
    assert.equal(page.requests.length, count, 'Account actions do not ask for another code');
    page.advance(3600000);
    page.expire();
    assert.equal(node('userManagementGate').open, false, 'Expiry waits for the next action before opening verification');
    assert.equal(page.context.window.userManagementAccess.ensure(), false);
    assert.equal(node('userManagementGate').open, true);
    assert.equal(node('userManagementContent').inert, false);
    node('userManagementCancelVerification').listeners.click();
    assert.equal(node('userManagementGate').open, false, 'Cancel returns to the page');
    const refreshed = browser({ verified: true, expires_at: '2026-11-07T00:00:00Z' });
    assert.equal(refreshed.nodes.get('userManagementGate').open, false, 'Page refresh keeps existing verified access');
    assert.equal(refreshed.requests.length, 0);
    const main = fs.readFileSync('resources/views/admin_acc/user_management.blade.php', 'utf8');
    const modals = main.slice(main.indexOf('        function openAddModal()'), main.indexOf('        function showModalError('));
    const fields = new Map();
    const modalContext = {
        accountBusy: false, usersData: [{ user_id: 1, name: 'Staff', email: 'staff@example.test', user_role: 'Staff', status: 'active' }],
        window: { userManagementAccess: { ensure: () => false } },
        document: { getElementById(id) {
            if (!fields.has(id)) fields.set(id, { value: '', style: {}, reset() {} });
            return fields.get(id);
        } },
    };
    vm.runInNewContext(modals, modalContext);
    modalContext.openAddModal();
    assert.equal(fields.size, 0, 'Locked page cannot open account forms');
    modalContext.window.userManagementAccess.ensure = () => true;
    modalContext.openAddModal();
    assert.equal(fields.get('userModal').style.display, 'flex');
    modalContext.closeModal();
    modalContext.openEditModal(1);
    assert.equal(fields.get('userEmail').value, 'staff@example.test');
    assert.equal(fields.get('userModal').style.display, 'flex', 'Account forms open without asking for another code');
    console.log('User Management overlay, OTP retry, unlock, refresh, and fixed 24-hour expiry checks passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
