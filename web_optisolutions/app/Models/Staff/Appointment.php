<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $primaryKey = 'appointment_id';

    protected $fillable = [
        'patient', 'doctor', 'date', 'status', 'staff_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function toApiArray()
    {
        return [
            'appointment_id' => $this->appointment_id,
            'patient'        => $this->patient,
            'doctor'         => $this->doctor,
            'date'           => $this->date,
            'status'         => $this->status,
        ];
    }
}