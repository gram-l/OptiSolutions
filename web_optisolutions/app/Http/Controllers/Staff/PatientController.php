<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index()
    {
        $patients = Patient::with('latestVisit.doctor')->get();
        return view('staff.patients.index', compact('patients'));
    }

    public function show($id)
    {
        $patient = Patient::with(['latestVisit.doctor', 'visits.doctor'])->findOrFail($id);
        return view('staff.patients.show', compact('patient'));
    }

    public function edit($id)
    {
        $patient = Patient::findOrFail($id);
        return view('staff.patients.edit', compact('patient'));
    }

    public function update(Request $request, $id)
    {
        // Department at Assigned Doctor ay hindi na dito ini-edit —
        // galing 'yan sa schedule_visit table, hindi sa patients table mismo
        $request->validate([
            'patient_fname'    => 'required|string',
            'patient_lname'    => 'required|string',
            'patient_birthdate'=> 'required|date',
            'patient_email'    => 'required|email',
            'patient_contact'  => 'required|string',
        ]);

        $patient = Patient::findOrFail($id);
        $patient->update($request->only([
            'patient_fname', 'patient_lname', 'patient_birthdate', 'patient_email', 'patient_contact',
        ]));

        return redirect()->route('staff.patients')->with('success', 'Patient updated successfully!');
    }
}