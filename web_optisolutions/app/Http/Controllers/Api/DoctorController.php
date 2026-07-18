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

    /**
     * PATCH /api/doctors/{doctor} — used by the "edit availability" / "edit schedule"
     * features in doctors.dart.
     *
     * Expected body for schedule updates:
     * {
     *   "schedules": [
     *     {"day": "Monday", "start_time": "08:00", "end_time": "11:00"},
     *     {"day": "Monday", "start_time": "14:00", "end_time": "18:00"},
     *     {"day": "Tuesday", "start_time": "08:00", "end_time": "17:00"}
     *   ]
     * }
     *
     * Each entry is one row in `doctor_schedules`, so a single day can have
     * multiple sessions (e.g. split shifts) by simply repeating the day.
     * Sending "schedules": [] clears all schedules for the doctor.
     */
    public function update(Request $request, Doctor $doctor)
    {
        $incoming = $request->validate([
            'status'                    => 'sometimes|in:Available,Unavailable',
            'schedules'                 => 'sometimes|array',
            'schedules.*.day'           => 'required_with:schedules|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'schedules.*.start_time'    => 'nullable|date_format:H:i',
            'schedules.*.end_time'      => 'nullable|date_format:H:i',
        ]);

        // Ang 'available' column sa DB ay boolean, hindi 'Available'/'Unavailable' string
        if (isset($incoming['status'])) {
            $doctor->available = $incoming['status'] === 'Available';
            $doctor->save();
        }

        // ✅ 'available' huwag payagang baguhin ang schedule ng doktor kung
        // in-mark siyang Unavailable ng admin (available = 0). Ito ay backup
        // lang sa server side; ang pangunahing pigil ay nasa staff app
        // (grayed-out button).
        if (isset($incoming['schedules']) && !$doctor->available) {
            return response()->json([
                'message' => "You cannot change this doctor's schedule because the administrator marked the doctor as inactive.",
            ], 403);
        }

        // Palitan ang buong schedule set ng doktor kung may 'schedules' na ipinadala
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