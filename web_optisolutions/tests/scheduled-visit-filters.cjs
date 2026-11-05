const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const page = fs.readFileSync('resources/views/admin_acc/appointments.blade.php', 'utf8');
const functions = page.slice(page.indexOf('        function populateServiceFilter('), page.indexOf('        function updateStats('));
const listeners = page.slice(page.indexOf("        document.getElementById('serviceFilter').addEventListener"), page.indexOf("        document.getElementById('visitSearch').addEventListener"));
const nodes = {};
for (const id of ['doctorFilter', 'serviceFilter', 'visitSearch']) {
    nodes[id] = { value: id === 'visitSearch' ? '' : 'all', options: [],
        replaceChildren(...options) { this.options = options; },
        addEventListener(type, handler) { this[type] = handler; },
    };
}
const doctors = [
    { doctor_id: 1, doctor_name: 'Dr. Same Name', specialty: 'Pediatrics' },
    { doctor_id: 2, doctor_name: 'Dr. Other', specialty: 'Pediatrics' },
    { doctor_id: 3, doctor_name: 'Dr. Same Name', specialty: 'Ophthalmology' },
    { doctor_id: 4, doctor_name: 'Dr. No Specialty', specialty: null },
];
const visits = [
    { visit_id: 1, doctor_id: 1, doctor_specialty: 'Pediatrics', service_type: 'Child consultation', patient_name: 'Alice' },
    { visit_id: 2, doctor_id: 2, doctor_specialty: 'Pediatrics', service_type: 'Vaccination', patient_name: 'Bob' },
    { visit_id: 3, doctor_id: 3, doctor_specialty: 'Ophthalmology', service_type: 'Eye exam', patient_name: 'Carol' },
];
const context = { visitDoctors: doctors, visitsData: visits, visitServices: ['Ophthalmology', 'Pediatrics'],
    document: { getElementById: id => nodes[id] }, renderDay() {},
    Option: class { constructor(text, value) { this.text = text; this.value = value; } },
};
vm.runInNewContext(fs.readFileSync('public/js/doctor-specialties.js', 'utf8') + functions + listeners, context);
const doctor = nodes.doctorFilter, service = nodes.serviceFilter;
const values = node => node.options.map(option => option.value);
const filteredIds = () => Array.from(context.getFiltered(), visit => visit.visit_id);
context.populateServiceFilter(); context.populateDoctorFilter();
assert.deepEqual(values(doctor), ['all', '1', '2', '3', '4']);
assert.deepEqual(values(service), ['all', 'Ophthalmology', 'Pediatrics']);
doctor.value = '1'; doctor.change();
assert.equal(service.value, 'Pediatrics');
assert.deepEqual(values(service), ['all', 'Pediatrics']);
assert.deepEqual(values(doctor), ['all', '1', '2']);
assert.deepEqual(filteredIds(), [1], 'Same-name doctors are distinguished by ID');
service.value = 'all'; service.change();
assert.equal(doctor.value, 'all');
assert.equal(values(service).length, 3);
assert.equal(values(doctor).length, 5);
service.value = 'Pediatrics'; service.change();
assert.deepEqual(values(doctor), ['all', '1', '2']);
assert.deepEqual(filteredIds(), [1, 2], 'Match specialty even when booked service labels differ');
nodes.visitSearch.value = 'bob';
assert.deepEqual(filteredIds(), [2]);
nodes.visitSearch.value = '';
doctor.value = 'all'; doctor.change();
assert.equal(service.value, 'Pediatrics', 'Keep specialty when clearing doctor, matching diagnostic modal');
service.value = 'all'; service.change();
doctor.value = '4'; doctor.change();
assert.deepEqual(values(service), ['all']);
assert.deepEqual(filteredIds(), []);
context.visitsData = [];
service.value = 'all'; service.change();
assert.equal(values(doctor).length, 5, 'Catalog remains available on days without visits');
context.visitDoctors = [
    { doctor_id: 1, doctor_name: 'Doctor A', specialty: 'General / Adult Medicine' },
    { doctor_id: 2, doctor_name: 'Doctor B', specialty: 'Ophthalmology / General Medicine' },
];
context.visitsData = context.visitDoctors.map(d => ({ doctor_id: d.doctor_id, doctor_specialty: d.specialty, visit_id: d.doctor_id }));
context.visitServices = ['Adult Medicine', 'General', 'General Medicine', 'Ophthalmology'];
context.populateServiceFilter(); context.populateDoctorFilter();
assert.deepEqual(values(service), ['all', 'Adult Medicine', 'General', 'General Medicine', 'Ophthalmology']);
doctor.value = '1'; doctor.change();
assert.deepEqual(values(service), ['all', 'General', 'Adult Medicine']);
service.value = 'Adult Medicine'; service.change();
assert.equal(doctor.value, '1', 'Either specialty keeps its linked doctor selected');
assert.deepEqual(filteredIds(), [1]);
service.value = 'all'; service.change();
service.value = 'General Medicine'; service.change();
assert.deepEqual(values(doctor), ['all', '2']);
assert.deepEqual(filteredIds(), [2], 'General does not accidentally match General Medicine');
service.value = 'all'; service.change();
context.visitServices.push('ENT');
context.populateServiceFilter();
assert.ok(values(service).includes('ENT'), 'A newly managed service appears without an assigned doctor');
service.value = 'ENT'; service.change();
assert.deepEqual(values(doctor), ['all']);
assert.deepEqual(filteredIds(), []);
console.log('Scheduled Visit linked and split specialty filter checks passed.');

const staffPage = fs.readFileSync('resources/views/staff/appointments/index.blade.php', 'utf8');
const staffSync = staffPage.slice(staffPage.indexOf('    function syncVisitFilters('), staffPage.indexOf('\n    }', staffPage.indexOf('    function syncVisitFilters(')) + 6);
const staffContext = { ...context, visitDoctors: context.visitDoctors, filterVisitsTable() {} };
vm.runInNewContext(fs.readFileSync('public/js/doctor-specialties.js', 'utf8') + staffSync, staffContext);
doctor.value = ''; service.value = '';
staffContext.syncVisitFilters('service');
assert.deepEqual(values(service), ['', 'Adult Medicine', 'General', 'General Medicine', 'Ophthalmology', 'ENT']);
doctor.value = 'Doctor B'; staffContext.syncVisitFilters('doctor');
assert.deepEqual(values(service), ['', 'Ophthalmology', 'General Medicine']);
service.value = 'General Medicine'; staffContext.syncVisitFilters('service');
assert.equal(doctor.value, 'Doctor B');
service.value = ''; staffContext.syncVisitFilters('service');
service.value = 'Adult Medicine'; staffContext.syncVisitFilters('service');
assert.deepEqual(values(doctor), ['', 'Doctor A']);
console.log('Staff linked and split specialty filter checks passed.');
