<style>
    select.dashboard-chart-filter { border:1px solid transparent; font:inherit; font-size:.85rem; color:#062744; cursor:pointer; max-width:100%; }
    select.dashboard-chart-filter:focus-visible { outline:2px solid #0e62aa; outline-offset:2px; }
    .dashboard-chart-filter-status { color:#64748b; font-size:.85rem; }
</style>
<script>
window.createDashboardChartFilters = function (refresh) {
    const inquiry = document.getElementById('inquiry-period-filter');
    const visits = document.getElementById('visit-weeks-filter');
    const statuses = [document.getElementById('inquiry-filter-status'), document.getElementById('visits-filter-status')];
    let controller;
    for (const [control, status] of [[inquiry, statuses[0]], [visits, statuses[1]]]) {
        control.addEventListener('change', () => {
            for (const message of statuses) message.textContent = '';
            status.textContent = 'Updating chart…';
            refresh();
        });
    }
    return {
        async load(baseUrl) {
            controller?.abort();
            const active = new AbortController();
            controller = active;
            const url = new URL(baseUrl, window.location.href);
            url.searchParams.set('inquiry_period', inquiry.value);
            url.searchParams.set('visit_weeks', visits.value);
            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' }, cache: 'no-store', signal: active.signal,
                });
                if (!response.ok) throw new Error('Unable to update charts. Please try again.');
                const data = await response.json();
                if (active.signal.aborted) return null;
                document.getElementById('inquiry-period-description').textContent = data.inquiryPeriodLabel;
                document.getElementById('visits-period-description').textContent = data.visitPeriodLabel;
                for (const message of statuses) message.textContent = '';
                return data;
            } catch (error) {
                if (!active.signal.aborted) {
                    for (const message of statuses) message.textContent = 'Could not update charts. Previous data is still shown; please try again.';
                }
                return null;
            }
        },
    };
};
</script>
