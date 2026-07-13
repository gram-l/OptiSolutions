<?php
// app/Http/Controllers/Api/PatientController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /** GET /api/patients */
    public function index()
    {
        $patients = Patient::with('latestVisit.doctor')->get();

        return response()->json($patients->map->toApiArray());
    }

    /** GET /api/patients/{patient} */
    public function show(Patient $patient)
    {
        $patient->load('latestVisit.doctor');

        return response()->json($patient->toApiArray());
    }

    /** PATCH /api/patients/{patient} — used by the "edit patient" feature in patients.dart */
    public function update(Request $request, Patient $patient)
    {
        $incoming = $request->validate([
            'name'       => 'sometimes|string',
            'department' => 'sometimes|string',
            'doctor'     => 'sometimes|string',
            'contact'    => 'sometimes|string',
            'notes'      => 'sometimes|string',
        ]);

        $data = [];
        if (isset($incoming['name'])) {
            $parts = preg_split('/\s+/', trim($incoming['name']), 2);
            $data['patient_fname'] = $parts[0] ?? '';
            $data['patient_lname'] = $parts[1] ?? '';
        }
        if (isset($incoming['contact'])) {
            $data['patient_contact'] = $incoming['contact'];
        }

        if (!empty($data)) {
            $patient->update($data);
        }

        return response()->json($patient->fresh('latestVisit.doctor')->toApiArray());
    }
}