<?php

namespace App\Http\Controllers;

use App\Models\PatientDoctor;

class PatientDoctorController extends Controller
{
    // GET /api/doctors — used by the patient-facing website
    public function index()
    {
        $doctors = PatientDoctor::where('available', 1)
            ->get()
            ->map(function ($doctor) {
                return [
                    'doctor_id'        => $doctor->doctor_id,
                    'doctor_name'      => $doctor->doctor_name,
                    'specialty'        => $doctor->specialty,
                    'gender'           => $doctor->gender,
                    'years_experience' => $doctor->years_experience,
                    'education'        => $doctor->education,
                    'license'          => $doctor->license,
                    'fellowship'       => $doctor->fellowship,
                    'description'      => $doctor->description,
                    'clinic_room'      => $doctor->clinic_room,
                    'profile_image'    => $doctor->profile_image,
                    'available'        => $doctor->available,
                    'schedules'        => [], // placeholder for now
                ];
            });

        return response()->json($doctors);
    }
}