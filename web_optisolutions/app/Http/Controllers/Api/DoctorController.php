<?php
// app/Http/Controllers/Api/DoctorController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;

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

    /** PATCH /api/doctors/{doctor} — used by the "edit availability" / "edit schedule" features in doctors.dart */
    public function update(Request $request, Doctor $doctor)
    {
        $incoming = $request->validate([
            'status'   => 'sometimes|in:Available,Unavailable',
            'schedule' => 'sometimes|string', // hal. "Mon - Fri"
            'time'     => 'sometimes|string', // hal. "8:00am - 3:00pm"
        ]);

        // Ang 'available' column sa DB ay boolean, hindi 'Available'/'Unavailable' string
        if (isset($incoming['status'])) {
            $doctor->available = $incoming['status'] === 'Available';
            $doctor->save();
        }

        // I-update ang schedule kung binigyan ng bago
        if (isset($incoming['schedule']) || isset($incoming['time'])) {
            $days = $this->parseDayRange($incoming['schedule'] ?? null, $doctor);
            [$startTime, $endTime] = $this->parseTimeRange($incoming['time'] ?? null, $doctor);

            // Palitan ang lumang schedule rows ng doktor ng bagong set
            DoctorSchedule::where('doctor_id', $doctor->doctor_id)->delete();

            foreach ($days as $day) {
                DoctorSchedule::create([
                    'doctor_id'  => $doctor->doctor_id,
                    'day'        => $day,
                    'start_time' => $startTime,
                    'end_time'   => $endTime,
                ]);
            }
        }

        return response()->json($doctor->fresh('schedules')->toApiArray());
    }

    /**
     * I-parse ang "Mon - Fri" o "Monday - Friday" papunta sa listahan ng
     * indibidwal na araw. Kung hindi ma-parse, panatilihin ang dating days.
     */
    private function parseDayRange(?string $input, Doctor $doctor): array
    {
        $weekOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $aliases = ['Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday'];

        if (!$input) {
            $existing = $doctor->schedules->pluck('day')->unique()->values()->all();
            return !empty($existing) ? $existing : ['Monday'];
        }

        $parts = array_map('trim', explode('-', $input));
        $normalize = fn($d) => $aliases[$d] ?? $d;

        if (count($parts) === 2) {
            $start = $normalize($parts[0]);
            $end = $normalize($parts[1]);
            $startIdx = array_search($start, $weekOrder);
            $endIdx = array_search($end, $weekOrder);

            if ($startIdx !== false && $endIdx !== false) {
                if ($startIdx <= $endIdx) {
                    return array_slice($weekOrder, $startIdx, $endIdx - $startIdx + 1);
                }
                return array_merge(array_slice($weekOrder, $startIdx), array_slice($weekOrder, 0, $endIdx + 1));
            }
        }

        // Hindi ma-parse bilang range — ituring na single day o list na pinaghiwalay ng comma
        return array_map($normalize, array_map('trim', explode(',', $input)));
    }

    /**
     * I-parse ang "8:00am - 3:00pm" papunta sa ['08:00:00', '15:00:00'].
     * Kung hindi ma-parse, panatilihin ang dating oras.
     */
    private function parseTimeRange(?string $input, Doctor $doctor): array
    {
        if (!$input) {
            $existing = $doctor->schedules->first();
            return $existing
                ? [$existing->start_time, $existing->end_time]
                : ['08:00:00', '17:00:00'];
        }

        $parts = array_map('trim', explode('-', $input));
        if (count($parts) === 2) {
            try {
                return [
                    Carbon::parse($parts[0])->format('H:i:s'),
                    Carbon::parse($parts[1])->format('H:i:s'),
                ];
            } catch (\Exception $e) {
                // babagsak sa default sa ibaba
            }
        }

        return ['08:00:00', '17:00:00'];
    }
}