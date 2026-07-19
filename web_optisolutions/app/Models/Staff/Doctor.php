<?php

namespace App\Models\Staff;

use App\Models\DoctorSchedule;
use App\Models\Staff\Appointment;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Doctor extends Model
{
    protected $table = 'doctors';
    protected $primaryKey = 'doctor_id';
    public $timestamps = false; // palitan sa true kung may created_at/updated_at ang table mo

    protected $fillable = [
        'doctor_name', 'gender', 'specialty', 'years_experience', 'education',
        'license', 'clinic_room', 'fellowship', 'description', 'profile_image', 'available',
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class, 'doctor_id', 'doctor_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'doctor_id', 'doctor_id');
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class, 'doctor_id', 'doctor_id');
    }

    /**
     * I-convert ang doctor data papunta sa array format na inaasahan ng
     * Flutter app (DoctorsPage): id, name, specialty, status, schedule,
     * time, avatar, color, schedules
     *
     * 'schedules' ay ang buong listahan ng sessions (isa-isa per row sa
     * doctor_schedules), ganito ang hugis ng bawat entry:
     *   {"day": "Monday", "start_time": "08:00", "end_time": "11:00"}
     * Ginagamit ito ng app para buuin ang checklist + multiple-sessions-
     * per-day na editor. 'schedule' at 'time' ay pinapanatili pa rin bilang
     * simpleng buod (summary) kung sakaling may ibang parte ng app na
     * gumagamit pa ng mga iyon.
     */
    public function toApiArray()
    {
        // Kunin ang initials para sa avatar, hal. "Juan Dela Cruz" -> "JD"
        $nameParts = preg_split('/\s+/', trim($this->doctor_name ?? ''));
        $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1) . substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));

        $scheduleStr = 'Not set';
        $timeStr = 'Not set';

        $schedules = $this->relationLoaded('schedules') ? $this->schedules : $this->schedules()->get();

        if ($schedules->isNotEmpty()) {
            // I-order ang days base sa normal na linggo (Mon -> Sun)
            $dayOrder = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
            $days = $schedules->pluck('day')->unique()
                ->sortBy(fn($d) => $dayOrder[$d] ?? 99)
                ->values();

            $scheduleStr = $days->count() > 1
                ? ($days->first() . ' - ' . $days->last())
                : $days->first();

            // Ipagpalagay na parehong oras kada araw; kunin na lang sa unang record
            $first = $schedules->first();
            if ($first->start_time && $first->end_time) {
                $timeStr = Carbon::parse($first->start_time)->format('g:ia')
                    . ' - ' . Carbon::parse($first->end_time)->format('g:ia');
            }
        }

        // ✅ NEW: buong listahan ng sessions (isa-isa), 24-hour "H:i" format
        // para madaling i-parse ng Flutter app pabalik sa TimeOfDay.
        $schedulesList = $schedules->map(function ($s) {
            return [
                'day'        => $s->day,
                'start_time' => $s->start_time ? Carbon::parse($s->start_time)->format('H:i') : null,
                'end_time'   => $s->end_time ? Carbon::parse($s->end_time)->format('H:i') : null,
            ];
        })->values();

        return [
            'id'        => $this->doctor_id,
            'name'      => $this->doctor_name ?? 'Unknown',
            'specialty' => $this->specialty ?? 'General',
            'status'    => $this->available ? 'Available' : 'Unavailable',
            'schedule'  => $scheduleStr,
            'time'      => $timeStr,
            'schedules' => $schedulesList,
            'is_active' => (int) $this->available,
            'avatar'    => $initials ?: 'DR',
            'color'     => '#1A237E',
        ];
    }
}