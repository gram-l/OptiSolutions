<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class ScheduleVisit extends Model
{
    protected $table = 'schedule_visit';
    protected $primaryKey = 'visit_id';
    public $timestamps = false;

    protected $fillable = [
        'doctor_id', 'patient_id', 'service_type', 'visit_date', 'notes', 'scheduled_at',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    /**
     * ✅ Awtomatikong gumagawa ng staff notification tuwing may bagong
     * schedule visit na malikha (basta Eloquent create()/save() ang
     * ginamit sa paggawa nito).
     */
    protected static function booted()
    {
        static::created(function (ScheduleVisit $visit) {
            $patientName = $visit->patient->full_name ?? 'A patient';
            $doctorName = $visit->doctor->doctor_name ?? null;
            $dateStr = $visit->visit_date
                ? \Carbon\Carbon::parse($visit->visit_date)->format('M d, Y')
                : '';

            AppNotification::create([
                'icon' => 'calendar_today',
                'title' => 'New Schedule Visit',
                'message' => trim(
                    "{$patientName} scheduled a visit"
                    . ($doctorName ? " with Dr. {$doctorName}" : '')
                    . ($dateStr ? " on {$dateStr}" : '')
                    . '.'
                ),
                'is_read' => false,
                'color' => '4CAF50',
            ]);
        });
    }

    public function toApiArray()
    {
        return [
            'appointment_id' => $this->visit_id,
            'patient'        => $this->patient->full_name ?? 'N/A',
            // Ipalit sa 'doctor_name' kung iba ang column name sa Doctor model mo
            'doctor'         => $this->doctor->doctor_name ?? 'N/A',
            // Walang status column sa DB kaya default value na lang muna
            'status'         => 'Scheduled',
            'date'           => $this->visit_date,
        ];
    }
}