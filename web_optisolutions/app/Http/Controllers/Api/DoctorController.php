<?php
// app/Http/Controllers/Api/DoctorController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /** GET /api/doctors */
    public function index()
    {
        $doctors = Doctor::with('schedules')->get();

        return response()->json($doctors->map->toApiArray());
    }

    /** GET /api/doctors/{doctor} */
    public function show(Doctor $doctor)
    {
        $doctor->load('schedules');

        return response()->json($doctor->toApiArray());
    }

    public function update(Request $request, Doctor $doctor)
    {
        $incoming = $request->validate([
            'status'                    => 'sometimes|in:Available,Unavailable',
            'schedules'                 => 'sometimes|array',
            'schedules.*.day'           => 'required_with:schedules|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'schedules.*.start_time'    => 'nullable|date_format:H:i',
            'schedules.*.end_time'      => 'nullable|date_format:H:i',
        ]);

    
        if (isset($incoming['status'])) {
            $doctor->available = $incoming['status'] === 'Available';
            $doctor->save();
        }

        if (isset($incoming['schedules']) && !$doctor->available) {
            return response()->json([
                'message' => "You cannot change this doctor's schedule because the administrator marked the doctor as inactive.",
            ], 403);
        }


        if (isset($incoming['schedules'])) {
            DoctorSchedule::where('doctor_id', $doctor->doctor_id)->delete();

            foreach ($incoming['schedules'] as $entry) {
                DoctorSchedule::create([
                    'doctor_id'  => $doctor->doctor_id,
                    'day'        => $entry['day'],
                    'start_time' => $entry['start_time'] ?? null,
                    'end_time'   => $entry['end_time'] ?? null,
                ]);
            }
        }

        return response()->json($doctor->fresh('schedules')->toApiArray());
    }
}