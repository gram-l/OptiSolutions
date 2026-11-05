<?php

namespace App\Models\admin_models;

use Illuminate\Database\Eloquent\Model;


class Doctor extends Model
{
    use \App\Models\Concerns\HasDoctorServices;

    protected $with = ['services'];
    protected $appends = ['specialty'];

    protected $table = 'doctors';

    protected $primaryKey = 'doctor_id';

    public $timestamps = false; 

    protected $fillable = [
        'doctor_name',
        'status',
        'available',
        'description',
        'schedule',
        'contact_number',
        'profile_image',
        'gender',
        'years_experience',
        'education',
        'license',
        'fellowship',
    ];

    public function schedules()
{
    return $this->hasMany(\App\Models\admin_models\DoctorSchedule::class, 'doctor_id', 'doctor_id');
}
}
