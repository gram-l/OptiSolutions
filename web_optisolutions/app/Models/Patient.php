<?php
// app/Models/Patient.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'patient_code', 'name', 'department', 'doctor_id',
        'birthday', 'contact', 'status', 'date_registered', 'notes',
    ];

    protected $casts = [
        'birthday' => 'date:Y-m-d',
        'date_registered' => 'date:Y-m-d',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    // Shapes the JSON output to match the field names your Flutter app already
    // expects (e.g. "id" instead of "patient_code", "doctor" as a name not an id).
    public function toApiArray(): array
    {
        return [
            'id' => $this->patient_code,
            'name' => $this->name,
            'department' => $this->department,
            'doctor' => $this->doctor?->name,
            'birthday' => $this->birthday?->format('Y-m-d'),
            'contact' => $this->contact,
            'status' => $this->status,
            'dateRegistered' => $this->date_registered?->format('Y-m-d'),
            'notes' => $this->notes,
        ];
    }
}