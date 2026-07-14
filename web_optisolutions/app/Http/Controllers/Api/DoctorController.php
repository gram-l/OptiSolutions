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

        $result = $doctors->map(function ($doctor) {
            return $doctor->toApiArray();
        })->values();

        return response()->json($result);
    }

    /** GET /api/doctors/{doctor} */
    public function show(Doctor $doctor)
    {
        $doctor->load('schedules');

        return response()->json($doctor->toApiArray());
    }

    /** PATCH /api/doctors/{doctor} */
    public function update(Request $request, Doctor $doctor)
    {
        $incoming = $request->validate([
            'status'   => 'sometimes|in:Available,Unavailable',
            'schedule' => 'sometimes|string',
            'time'     => 'sometimes|string',
        ]);

        if (isset($incoming['status'])) {
            $doctor->available = $incoming['status'] === 'Available';
            $doctor->save();
        }

        if (isset($incoming['schedule']) || isset($incoming['time'])) {
            $days = $this->parseDayRange($incoming['schedule'] ?? null, $doctor);
            [$startTime, $endTime] = $this->parseTimeRange($incoming['time'] ?? null, $doctor);

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

        return array_map($normalize, array_map('trim', explode(',', $input)));
    }

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
                //
            }
        }

        return ['08:00:00', '17:00:00'];
    }
}