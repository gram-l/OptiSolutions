<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $primaryKey = 'appointment_id';

    protected $fillable = [
        'patient', 'doctor', 'date', 'status', 'staff_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    /**
     * ✅ Awtomatikong gumagawa ng staff notification tuwing may bagong
     * appointment/schedule visit na malikha (basta Eloquent create()/save()
     * ang ginamit sa paggawa nito).
     */
    protected static function booted()
    {
        static::created(function (Appointment $appointment) {
            $dateStr = $appointment->date
                ? $appointment->date->format('M d, Y')
                : '';

            AppNotification::create([
                'icon' => 'calendar_today',
                'title' => 'New Schedule Visit',
                'message' => trim(
                    ($appointment->patient ? "{$appointment->patient} " : 'A patient ')
                    . 'scheduled a visit'
                    . ($appointment->doctor ? " with Dr. {$appointment->doctor}" : '')
                    . ($dateStr ? " on {$dateStr}" : '')
                    . '.'
                ),
                'is_read' => false,
                'color' => '4CAF50',
            ]);
        });
    }

    public function toApiArray()
    {
        return [
            'appointment_id' => $this->appointment_id,
            'patient'        => $this->patient,
            'doctor'         => $this->doctor,
            'date'           => $this->date,
            'status'         => $this->status,
        ];
    }
}