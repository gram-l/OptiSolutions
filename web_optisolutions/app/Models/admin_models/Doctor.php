<?php

namespace App\Models\admin_models;

use Illuminate\Database\Eloquent\Model;


class Doctor extends Model
{
    protected $table = 'doctors';

    protected $primaryKey = 'doctor_id';

    public $timestamps = false; 

    protected $fillable = [
        'doctor_name',
        'specialty',
        'status',
        'description',
        'schedule',
        'contact_number'
    ];

    public function schedules()
{
    return $this->hasMany(\App\Models\admin_models\DoctorSchedule::class, 'doctor_id', 'doctor_id');
}
}