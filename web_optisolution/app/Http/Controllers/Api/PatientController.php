<?php
// app/Http/Controllers/Api/PatientController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /** GET /api/patients */
    public function index()
    {
        return Patient::with('doctor')->get()->map->toApiArray();
    }

    /** GET /api/patients/{patient} — note: route uses the numeric id, see api.php */
    public function show(Patient $patient)
    {
        return $patient->load('doctor')->toApiArray();
    }

    /** PATCH /api/patients/{patient} — used by the "edit patient" feature in patients.dart */
    public function update(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'department' => 'sometimes|string',
            'contact' => 'sometimes|string',
            'status' => 'sometimes|in:Active,Inactive',
            'notes' => 'sometimes|string',
        ]);

        $patient->update($data);

        return $patient->load('doctor')->toApiArray();
    }
}