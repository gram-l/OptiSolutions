<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleVisit extends Model
{
    use HasFactory;

    protected $table = 'schedule_visit';
    protected $primaryKey = 'visit_id';

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'service_type',
        'visit_date',
        'status',
        'notes',
    ];
}