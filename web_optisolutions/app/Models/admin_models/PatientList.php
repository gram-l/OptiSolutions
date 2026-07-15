<?php
// app/Models/admin_models/PatientList.php
namespace App\Models\admin_models;

use Illuminate\Database\Eloquent\Model;

class PatientList extends Model
{
    protected $table = 'patients';
    protected $primaryKey = 'patient_id';
    public $timestamps = false;

    protected $fillable = [
        'patient_fname', 'patient_lname', 'patient_birthdate',
        'patient_email', 'patient_contact',
    ];
}