<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $primaryKey = 'patient_id';

    protected $fillable = [
        'patient_name', 'department', 'assigned_doctor',
        'phone', 'age', 'last_visit',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'assigned_doctor', 'name');
    }

    public function toApiArray()
    {
        // Walang totoong "birthday" column sa database, "age" lang meron —
        // kaya gumagawa tayo ng approximate na petsa base dito para gumana
        // pa rin ang existing na "calculate age" logic sa Flutter side.
        $approxBirthYear = now()->subYears($this->age ?? 0)->year;

        return [
            'dbId'           => $this->patient_id,
            'id'             => 'PT-' . str_pad($this->patient_id, 3, '0', STR_PAD_LEFT),
            'name'           => $this->patient_name ?? '',
            'department'     => $this->department ?? '',
            'doctor'         => $this->assigned_doctor ?? '',
            'birthday'       => $approxBirthYear . '-01-01',
            'contact'        => $this->phone ?? '',
            'status'         => 'Active', // walang status column sa DB, default muna ito
            'dateRegistered' => $this->created_at?->format('Y-m-d') ?? '',
            'notes'          => $this->last_visit ? "Last visit: {$this->last_visit}" : '',
        ];
    }
}