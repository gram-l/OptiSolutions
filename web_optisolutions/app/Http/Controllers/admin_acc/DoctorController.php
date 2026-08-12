<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_models\Doctor;
use App\Models\admin_models\DoctorSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class DoctorController extends Controller
{
    // Used by the Blade admin page (initial render)
    public function index()
    {
        $doctors = $this->doctorsForWeb();

        return view('admin_acc.doctors', compact('doctors'));
    }

    // GET /admin_acc/doctors/list — JSON refresh for the web admin grid.
    // Web-only counterpart to apiIndex(); same field shape as index() above
    // (name/phone/active) rather than the raw model shape Flutter gets.
    public function listJson()
    {
        return response()->json([
            'success' => true,
            'doctors' => $this->doctorsForWeb(),
        ]);
    }

    private function doctorsForWeb()
    {
        return Doctor::with('schedules')->get()->map(function ($doctor) {
            return [
                'id' => $doctor->doctor_id,
                'name' => $doctor->doctor_name,
                'specialty' => $doctor->specialty,
                'schedule' => $this->formatSchedule($doctor->schedules),
                'schedule_sessions' => $this->sessionsForApi($doctor->schedules),
                'description' => $doctor->description,
                'phone' => $doctor->contact_number,
                'profile_image_url' => $this->profileImageUrl($doctor->profile_image),
                'active' => $doctor->status === 'Active',
            ];
        });
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
            $doctor->profile_image_url = $this->profileImageUrl($doctor->profile_image);
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

        $doctorData = collect($validated)->except(['schedule_sessions', 'profile_image'])->toArray();
        $doctorData['status'] = $doctorData['status'] ?? 'Active';

        if ($request->hasFile('profile_image')) {
            $doctorData['profile_image'] = $request->file('profile_image')->store('doctors', 'public');
        }

        $doctor = Doctor::create($doctorData);

        $this->syncScheduleRows($doctor, $validated['schedule_sessions']);

        $doctor->load('schedules');
        $doctor->schedule = $this->formatSchedule($doctor->schedules);
        $doctor->schedule_sessions = $this->sessionsForApi($doctor->schedules);
        $doctor->profile_image_url = $this->profileImageUrl($doctor->profile_image);

        return response()->json([
            'success' => true,
            'message' => 'Doctor added successfully.',
            'doctor'  => $doctor,
        ], 201);
    }

    // PUT /api/doctors/{id}  (submitted as POST + _method=PUT so file uploads work)
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

        $doctorData = collect($validated)->except(['schedule_sessions', 'profile_image'])->toArray();

        if ($request->hasFile('profile_image')) {
            // Replacing an existing photo — remove the old file so uploads
            // don't pile up on disk.
            if ($doctor->profile_image) {
                Storage::disk('public')->delete($doctor->profile_image);
            }
            $doctorData['profile_image'] = $request->file('profile_image')->store('doctors', 'public');
        }

        $doctor->update($doctorData);

        $this->syncScheduleRows($doctor, $validated['schedule_sessions']);

        $doctor->load('schedules');
        $doctor->schedule = $this->formatSchedule($doctor->schedules);
        $doctor->schedule_sessions = $this->sessionsForApi($doctor->schedules);
        $doctor->profile_image_url = $this->profileImageUrl($doctor->profile_image);

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
        $doctor->profile_image_url = $this->profileImageUrl($doctor->profile_image);

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

        if ($doctor->profile_image) {
            Storage::disk('public')->delete($doctor->profile_image);
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
        // The web admin page submits multipart/form-data (so the photo file
        // can ride along), and multipart bodies can't carry nested arrays
        // natively — schedule_sessions arrives as a JSON string in that
        // case. The Flutter app still posts it as a real JSON array, so
        // only decode when it's actually a string.
        $input = $request->all();
        if (isset($input['schedule_sessions']) && is_string($input['schedule_sessions'])) {
            $decoded = json_decode($input['schedule_sessions'], true);
            $input['schedule_sessions'] = is_array($decoded) ? $decoded : [];
            $request->merge(['schedule_sessions' => $input['schedule_sessions']]);
        }

        $validator = Validator::make($input, [
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
            'profile_image'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

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
            'profile_image.image' => 'The photo must be an image file.',
            'profile_image.mimes' => 'The photo must be a JPG, PNG, or WEBP file.',
            'profile_image.max' => 'The photo must not be larger than 2MB.',
            'schedule_sessions.required' => 'Add at least one schedule session.',
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

    /**
     * Resolves a stored "doctors/xxx.jpg" path (from the public disk) into
     * a browser-usable URL, or null if the doctor has no photo. Requires
     * `php artisan storage:link` to have been run so /storage points at
     * storage/app/public.
     *
     * Deliberately uses asset() instead of Storage::disk('public')->url().
     * Storage::url() builds the URL from APP_URL in .env, so it breaks
     * (ERR_CONNECTION_REFUSED) the moment the app is viewed from a
     * different host/port than whatever APP_URL happens to be set to.
     * asset() instead derives the host from the actual incoming request,
     * so it matches the browser's address bar no matter which machine
     * or port the app is running on.
     */
    private function profileImageUrl($path)
    {
        return $path ? asset('storage/' . $path) : null;
    }

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