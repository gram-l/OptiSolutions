const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const template = fs.readFileSync('resources/views/auth/login.blade.php', 'utf8');
const script = template.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace('{{ csrf_token() }}', 'test-csrf');

function browser(response) {
    const state = { request: null, shown: false };
    const error = { textContent: '', classList: {
        add: () => state.shown = true, remove: () => state.shown = false,
    } };
    const context = {
        document: { getElementById: id => id === 'google-error' ? error : { addEventListener() {} } },
        window: { location: {} },
        AbortSignal: { timeout: () => 'timeout-signal' },
        fetch: async (url, options) => {
            state.request = { url, options };
            if (response instanceof Error) throw response;
            return response;
        },
    };
    vm.runInNewContext(script, context);
    return { context, state, error };
}

(async () => {
    const success = browser({ ok: true, json: async () => ({ redirect: '/staff/dashboard' }) });
    await success.context.handleGoogleLogin({ credential: 'google-token' });
    assert.equal(success.context.window.location.href, '/staff/dashboard');
    assert.equal(success.state.request.url, '/auth/google');
    assert.equal(success.state.request.options.credentials, 'same-origin');
    assert.equal(success.state.request.options.headers['X-CSRF-TOKEN'], 'test-csrf');
    assert.equal(JSON.parse(success.state.request.options.body).id_token, 'google-token');
    for (const response of [
        { status: 419, json: async () => ({}) },
        { status: 503, json: async () => ({ message: 'Google sign-in is not configured.' }) },
        { status: 502, json: async () => { throw new Error('HTML response'); } },
        Object.assign(new Error('timeout'), { name: 'TimeoutError' }),
    ]) {
        const page = browser(response);
        await page.context.handleGoogleLogin({ credential: 'google-token' });
        assert.equal(page.state.shown, true);
        assert.ok(page.error.textContent.length);
        assert.equal(page.context.window.location.href, undefined);
    }
    const missing = browser();
    await missing.context.handleGoogleLogin({});
    assert.equal(missing.state.request, null);
    assert.equal(missing.state.shown, true);
    missing.context.googleScriptFailed();
    assert.match(missing.error.textContent, /could not load/);
    console.log('Google login UI checks passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
