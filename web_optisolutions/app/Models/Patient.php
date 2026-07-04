<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table = 'patients';
    protected $primaryKey = 'patient_id';
    public $timestamps = false;

    protected $fillable = [
        'patient_fname', 'patient_lname', 'patient_birthdate', 'patient_email', 'patient_contact',
    ];
}