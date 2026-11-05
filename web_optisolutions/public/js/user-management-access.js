(() => {
    const initial = JSON.parse(document.getElementById('userManagementVerificationData').textContent);
    const dialog = document.getElementById('userManagementGate');
    const content = document.getElementById('userManagementContent');
    const form = document.getElementById('userManagementVerifyForm');
    const input = document.getElementById('userManagementOtp');
    const send = document.getElementById('userManagementSendCode');
    const verify = document.getElementById('userManagementVerifyCode');
    const error = document.getElementById('userManagementGateError');
    const status = document.getElementById('userManagementGateStatus');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const base = '/admin_acc/user_management';
    let access = initial, challenge = null, cooldown = 0, busy = false, timer, pendingAction;

    function valid() { return access.verified && Date.now() < Date.parse(access.expires_at); }
    function controls() {
        const seconds = Math.max(0, Math.ceil((cooldown - Date.now()) / 1000));
        send.disabled = busy || seconds > 0;
        verify.disabled = busy;
        send.textContent = seconds ? `Resend in ${seconds}s` : challenge ? 'Resend OTP' : 'Send OTP';
        verify.textContent = valid() ? 'Continue' : 'Verify & Continue';
    }
    function scheduleExpiry() {
        clearTimeout(timer);
        timer = setTimeout(() => lock(false), Math.max(0, Date.parse(access.expires_at) - Date.now()));
    }
    function lock(show = true) {
        access = { ...access, verified: false };
        challenge = null; input.value = ''; form.hidden = true; error.hidden = true;
        status.textContent = 'Verify your email to use account actions.';
        content.inert = false;
        if (show && !dialog.open) dialog.showModal();
        controls();
    }
    async function request(path, body) {
        const response = await fetch(base + path, {
            method: body === undefined ? 'GET' : 'POST', credentials: 'same-origin', cache: 'no-store',
            signal: AbortSignal.timeout(20000),
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: body === undefined ? undefined : JSON.stringify(body),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            if (data.retry_after) cooldown = Date.now() + data.retry_after * 1000;
            throw new Error(response.status === 419 || response.status === 401 ? 'Your session expired. Please sign in again.'
                : Object.values(data.errors || {}).flat()[0] || data.message || 'Could not verify access. Please try again.');
        }
        return data;
    }
    async function unlock(data) {
        access = data;
        const accounts = await request('/accounts');
        content.inert = false;
        window.dispatchEvent(new CustomEvent('user-management-unlocked', { detail: accounts }));
        if (dialog.open) dialog.close();
        scheduleExpiry();
        const resume = pendingAction;
        pendingAction = null;
        if (resume) resume();
    }
    async function run(action) {
        if (busy) return;
        busy = true; error.hidden = true; controls();
        try { await action(); }
        catch (failure) { error.textContent = failure.message || 'Could not reach the server. Please try again.'; error.hidden = false; }
        finally { busy = false; controls(); }
    }
    send.addEventListener('click', () => {
        if (send.disabled) return;
        run(async () => {
            const data = await request('/verification-code', {});
            if (data.verified) { await unlock(data); return; }
            challenge = data.challenge_id; cooldown = Date.now() + data.retry_after * 1000;
            status.textContent = data.message + ' The code expires in 10 minutes.';
            form.hidden = false; input.value = ''; input.focus();
        });
    });
    form.addEventListener('submit', event => {
        event.preventDefault();
        run(async () => {
            const data = valid() ? access : await request('/verify-access', { challenge_id: challenge, otp: input.value.trim() });
            await unlock(data);
        });
    });
    function cancel() {
        if (busy) return;
        pendingAction = null;
        dialog.close();
    }
    document.getElementById('userManagementCancelVerification').addEventListener('click', cancel);
    dialog.addEventListener('cancel', event => { event.preventDefault(); cancel(); });
    window.userManagementAccess = { lock, ensure(resume) { if (valid()) return true; pendingAction = resume; lock(); return false; } };
    content.addEventListener('click', event => {
        const button = event.target.closest('button');
        if (!button || !content.contains(button) || valid()) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        window.userManagementAccess.ensure(() => button.click());
    }, true);
    window.addEventListener('focus', () => {
        if (!valid()) { if (access.verified) lock(false); return; }
        request('/verification-status').then(data => { if (!data.verified) lock(false); else { access = data; scheduleExpiry(); } }).catch(() => lock(false));
    });
    setInterval(controls, 1000);
    content.inert = false;
    if (valid()) scheduleExpiry(); else controls();
})();
