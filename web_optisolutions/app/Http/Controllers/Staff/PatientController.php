<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Patient;
use App\Models\Staff\ScheduleVisit;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::with('latestVisit.doctor');


        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->whereHas('visits', function ($q) use ($request) {
                if ($request->filled('date_from')) {
                    $q->whereDate('visit_date', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $q->whereDate('visit_date', '<=', $request->date_to);
                }
            });
        }

        $patients = $query->get();

        return view('staff.patients.index', compact('patients'));
    }

    public function show($id)
    {
        $patient = Patient::with(['latestVisit.doctor', 'visits.doctor'])->findOrFail($id);
        return view('staff.patients.show', compact('patient'));
    }

    public function edit($id)
    {
        $patient = Patient::with('latestVisit')->findOrFail($id);
        return view('staff.patients.edit', compact('patient'));
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'patient_fname'    => 'required|string',
            'patient_lname'    => 'required|string',
            'patient_birthdate'=> 'required|date',
            'patient_email'    => 'required|email',
            'patient_contact'  => 'required|string',
            'visit_id'         => 'nullable|exists:schedule_visit,visit_id',
            'notes'            => 'nullable|string',
        ]);

        $patient = Patient::findOrFail($id);
        $patient->update($request->only([
            'patient_fname', 'patient_lname', 'patient_birthdate', 'patient_email', 'patient_contact',
        ]));

        // ---- Notes: nakatira sa schedule_visit table, hindi sa patients table ----
        if ($request->filled('visit_id')) {
            ScheduleVisit::where('visit_id', $request->visit_id)
                ->update(['notes' => $request->notes]);
        }

        return redirect()->route('staff.patients')->with('success', 'Patient updated successfully!');
    }
}