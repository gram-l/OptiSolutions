<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table = 'patients';
    protected $primaryKey = 'patient_id';
    public $timestamps = false; // palitan sa true kung may created_at/updated_at ang table mo

    protected $fillable = [
        'patient_fname', 'patient_lname', 'patient_birthdate', 'patient_email', 'patient_contact',
    ];

    /**
     * Buong pangalan ng pasyente (fname + lname)
     */
    public function getFullNameAttribute()
    {
        return trim("{$this->patient_fname} {$this->patient_lname}");
    }

    /**
     * Lahat ng visits ng pasyenteng ito
     */
    public function visits()
    {
        return $this->hasMany(ScheduleVisit::class, 'patient_id', 'patient_id');
    }

    /**
     * Pinakabagong visit — dito natin makukuha ang "current" assigned doctor/department
     */
    public function latestVisit()
    {
        return $this->hasOne(ScheduleVisit::class, 'patient_id', 'patient_id')->latestOfMany('visit_date');
    }

    /**
     * I-convert ang patient data papunta sa array format na inaasahan ng
     * Flutter app (PatientsPage): dbId, id, name, department, doctor,
     * birthday, contact, status, dateRegistered, notes.
     *
     * PAALALA: walang column sa 'patients' table para sa department, doctor,
     * status, notes, o date_registered — kaya galing sa latest visit ang
     * department/doctor, at safe default na lang ang iba. Kung gusto mong
     * tunay na ma-track ang mga ito, magdagdag ng columns sa DB.
     */
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
            'dateRegistered' => $this->patient_birthdate ?? '', // TODO: palitan kapag may date_registered column na
            'notes'          => '',
        ];
    }
}