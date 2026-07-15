<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_models\Doctor;
use App\Models\admin_models\DoctorSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DoctorController extends Controller
{
    // Used by the Blade admin page
    public function index()
    {
        $doctors = Doctor::with('schedules')->get()->map(function ($doctor) {
            return [
                'id' => $doctor->doctor_id,
                'name' => $doctor->doctor_name,
                'specialty' => $doctor->specialty,
                'schedule' => $this->formatSchedule($doctor->schedules),
                'description' => $doctor->description,
                'phone' => $doctor->contact_number,
                'active' => $doctor->status === 'Active',
            ];
        });

        return view('admin_acc.doctors', compact('doctors'));
    }

    // GET /api/doctors — used by Flutter
    public function apiIndex()
    {
        $doctors = Doctor::with('schedules')->get()->map(function ($doctor) {
            $doctor->schedule = $this->formatSchedule($doctor->schedules);
            return $doctor;
        });

        return response()->json([
            'success' => true,
            'doctors' => $doctors,
        ]);
    }

    // POST /api/doctors
    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_name'     => 'required|string|max:255',
            'specialty'       => 'required|string|max:255',
            'schedule'        => 'required|string|max:255',
            'description'     => 'nullable|string',
            'contact_number'  => 'nullable|string|max:20',
            'status'          => 'nullable|string|in:Active,Inactive',
        ]);

        $validated['status'] = $validated['status'] ?? 'Active';

        $doctor = Doctor::create($validated);

        $this->syncScheduleRows($doctor, $validated['schedule']);

        $doctor->load('schedules');
        $doctor->schedule = $this->formatSchedule($doctor->schedules);

        return response()->json([
            'success' => true,
            'message' => 'Doctor added successfully.',
            'doctor'  => $doctor,
        ], 201);
    }

    // PUT /api/doctors/{id}
    public function update(Request $request, $id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor not found.'], 404);
        }

        $validated = $request->validate([
            'doctor_name'    => 'required|string|max:255',
            'specialty'      => 'required|string|max:255',
            'schedule'       => 'required|string|max:255',
            'description'    => 'nullable|string',
            'contact_number' => 'nullable|string|max:20',
        ]);

        $doctor->update($validated);

        $this->syncScheduleRows($doctor, $validated['schedule']);

        $doctor->load('schedules');
        $doctor->schedule = $this->formatSchedule($doctor->schedules);

        return response()->json([
            'success' => true,
            'message' => 'Doctor updated successfully.',
            'doctor'  => $doctor,
        ]);
    }

    // PATCH /api/doctors/{id}/toggle
    public function toggleStatus($id)
    {
        $doctor = Doctor::with('schedules')->find($id);
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor not found.'], 404);
        }

        $doctor->status = $doctor->status === 'Active' ? 'Inactive' : 'Active';
        $doctor->save();

        $doctor->schedule = $this->formatSchedule($doctor->schedules);

        return response()->json([
            'success' => true,
            'message' => "Doctor {$doctor->status} status updated.",
            'doctor'  => $doctor,
        ]);
    }

    // DELETE /api/doctors/{id}
    public function destroy($id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor not found.'], 404);
        }

        DoctorSchedule::where('doctor_id', $doctor->doctor_id)->delete();
        $doctor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Doctor removed successfully.',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    //  Schedule helpers — doctor_schedules is now the source of
    //  truth for what gets displayed on both admin and staff.
    // ─────────────────────────────────────────────────────────

    private const DAY_ORDER = [
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
    ];

    private const DAY_ABBREV = [
        'Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed',
        'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun',
    ];

    private const DAY_ALIASES = [
        'mon' => 'Monday', 'monday' => 'Monday',
        'tue' => 'Tuesday', 'tues' => 'Tuesday', 'tuesday' => 'Tuesday',
        'wed' => 'Wednesday', 'weds' => 'Wednesday', 'wednesday' => 'Wednesday',
        'thu' => 'Thursday', 'thur' => 'Thursday', 'thurs' => 'Thursday', 'thursday' => 'Thursday',
        'fri' => 'Friday', 'friday' => 'Friday',
        'sat' => 'Saturday', 'saturday' => 'Saturday',
        'sun' => 'Sunday', 'sunday' => 'Sunday',
    ];

    /**
     * Turn a set of doctor_schedules rows into a display string like
     * "Mon-Fri 9:00 AM - 5:00 PM, Sat 9:00 AM - 12:00 PM".
     */
    private function formatSchedule($schedules)
    {
        if (!$schedules || $schedules->isEmpty()) {
            return 'Schedule not set';
        }

        $sorted = $schedules->sortBy(function ($s) {
            return array_search($s->day, self::DAY_ORDER);
        })->values();

        $groups = [];
        foreach ($sorted as $s) {
            $timeKey = ($s->start_time ?? '') . '-' . ($s->end_time ?? '');
            $lastIndex = count($groups) - 1;

            if ($lastIndex >= 0) {
                $last = $groups[$lastIndex];
                $lastDayIndex = array_search(end($last['days']), self::DAY_ORDER);
                $thisDayIndex = array_search($s->day, self::DAY_ORDER);

                if ($last['timeKey'] === $timeKey && $thisDayIndex === $lastDayIndex + 1) {
                    $groups[$lastIndex]['days'][] = $s->day;
                    continue;
                }
            }

            $groups[] = [
                'timeKey' => $timeKey,
                'days' => [$s->day],
                'start' => $s->start_time,
                'end' => $s->end_time,
            ];
        }

        $parts = [];
        foreach ($groups as $g) {
            $days = $g['days'];
            $dayLabel = count($days) > 1
                ? self::DAY_ABBREV[$days[0]] . '-' . self::DAY_ABBREV[end($days)]
                : self::DAY_ABBREV[$days[0]];

            $timeLabel = '';
            if ($g['start'] && $g['end']) {
                $timeLabel = ' ' . $this->formatTime($g['start']) . ' - ' . $this->formatTime($g['end']);
            }

            $parts[] = $dayLabel . $timeLabel;
        }

        return implode(', ', $parts);
    }

    private function formatTime($time)
    {
        try {
            return Carbon::createFromFormat('H:i:s', $time)->format('g:i A');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($time)->format('g:i A');
            } catch (\Exception $e2) {
                return $time;
            }
        }
    }

    /**
     * Replace a doctor's doctor_schedules rows based on the free-text
     * "schedule" field the admin form submits, e.g.:
     *   "Monday-Friday 9:00 AM - 5:00 PM"
     *   "Mon, Wed, Fri 10:00 AM - 2:00 PM; Sat 9:00 AM - 12:00 PM"
     */
    private function syncScheduleRows(Doctor $doctor, $scheduleText)
    {
        $rows = $this->parseScheduleToRows($scheduleText);

        DoctorSchedule::where('doctor_id', $doctor->doctor_id)->delete();

        foreach ($rows as $row) {
            DoctorSchedule::create([
                'doctor_id'  => $doctor->doctor_id,
                'day'        => $row['day'],
                'start_time' => $row['start_time'],
                'end_time'   => $row['end_time'],
            ]);
        }
    }

    private function parseScheduleToRows($scheduleText)
    {
        $rows = [];
        $segments = preg_split('/[;|]/', $scheduleText);

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            $timePattern = '/(\d{1,2}(:\d{2})?\s*(AM|PM|am|pm)?)\s*-\s*(\d{1,2}(:\d{2})?\s*(AM|PM|am|pm)?)/';
            $startTime = null;
            $endTime = null;
            $daysPart = $segment;

            if (preg_match($timePattern, $segment, $m)) {
                $startTime = $this->parseTimeString(trim($m[1]));
                $endTime = $this->parseTimeString(trim($m[4]));
                $daysPart = trim(str_replace($m[0], '', $segment));
                $daysPart = trim($daysPart, " ,-");
            }

            $days = [];
            if (preg_match('/([A-Za-z]+)\s*-\s*([A-Za-z]+)/', $daysPart, $rangeMatch)) {
                $startDay = self::DAY_ALIASES[strtolower($rangeMatch[1])] ?? null;
                $endDay = self::DAY_ALIASES[strtolower($rangeMatch[2])] ?? null;

                if ($startDay && $endDay) {
                    $startIdx = array_search($startDay, self::DAY_ORDER);
                    $endIdx = array_search($endDay, self::DAY_ORDER);

                    if ($startIdx <= $endIdx) {
                        $days = array_slice(self::DAY_ORDER, $startIdx, $endIdx - $startIdx + 1);
                    } else {
                        $days = array_merge(
                            array_slice(self::DAY_ORDER, $startIdx),
                            array_slice(self::DAY_ORDER, 0, $endIdx + 1)
                        );
                    }
                }
            } else {
                $tokens = preg_split('/[,\/&]+/', $daysPart);
                foreach ($tokens as $token) {
                    $token = strtolower(trim($token));
                    if (isset(self::DAY_ALIASES[$token])) {
                        $days[] = self::DAY_ALIASES[$token];
                    }
                }
            }

            foreach ($days as $day) {
                $rows[] = [
                    'day' => $day,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ];
            }
        }

        return $rows;
    }

    private function parseTimeString($time)
    {
        $time = trim($time);
        if ($time === '') {
            return null;
        }

        $formats = ['g:i A', 'g:iA', 'g A', 'gA', 'H:i', 'H:i:s'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, strtoupper($time))->format('H:i:s');
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($time)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}