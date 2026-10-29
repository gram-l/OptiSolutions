const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const raw = fs.readFileSync('resources/views/staff/dashboard.blade.php', 'utf8');
const script = [...raw.matchAll(/<script>([\s\S]*?)<\/script>/g)].at(-1)[1]
    .replace(/@json\(\$([\w]+)\)/g, (_, key) => JSON.stringify({
        hasServiceData: false, hasInquiryStatusData: false,
        serviceLabels: ['No data'], serviceTotals: [1], weeklyVisitsLabels: ['This week'],
        weeklyVisitsData: [0], inquiryVolumeData: [0, 0, 0, 0, 0, 0, 0],
        sentimentTrendLabelsData: [], sentimentTrendPositiveData: [],
        sentimentTrendNeutralData: [], sentimentTrendNegativeData: [],
        resolvedInquiriesCount: 0, unresolvedInquiriesCount: 0,
    }[key]));
const nodes = new Map();
const getNode = id => {
    if (!nodes.has(id)) nodes.set(id, { id, style: {}, textContent: '', innerHTML: '' });
    return nodes.get(id);
};
const charts = new Map();
// Chart.js exposes resolved option objects through proxies, not its raw config.
const proxies = new WeakMap();
function optionsProxy(object) {
    if (!object || typeof object !== 'object') return object;
    if (proxies.has(object)) return proxies.get(object);
    const proxy = new Proxy(object, {
        get(target, name) { return optionsProxy(target[name]); },
        set(target, name, value) {
            if (value && typeof value === 'object' && value === optionsProxy(target[name])) {
                throw new Error('Resolved Chart.js options must not be assigned back into their configuration');
            }
            target[name] = value; return true;
        },
    });
    proxies.set(object, proxy); return proxy;
}
let ready, refresh, loadCount = 0;
const errors = [];
const context = {
    document: { body: { classList: { contains: () => false } }, hidden: false,
        getElementById: getNode, querySelectorAll: () => [],
        addEventListener(name, callback) { if (name === 'DOMContentLoaded') ready = callback; },
    },
    window: { addEventListener() {}, createDashboardChartFilters(callback) {
        refresh = callback;
        return { async load() { loadCount++; return {
            serviceDistribution: [], weeklyVisits: Array(12).fill(0), weekLabels: Array(12).fill('Week'),
            inquiryVolumeByDay: [1, 2, 3, 0, 0, 0, 0], recentActivities: [],
        }; } };
    } },
    Chart: class {
        constructor(canvas, config) {
            config.options.plugins.legend ||= {};
            config.options.plugins.legend.labels ||= {};
            this.config = config; this.data = config.data; this.options = optionsProxy(config.options);
            charts.set(canvas.id, this);
        }
        update() {}
    },
    setInterval() {}, console: { error: (...args) => errors.push(args) },
};
(async () => {
    vm.runInNewContext(script, context);
    ready();
    assert.equal(typeof refresh, 'function', 'Chart initialization must reach filter registration');
    await refresh();
    assert.equal(loadCount, 1);
    assert.equal(errors.length, 0, JSON.stringify(errors));
    assert.equal(charts.get('weeklyVisitsChart').data.datasets[0].data.length, 12);
    assert.deepEqual(charts.get('inquiryVolumeChart').data.datasets[0].data, [1, 2, 3, 0, 0, 0, 0]);
    console.log('Staff chart initialization and filter refresh passed, including empty 12-week data.');
})().catch(error => { console.error(error); process.exitCode = 1; });
