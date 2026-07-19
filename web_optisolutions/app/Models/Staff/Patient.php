<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table = 'patients';
    protected $primaryKey = 'patient_id';
    public $timestamps = false; 

    protected $fillable = [
        'patient_fname', 'patient_lname', 'patient_birthdate', 'patient_email', 'patient_contact',
    ];


    public function getFullNameAttribute()
    {
        return trim("{$this->patient_fname} {$this->patient_lname}");
    }

    public function visits()
    {
        return $this->hasMany(ScheduleVisit::class, 'patient_id', 'patient_id');
    }

    public function latestVisit()
    {
        return $this->hasOne(ScheduleVisit::class, 'patient_id', 'patient_id')->latestOfMany('visit_date');
    }

    public function toApiArray()
    {
        $visit = $this->latestVisit;

        return [
            'dbId'           => $this->patient_id,
            'id'             => 'PT-' . str_pad((string) $this->patient_id, 3, '0', STR_PAD_LEFT),
            'name'           => $this->full_name ?: 'Unknown',
            'department'     => $visit->service_type ?? 'General',
            'doctor'         => $visit->doctor->doctor_name ?? 'N/A',
            'birthday'       => $this->patient_birthdate ?? '',
            'contact'        => $this->patient_contact ?? '',
            'status'         => 'Active',
            'dateRegistered' => $this->patient_birthdate ?? '', 
            'notes'          => '',
        ];
    }
}