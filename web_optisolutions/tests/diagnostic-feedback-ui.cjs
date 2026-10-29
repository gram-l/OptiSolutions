const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const complaint = {
    complaint_id: 1, complaint_text: 'Matagal ang pila', category: 'waiting_time',
    created_at: '2026-09-10 12:00:00', issues_analyzed: true,
    issue_findings: [{category: 'waiting_time', reported_reason: 'Queue reported', evidence: '<img src=x onerror=alert(1)>'}],
};
const source = fs.readFileSync('resources/views/admin_acc/feedback.blade.php', 'utf8');
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace('@json($feedbackData)', '[]')
    .replace('@json($complaintData)', JSON.stringify([complaint]));
const nodes = new Map();
function node(id) {
    if (!nodes.has(id)) nodes.set(id, {value: id === 'sentimentFilter' ? 'all' : '', style: {}, innerHTML: '',
        addEventListener() {}, classList: {contains: () => false, toggle() {}}});
    return nodes.get(id);
}
const context = vm.createContext({document: {
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
assert.match(vm.runInContext('renderComplaintEvidence(legacy)', context), /Reason unspecified/);
console.log('Complaint rendering without duplicate quotes, unavailable analysis, legacy results, and export checks passed.');
