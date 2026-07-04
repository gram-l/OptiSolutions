<?php
// app/Http/Controllers/Api/DoctorController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /** GET /api/doctors */
    public function index()
    {
        return Doctor::all();
    }

    /** GET /api/doctors/{doctor} */
    public function show(Doctor $doctor)
    {
        return $doctor;
    }

    /** PATCH /api/doctors/{doctor} — used by the "edit availability" feature in doctors.dart */
    public function update(Request $request, Doctor $doctor)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:Available,Unavailable',
            'schedule' => 'sometimes|string',
            'time' => 'sometimes|string',
        ]);

        $doctor->update($data);

        return $doctor;
    }
}