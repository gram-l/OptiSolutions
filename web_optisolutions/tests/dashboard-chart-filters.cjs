const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const nodes = new Map();
for (const id of ['inquiry-period-filter', 'visit-weeks-filter', 'inquiry-filter-status', 'visits-filter-status', 'inquiry-period-description', 'visits-period-description']) {
    nodes.set(id, { value: id === 'visit-weeks-filter' ? '6' : 'this_week', textContent: '',
        addEventListener(event, callback) { this[event] = callback; } });
}
const pending = [];
let refreshes = 0;
const context = { window: { location: { href: 'http://localhost/dashboard' } },
    document: { getElementById: id => nodes.get(id) }, URL, AbortController,
    fetch: (url, options) => new Promise(resolve => pending.push({ url, options, resolve })),
};
const source = fs.readFileSync('resources/views/partials/dashboard_chart_filters_script.blade.php', 'utf8');
vm.runInNewContext(source.match(/<script>([\s\S]*?)<\/script>/)[1], context);
const filters = context.window.createDashboardChartFilters(() => refreshes++);
const response = label => ({ ok: true, json: async () => ({ inquiryPeriodLabel: label, visitPeriodLabel: 'Visits' }) });
(async () => {
    nodes.get('inquiry-period-filter').value = 'last_week';
    nodes.get('inquiry-period-filter').change();
    nodes.get('visit-weeks-filter').value = '12';
    nodes.get('visit-weeks-filter').change();
    assert.equal(refreshes, 2);
    const first = filters.load('/data');
    assert.equal(pending[0].url.searchParams.get('inquiry_period'), 'last_week');
    assert.equal(pending[0].url.searchParams.get('visit_weeks'), '12');
    const second = filters.load('/data');
    assert.equal(pending[0].options.signal.aborted, true);
    pending[1].resolve(response('Latest'));
    await second;
    pending[0].resolve(response('Outdated'));
    assert.equal(await first, null);
    assert.equal(nodes.get('inquiry-period-description').textContent, 'Latest');
    const failed = filters.load('/data');
    pending[2].resolve({ ok: false });
    assert.equal(await failed, null);
    assert.match(nodes.get('inquiry-filter-status').textContent, /Previous data is still shown/);
    assert.equal(nodes.get('inquiry-period-filter').value, 'last_week');
    assert.equal(nodes.get('visit-weeks-filter').value, '12');
    console.log('Chart selection persistence, refresh parameters, stale responses, and error handling passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
