<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index()
    {
        $patients = Patient::all();
        return view('staff.patients.index', compact('patients'));
    }

    public function show($id)
    {
        $patient = Patient::findOrFail($id);
        return view('staff.patients.show', compact('patient'));
    }

    public function edit($id)
    {
        $patient = Patient::findOrFail($id);
        return view('staff.patients.edit', compact('patient'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'patient_name' => 'required',
            'department' => 'required',
            'assigned_doctor' => 'required',
            'birthday' => 'required|date',
            'contact_number' => 'required',
        ]);

        $patient = Patient::findOrFail($id);
        $patient->update($request->all());

        return redirect()->route('staff.patients')->with('success', 'Patient updated successfully!');
    }
}