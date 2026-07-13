<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Staff\Doctor;
class DoctorSchedule extends Model
{
    protected $table = 'doctor_schedules';
    protected $primaryKey = 'schedule_id';
    public $timestamps = false;

    protected $fillable = [
        'doctor_id', 'day', 'start_time', 'end_time',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }
}