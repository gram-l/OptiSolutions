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
        return Patient::all()->map->toApiArray();
    }

    /** GET /api/patients/{patient} */
    public function show(Patient $patient)
    {
        return $patient->toApiArray();
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

        // I-map ang mga pangalan mula sa Flutter papunta sa totoong column
        // names sa database (halimbawa: 'name' -> 'patient_name')
        $data = [];
        if (isset($incoming['name']))       $data['patient_name']    = $incoming['name'];
        if (isset($incoming['department'])) $data['department']      = $incoming['department'];
        if (isset($incoming['doctor']))     $data['assigned_doctor'] = $incoming['doctor'];
        if (isset($incoming['contact']))    $data['phone']           = $incoming['contact'];

        $patient->update($data);

        return $patient->toApiArray();
    }
}