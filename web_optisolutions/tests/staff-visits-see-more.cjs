const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const page = fs.readFileSync('resources/views/staff/appointments/index.blade.php', 'utf8');
const filter = page.slice(page.indexOf('    function filterVisitsTable('), page.indexOf('\n\n\n    updateDateLabel();', page.indexOf('    function filterVisitsTable(')));
const rows = Array.from({ length: 16 }, (_, index) => ({
    style: {}, dataset: { doctor: 'Doctor A', search: index < 9 ? 'alice' : 'bob' },
    getAttribute: () => '2026-11-05',
}));
const nodes = Object.fromEntries(['visitDate', 'serviceFilter', 'doctorFilter', 'visitSearch', 'visitsVisibleCount', 'visitsSeeMore', 'noResultsMsg']
    .map(id => [id, { value: '', style: {} }]));
nodes.visitsTable = { getElementsByTagName: () => [{ getElementsByTagName: () => rows }] };
const links = [{ href: 'https://clinic.example/staff/appointments/export/csv' }];
const context = {
    document: { getElementById: id => nodes[id], querySelectorAll: () => links },
    visitDoctors: [{ doctor_name: 'Doctor A', specialty: 'General / Adult Medicine' }],
    URL, URLSearchParams,
};
vm.runInNewContext(fs.readFileSync('public/js/doctor-specialties.js', 'utf8') + '\nlet visibleVisitCount = 7;\n' + filter, context);
const shown = () => rows.filter(row => row.style.display !== 'none').length;
context.filterVisitsTable();
assert.equal(shown(), 7);
assert.equal(nodes.visitsVisibleCount.textContent, '7 of 16 visit(s)');
assert.equal(nodes.visitsSeeMore.textContent, 'See more (9 more)');
vm.runInNewContext('visibleVisitCount += 7; filterVisitsTable(false);', context);
assert.equal(shown(), 14);
assert.equal(nodes.visitsSeeMore.textContent, 'See more (2 more)');
vm.runInNewContext('visibleVisitCount += 7; filterVisitsTable(false);', context);
assert.equal(shown(), 16);
assert.equal(nodes.visitsSeeMore.hidden, true);
nodes.visitSearch.value = 'alice';
context.filterVisitsTable();
assert.equal(shown(), 7, 'Changing filters resets the visible limit');
assert.equal(nodes.visitsVisibleCount.textContent, '7 of 9 visit(s)');
assert.equal(new URL(links[0].href).searchParams.get('search'), 'alice', 'Exports keep the complete filtered result');
nodes.visitSearch.value = 'missing';
context.filterVisitsTable();
assert.equal(shown(), 0);
assert.equal(nodes.visitsSeeMore.hidden, true);
assert.equal(nodes.noResultsMsg.style.display, 'block');
nodes.visitSearch.value = 'bob';
context.filterVisitsTable();
assert.equal(shown(), 7);
assert.equal(nodes.visitsSeeMore.hidden, true, 'Exactly seven matches need no expansion');
console.log('Staff visit See more checks passed.');
