const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const complaint = {
    complaint_id: 1, complaint_text: 'Matagal ang pila', category: 'waiting_time',
    created_at: '2026-09-10 12:00:00', issues_analyzed: true, issue_categories: ['waiting_time'],
    issue_findings: [{category: 'waiting_time', reported_reason: 'Queue reported', evidence: '<img src=x onerror=alert(1)>'}],
};
const reviews = [
    {id: 1, rating: 2, doctor_id: 11, doctor: 'Doctor A', service: 'Consultation', sentiment: 'negative',
        comment: 'Long wait', issue_categories: ['waiting_time'], issues_analyzed: true, date: '2026-09-10 12:00:00'},
    {id: 2, rating: 5, doctor_id: 12, doctor: 'Doctor B', service: 'consultation', sentiment: 'positive',
        comment: 'Helpful', date: '2026-09-11 12:00:00'},
    {id: 3, rating: 4, doctor_id: 11, doctor: 'Doctor A', service: 'Dental', sentiment: 'no_comment',
        date: '2026-09-12 12:00:00'},
];
const source = fs.readFileSync('resources/views/admin_acc/feedback.blade.php', 'utf8');
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace('@json($feedbackData)', JSON.stringify(reviews))
    .replace('@json($complaintData)', JSON.stringify([complaint]));
const nodes = new Map();
function node(id) {
    if (!nodes.has(id)) nodes.set(id, {value: id === 'sentimentFilter' ? 'all' : '', style: {}, innerHTML: '',
        options: [], add(option) { this.options.push(option); },
        listeners: {}, addEventListener(event, listener) { this.listeners[event] = listener; },
        classList: {contains: () => false, toggle() {}}});
    return nodes.get(id);
}
const context = vm.createContext({Option: class {
    constructor(text, value) { this.text = text; this.value = value; }
}, document: {
    getElementById: node, addEventListener() {},
    createElement: () => ({set textContent(value) { this.innerHTML = String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }}),
}});
vm.runInContext(script, context);
assert.match(node('complaintTableBody').innerHTML, /Queue reported/);
assert.doesNotMatch(node('complaintTableBody').innerHTML, /<blockquote|onerror=/);
assert.equal(node('complaintTableBody').innerHTML.split(complaint.complaint_text).length - 1, 1);
assert.doesNotMatch(node('complaintTableBody').innerHTML, /<img/);
const rows = vm.runInContext("getExportRows('complaint')", context);
assert.equal(rows[0]['Reported reason'], 'Waiting Time: Queue reported');
assert.equal(rows[0].Evidence, complaint.issue_findings[0].evidence);
context.unavailable = {...complaint, issues_analyzed: false};
assert.match(vm.runInContext('renderComplaintEvidence(unavailable)', context), /Analysis unavailable/);
context.legacy = {...complaint, issue_findings: []};
assert.match(vm.runInContext('renderComplaintEvidence(legacy)', context), /No additional detail provided/);
context.vague = {...context.legacy, category: 'other'};
assert.match(vm.runInContext('renderComplaintEvidence(vague)', context), /Needs clarification.*No specific issue described/s);
context.queued = {...complaint, issue_findings: [{category: 'waiting_time', reported_reason: 'Wait in queue reported; underlying reason unspecified'}]};
assert.match(vm.runInContext('renderComplaintEvidence(queued)', context), /Wait in queue reported/);
assert.doesNotMatch(vm.runInContext('renderComplaintEvidence(queued)', context), /unspecified/i);

const doctorFilter = node('feedbackDoctorFilter');
const serviceFilter = node('feedbackServiceFilter');
assert.deepEqual(doctorFilter.options.map(option => option.value), ['11', '12']);
assert.deepEqual(serviceFilter.options.map(option => option.value), ['consultation', 'dental']);
assert.match(node('doctorRatings').innerHTML, /Doctor A/);
assert.match(node('doctorRatings').innerHTML, /Doctor B/);
assert.match(node('negativePatterns').innerHTML, /2 mentions/);
serviceFilter.value = 'consultation';
serviceFilter.listeners.change();
assert.equal(vm.runInContext("getExportRows('feedback').length", context), 2);
assert.match(node('negativePatterns').innerHTML, /1 mentions/);
assert.equal(node('filteredPatternsNote').hidden, false);
doctorFilter.value = '11';
doctorFilter.listeners.change();
assert.doesNotMatch(node('doctorRatings').innerHTML, /Doctor B/);
assert.doesNotMatch(node('feedbackTableBody').innerHTML, /Helpful/);
assert.equal(vm.runInContext("getExportRows('feedback').length", context), 1);
assert.equal(vm.runInContext("getExportRows('complaint').length", context), 1);
node('sentimentFilter').value = 'positive';
node('sentimentFilter').listeners.change();
assert.equal(vm.runInContext("getExportRows('feedback').length", context), 0);
console.log('Feedback filters, rating views, complaint rendering, and exports passed.');
