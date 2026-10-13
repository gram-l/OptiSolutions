const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const raw = fs.readFileSync('resources/views/partials/account-settings.blade.php', 'utf8');
const script = raw.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace('@json(csrf_token())', '"csrf"')
    .replace("@json(route('login'))", '"/auth/login"')
    .replace("@json(route($accountRole . '.profile.activity'))", '"/profile/activity"')
    .replace('@json((string) Auth::id())', '"42"')
    .replace('@json((int) (Auth::user()->session_timeout ?? 30))', '15')
    .replace("@json((int) session('account_last_activity', now()->timestamp))", '1000');

function browser(response = { ok: true, status: 200, json: async () => ({ timeout: 15, message: 'Cleared' }) }) {
    let time = 1000000;
    const events = {}, windowEvents = {}, buttonEvents = {}, intervals = [], deferred = [];
    const state = { requests: [], logout: 0, redirect: null };
    const button = { disabled: false, dataset: { cacheUrl: '/profile/cache' }, addEventListener: (type, fn) => buttonEvents[type] = fn };
    const status = { textContent: '' };
    const context = {
        Date: { now: () => time },
        localStorage: { setItem() {} },
        window: { addEventListener: (type, fn) => windowEvents[type] = fn, location: { assign: url => state.redirect = url } },
        document: {
            addEventListener: (type, fn) => events[type] = fn,
            getElementById: () => ({ submit: () => state.logout++ }),
            querySelectorAll: () => [button], querySelector: () => status,
        },
        fetch: async (url, options) => { state.requests.push({ url, options }); return response; },
        setInterval: fn => intervals.push(fn), setTimeout: fn => deferred.push(fn), clearTimeout() {},
    };
    vm.runInNewContext(script, context);
    return { state, button, status, events, windowEvents, buttonEvents, intervals, deferred, advance: ms => time += ms };
}

(async () => {
    const idle = browser();
    idle.advance(900000);
    idle.intervals[0]();
    idle.intervals[0]();
    assert.equal(idle.state.logout, 1);
    assert.equal(idle.state.requests.length, 0, 'No background activity requests');

    const active = browser();
    active.advance(899000);
    active.events.keydown({ isTrusted: true });
    await new Promise(setImmediate);
    active.advance(2000);
    active.intervals[0]();
    assert.equal(active.state.logout, 0);
    assert.equal(active.state.requests[0].options.method, 'POST');
    assert.equal(active.state.requests[0].options.headers['X-CSRF-TOKEN'], 'csrf');

    const fake = browser();
    fake.advance(899000);
    fake.events.keydown({ isTrusted: false });
    fake.advance(1000);
    fake.intervals[0]();
    assert.equal(fake.state.logout, 1, 'Synthetic events cannot extend inactivity');

    const otherTab = browser();
    otherTab.advance(899000);
    otherTab.windowEvents.storage({ key: 'opti_account_activity_42', newValue: JSON.stringify({ lastActivity: 1899000, timeout: 15 }) });
    otherTab.advance(2000);
    otherTab.intervals[0]();
    assert.equal(otherTab.state.logout, 0, 'Activity in another tab keeps the account active');
    otherTab.windowEvents.storage({ key: 'opti_account_activity_42', newValue: JSON.stringify({ lastActivity: 1899000, timeout: 0 }) });
    otherTab.advance(9000000);
    otherTab.intervals[0]();
    assert.equal(otherTab.state.logout, 0, 'Never disables the browser inactivity timer');

    const expired = browser({ ok: false, status: 401 });
    expired.events.pointerdown({ isTrusted: true });
    await new Promise(setImmediate);
    assert.equal(expired.state.redirect, '/auth/login');

    const cache = browser();
    await cache.buttonEvents.click();
    assert.equal(cache.status.textContent, 'Cleared');
    assert.equal(cache.button.disabled, false);
    assert.equal(cache.state.requests[0].url, '/profile/cache');
    const failedCache = browser({ ok: false, status: 500, json: async () => ({ message: 'Cache unavailable' }) });
    await failedCache.buttonEvents.click();
    assert.equal(failedCache.status.textContent, 'Cache unavailable');
    assert.equal(failedCache.button.disabled, false);

    // Validate the remaining inline settings scripts after substituting Blade output.
    for (const file of ['resources/views/admin_acc/partials/settings.blade.php', 'resources/views/staff/layouts/app.blade.php']) {
        const source = fs.readFileSync(file, 'utf8');
        for (const [, inline] of source.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)) {
            const javascript = inline.replace(/\{\{[^\n]*?\}\}/g, 'test')
                .replace(/^\s*@(?:if|elseif|else|endif).*$/gm, '');
            new vm.Script(javascript, { filename: file });
        }
    }
    console.log('Account UI checks passed: inactivity, real activity, multiple tabs, Never, expiry redirects, cache success/failure, inline syntax.');
})().catch(error => { console.error(error); process.exitCode = 1; });
