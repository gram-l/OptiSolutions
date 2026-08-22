<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_models\Doctor;
use App\Models\admin_models\DoctorSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
                'schedule_sessions' => $this->sessionsForApi($doctor->schedules),
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
            // 'schedule' is a human-readable display string for the list card.
            $doctor->schedule = $this->formatSchedule($doctor->schedules);
            // 'schedule_sessions' is the structured source of truth the edit
            // form reads from directly — no text parsing involved.
            $doctor->schedule_sessions = $this->sessionsForApi($doctor->schedules);
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
        $validated = $this->validateDoctorRequest($request);

        $doctorData = collect($validated)->except('schedule_sessions')->toArray();
        $doctorData['status'] = $doctorData['status'] ?? 'Active';

        $doctor = Doctor::create($doctorData);

        $this->syncScheduleRows($doctor, $validated['schedule_sessions']);

        $doctor->load('schedules');
        $doctor->schedule = $this->formatSchedule($doctor->schedules);
        $doctor->schedule_sessions = $this->sessionsForApi($doctor->schedules);

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

        // Pass the doctor's own id so the uniqueness check on doctor_name
        // ignores this row (otherwise saving without changing the name
        // would incorrectly flag itself as a duplicate).
        $validated = $this->validateDoctorRequest($request, $doctor->doctor_id);

        $doctorData = collect($validated)->except('schedule_sessions')->toArray();
        $doctor->update($doctorData);

        $this->syncScheduleRows($doctor, $validated['schedule_sessions']);

        $doctor->load('schedules');
        $doctor->schedule = $this->formatSchedule($doctor->schedules);
        $doctor->schedule_sessions = $this->sessionsForApi($doctor->schedules);

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
        $doctor->schedule_sessions = $this->sessionsForApi($doctor->schedules);

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
    //  Validation
    // ─────────────────────────────────────────────────────────

    /**
     * Shared validation for store/update. Schedule now arrives as a
     * structured array of sessions instead of a free-text string:
     *   "schedule_sessions": [
     *     {"day": "Monday", "start_time": "08:00", "end_time": "17:00"},
     *     {"day": "Monday", "start_time": "18:00", "end_time": "20:00"}
     *   ]
     *
     * $doctorId is passed on update() so the doctor_name uniqueness check
     * ignores the row being edited.
     */
    private function validateDoctorRequest(Request $request, $doctorId = null)
    {
        $validator = Validator::make($request->all(), [
            'doctor_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                // MySQL's default utf8mb4_general_ci collation already compares
                // case-insensitively, so this catches "Dr. Smith" vs "dr. smith" too.
                Rule::unique('doctors', 'doctor_name')->ignore($doctorId, 'doctor_id'),
            ],
            'specialty'       => 'required|string|max:60',
            'description'     => 'nullable|string|max:500',
            'contact_number'  => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'],
            'status'          => 'nullable|string|in:Active,Inactive',

            'schedule_sessions'               => 'required|array|min:1',
            'schedule_sessions.*.day'         => 'required|in:' . implode(',', self::DAY_ORDER),
            'schedule_sessions.*.start_time'  => 'required|date_format:H:i',
            'schedule_sessions.*.end_time'    => 'required|date_format:H:i|after:schedule_sessions.*.start_time',
        ], [
            'doctor_name.unique' => 'A doctor with this name already exists.',
            'doctor_name.min' => 'Name is too short.',
            'doctor_name.max' => 'Name is too long (max 100 characters).',
            'specialty.max' => 'Specialty is too long (max 60 characters).',
            'description.max' => 'Description is too long (max 500 characters).',
            'contact_number.regex' => 'Enter a valid phone number (digits only, 7–15 digits).',
            'schedule_sessions.*.end_time.after' => 'End time must be after start time.',
        ]);

        // Cross-item check Laravel's built-in rules can't express on their
        // own: no two sessions on the same day may overlap.
        $validator->after(function ($validator) use ($request) {
            $sessions = $request->input('schedule_sessions', []);

            $toMinutes = function (string $hhmm) {
                [$h, $m] = array_map('intval', explode(':', $hhmm));
                return $h * 60 + $m;
            };

            for ($i = 0; $i < count($sessions); $i++) {
                for ($j = $i + 1; $j < count($sessions); $j++) {
                    $a = $sessions[$i];
                    $b = $sessions[$j];

                    if (($a['day'] ?? null) !== ($b['day'] ?? null)) {
                        continue;
                    }
                    if (!isset($a['start_time'], $a['end_time'], $b['start_time'], $b['end_time'])) {
                        continue; // malformed rows are already caught by the rules above
                    }

                    $aStart = $toMinutes($a['start_time']);
                    $aEnd   = $toMinutes($a['end_time']);
                    $bStart = $toMinutes($b['start_time']);
                    $bEnd   = $toMinutes($b['end_time']);

                    if ($aStart < $bEnd && $bStart < $aEnd) {
                        $validator->errors()->add(
                            "schedule_sessions.$j.start_time",
                            "Sessions on {$a['day']} overlap. Please fix before saving."
                        );
                    }
                }
            }
        });

        // Throws a ValidationException (auto 422 JSON response, same as
        // $request->validate() did before) if anything above fails.
        return $validator->validate();
    }

    // ─────────────────────────────────────────────────────────
    //  Schedule helpers — doctor_schedules is the source of truth.
    //  "schedule" (string) is only ever a display value derived from it;
    //  "schedule_sessions" (array) is the structured value the app reads
    //  from and writes to. Nothing round-trips through free text anymore.
    // ─────────────────────────────────────────────────────────

    private const DAY_ORDER = [
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
    ];

    private const DAY_ABBREV = [
        'Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed',
        'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun',
    ];

    /**
     * Turn doctor_schedules rows into a display string like
     * "Mon-Fri 9:00 AM - 5:00 PM, Sat 9:00 AM - 12:00 PM" for list cards.
     * Purely cosmetic — never parsed back.
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
     * Structured rows for the edit form: [{day, start_time, end_time}, ...]
     * with times truncated to "H:i" (no seconds), ordered Monday -> Sunday.
     * This is what the Flutter app reads to populate sessions — no parsing.
     */
    private function sessionsForApi($schedules)
    {
        if (!$schedules) {
            return [];
        }

        return $schedules
            ->sortBy(fn ($s) => array_search($s->day, self::DAY_ORDER))
            ->values()
            ->map(function ($s) {
                return [
                    'day'        => $s->day,
                    'start_time' => $s->start_time ? substr($s->start_time, 0, 5) : null,
                    'end_time'   => $s->end_time ? substr($s->end_time, 0, 5) : null,
                ];
            })
            ->values();
    }

    /**
     * Replace a doctor's doctor_schedules rows with the structured
     * sessions submitted by the app. Each $session is expected to be
     * ['day' => 'Monday', 'start_time' => '08:00', 'end_time' => '17:00'].
     */
    private function syncScheduleRows(Doctor $doctor, array $sessions)
    {
        DoctorSchedule::where('doctor_id', $doctor->doctor_id)->delete();

        foreach ($sessions as $session) {
            DoctorSchedule::create([
                'doctor_id'  => $doctor->doctor_id,
                'day'        => $session['day'],
                'start_time' => $session['start_time'],
                'end_time'   => $session['end_time'],
            ]);
        }
    }
}