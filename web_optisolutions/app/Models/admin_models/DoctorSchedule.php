<?php

namespace App\Models\admin_models;

use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    protected $table = 'doctor_schedules';
    protected $primaryKey = 'schedule_id';
    public $timestamps = false;

    protected $fillable = [
        'doctor_id',
        'day',
        'start_time',
        'end_time',
    ];
}