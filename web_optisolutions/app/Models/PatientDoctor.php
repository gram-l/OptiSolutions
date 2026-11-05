<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientDoctor extends Model
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
        'description',
        'schedule',
        'contact_number'
    ];
}