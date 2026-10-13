<form id="accountLogoutForm" method="POST" action="{{ route('logout') }}" hidden>
    @csrf
</form>
<script>
(() => {
    const csrf = @json(csrf_token());
    const loginUrl = @json(route('login'));
    const activityUrl = @json(route($accountRole . '.profile.activity'));
    const activityKey = 'opti_account_activity_' + @json((string) Auth::id());
    let timeout = @json((int) (Auth::user()->session_timeout ?? 30));
    let lastActivity = @json((int) session('account_last_activity', now()->timestamp)) * 1000;
    let lastSent = 0;
    let pending = null;
    let busy = false;
    let expired = false;

    function publishActivity() {
        try { localStorage.setItem(activityKey, JSON.stringify({ lastActivity, timeout })); } catch (_) {}
    }
    publishActivity();
    window.addEventListener('storage', event => {
        if (event.key !== activityKey || !event.newValue) return;
        try {
            const state = JSON.parse(event.newValue);
            lastActivity = Math.max(lastActivity, state.lastActivity);
            timeout = state.timeout;
        } catch (_) {}
    });

    async function sendActivity() {
        pending = null;
        if (busy || expired) return;
        busy = true;
        lastSent = Date.now();
        try {
            const response = await fetch(activityUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            if (response.status === 401 || response.status === 419) {
                expired = true;
                window.location.assign(loginUrl);
                return;
            }
            if (!response.ok) return;
            const state = await response.json();
            timeout = state.timeout;
            publishActivity();
        } catch (_) {
            // Retry on the next interaction; do not silently extend server activity.
        } finally { busy = false; }
    }

    function recordActivity(event) {
        if (!event.isTrusted || expired) return;
        lastActivity = Date.now();
        publishActivity();
        if (Date.now() - lastSent >= 10000) {
            if (pending) clearTimeout(pending);
            sendActivity();
        } else if (!pending) {
            pending = setTimeout(sendActivity, 10000 - (Date.now() - lastSent));
        }
    }
    ['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach(type =>
        document.addEventListener(type, recordActivity, { passive: true }));

    function checkExpiry() {
        if (expired || timeout === 0 || Date.now() - lastActivity < timeout * 60000) return;
        expired = true;
        document.getElementById('accountLogoutForm').submit();
    }
    setInterval(checkExpiry, 1000);
    document.addEventListener('visibilitychange', checkExpiry);

    document.querySelectorAll('[data-account-clear-cache]').forEach(button => {
        button.addEventListener('click', async () => {
            const status = document.querySelector('[data-account-cache-status]');
            button.disabled = true;
            status.textContent = 'Clearing cached pages…';
            try {
                const response = await fetch(button.dataset.cacheUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Could not clear cached pages. Please try again.');
                status.textContent = result.message;
            } catch (error) {
                status.textContent = error.message || 'Could not clear cached pages. Please try again.';
            } finally { button.disabled = false; }
        });
    });
})();
</script>
