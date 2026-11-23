<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleVisit extends Model
{
    protected $table = 'schedule_visit';
    protected $primaryKey = 'visit_id';

    const CREATED_AT = 'scheduled_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'doctor_id', 'patient_id', 'service_type', 'visit_date', 'notes',
        'visit_time', 'visit_end_time', 'status',
    ];

    protected $casts = ['visited' => 'boolean', 'visited_at' => 'datetime'];
    protected $hidden = ['review_pass_code', 'review_pass_hash'];
}