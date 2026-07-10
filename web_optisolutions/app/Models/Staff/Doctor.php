<?php
// app/Models/Doctor.php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable = [
        'name', 'specialty', 'status', 'schedule', 'time', 'avatar', 'color',
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}