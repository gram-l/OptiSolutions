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
     * time, avatar, color
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

        return [
            'id'        => $this->doctor_id,
            'name'      => $this->doctor_name ?? 'Unknown',
            'specialty' => $this->specialty ?? 'General',
            'status'    => $this->available ? 'Available' : 'Unavailable',
            'schedule'  => $scheduleStr,
            'time'      => $timeStr,
            'avatar'    => $initials ?: 'DR',
            'color'     => '#1A237E',
        ];
    }
}