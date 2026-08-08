<?php
// app/Http/Controllers/admin_acc/PatientListController.php
namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_models\PatientList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientListController extends Controller
{
    // GET /admin/patients?doctor_id=&service_type=
    // Both filters are optional and combine with AND when both are given.
    // Filtering works entirely through schedule_visit — no columns or
    // relationships were added to `patients` or `doctors` for this.
    public function apiIndex(Request $request)
    {
        $query = PatientList::query();

        $doctorId = $request->query('doctor_id');
        $serviceType = $request->query('service_type');

        if ($doctorId || $serviceType) {
            $patientIds = DB::table('schedule_visit')
                ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
                ->when($serviceType, fn ($q) => $q->where('service_type', $serviceType))
                ->distinct()
                ->pluck('patient_id');

            $query->whereIn('patient_id', $patientIds);
        }

        return response()->json([
            'success'  => true,
            'patients' => $query->orderBy('patient_lname')->get(),
        ]);
    }

    // GET /admin/patients/service-types
    // Distinct list of service_type values that have ever appeared in
    // schedule_visit, for populating the "Service" filter dropdown.
    public function apiServiceTypes()
    {
        $types = DB::table('schedule_visit')
            ->whereNotNull('service_type')
            ->where('service_type', '!=', '')
            ->distinct()
            ->orderBy('service_type')
            ->pluck('service_type');

        return response()->json([
            'success'       => true,
            'service_types' => $types,
        ]);
    }

    // GET /admin/patients/{id}/visits
    // Visit/service history for one patient — service_type, visit_date,
    // and the doctor they saw — for display on the "View" sheet. Straight
    // read from schedule_visit + doctors, no new tables.
    public function apiVisits($id)
    {
        $patient = PatientList::find($id);
        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found.'], 404);
        }

        $visits = DB::table('schedule_visit')
            ->leftJoin('doctors', 'doctors.doctor_id', '=', 'schedule_visit.doctor_id')
            ->where('schedule_visit.patient_id', $id)
            ->orderByDesc('schedule_visit.visit_date')
            ->select(
                'schedule_visit.visit_id',
                'schedule_visit.service_type',
                'schedule_visit.visit_date',
                'schedule_visit.notes',
                'doctors.doctor_name'
            )
            ->get();

        return response()->json([
            'success' => true,
            'visits'  => $visits,
        ]);
    }

    // PATCH /admin/visits/{id}/notes
    // Updates just the notes field on a single schedule_visit row.
    // Used by the editable notes field on the patient "View" sheet.
    public function updateVisitNotes(Request $request, $id)
    {
        $visit = DB::table('schedule_visit')->where('visit_id', $id)->first();
        if (!$visit) {
            return response()->json(['success' => false, 'message' => 'Visit not found.'], 404);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        DB::table('schedule_visit')
            ->where('visit_id', $id)
            ->update(['notes' => $validated['notes'] ?? null]);

        return response()->json(['success' => true, 'message' => 'Note updated.']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_fname'     => 'required|string|max:100',
            'patient_lname'     => 'required|string|max:100',
            'patient_birthdate' => 'nullable|date',
            'patient_email'     => 'nullable|email|max:255',
            'patient_contact'   => 'nullable|string|max:20',
        ]);

        $patient = PatientList::create($validated);

        return response()->json(['success' => true, 'patient' => $patient], 201);
    }

    public function update(Request $request, $id)
    {
        $patient = PatientList::find($id);
        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found.'], 404);
        }

        $validated = $request->validate([
            'patient_fname'     => 'required|string|max:100',
            'patient_lname'     => 'required|string|max:100',
            'patient_birthdate' => 'nullable|date',
            'patient_email'     => 'nullable|email|max:255',
            'patient_contact'   => 'nullable|string|max:20',
        ]);

        $patient->update($validated);

        return response()->json(['success' => true, 'patient' => $patient]);
    }

    // ─────────────────────────────────────────────────────────
    //  Web-only below — the admin Blade page's patient list.
    //  Nothing above this line is touched; mobile keeps using
    //  apiIndex/apiServiceTypes/apiVisits/updateVisitNotes/store/update
    //  exactly as before via api.php. store() and update() are reused
    //  as-is here too (their fields already match the web form), just
    //  reached through a separate web.php route.
    // ─────────────────────────────────────────────────────────

    // GET /admin_acc/patients/list
    // The `patients` table has no age/department/doctor columns — age is
    // derived from patient_birthdate, and department/doctor come from
    // whichever schedule_visit row is most recent for that patient (a
    // patient isn't tied to one department, so this is "most recent", not
    // "assigned").
    public function webList()
    {
        $patients = PatientList::orderBy('patient_lname')->get();

        $latestVisitByPatient = DB::table('schedule_visit')
            ->leftJoin('doctors', 'doctors.doctor_id', '=', 'schedule_visit.doctor_id')
            ->select('schedule_visit.patient_id', 'schedule_visit.service_type', 'schedule_visit.visit_date', 'doctors.doctor_name')
            ->orderByDesc('schedule_visit.visit_date')
            ->get()
            ->groupBy('patient_id')
            ->map(fn ($rows) => $rows->first()); // already ordered desc, so first() = most recent

        $data = $patients->map(function ($patient) use ($latestVisitByPatient) {
            $latest = $latestVisitByPatient->get($patient->patient_id);

            return [
                'id' => $patient->patient_id,
                'first_name' => $patient->patient_fname,
                'last_name' => $patient->patient_lname,
                'name' => trim($patient->patient_fname . ' ' . $patient->patient_lname),
                'birthdate' => $patient->patient_birthdate,
                'age' => $patient->patient_birthdate ? \Carbon\Carbon::parse($patient->patient_birthdate)->age : null,
                'department' => $latest->service_type ?? null,
                'doctor' => $latest->doctor_name ?? null,
                'email' => $patient->patient_email,
                'phone' => $patient->patient_contact,
            ];
        });

        return response()->json(['success' => true, 'patients' => $data]);
    }
}