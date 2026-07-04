<?php
// app/Models/Appointment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'appointment_code', 'patient_id', 'doctor_id', 'department', 'date', 'status',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    // Matches the field names your appointments.dart already uses
    public function toApiArray(): array
    {
        return [
            'id' => $this->appointment_code,
            'patientName' => $this->patient?->name,
            'doctor' => $this->doctor?->name,
            'department' => $this->department,
            'date' => $this->date?->format('Y-m-d'),
        ];
    }
}