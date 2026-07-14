<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientDoctor extends Model
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
}