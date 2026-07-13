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