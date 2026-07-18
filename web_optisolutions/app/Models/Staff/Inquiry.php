<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $table = 'inquiries';
    protected $primaryKey = 'inquiry_id';
    public $timestamps = false;

    protected $fillable = [
        'inquiry_id',
        'patient_id',
        'log_id',
        'inquiry_type',
        'resolved_status',
        'inquiry_reply',
        'replied_at',
    ];

    /**
     * ✅ Hindi auto_increment ang inquiry_id sa database — kailangan
     * manual na i-generate ang susunod na ID bago i-save.
     * ✅ Pagkatapos ma-save nang matagumpay, saka pa lang gagawin ang
     * staff notification gamit ang tamang column names.
     */
    protected static function booted()
    {
        static::creating(function (Inquiry $inquiry) {
            if (empty($inquiry->inquiry_id)) {
                $inquiry->inquiry_id = (static::max('inquiry_id') ?? 0) + 1;
            }
        });

        static::created(function (Inquiry $inquiry) {
            AppNotification::create([
                'user_id' => null,
                'type' => 'inquiry',
                'title' => 'New Inquiry',
                'message' => $inquiry->inquiry_type
                    ? "A new {$inquiry->inquiry_type} inquiry has been submitted."
                    : 'A new inquiry has been submitted.',
                'reference_type' => 'inquiry',
                'reference_id' => $inquiry->inquiry_id,
                'is_read' => false,
            ]);
        });
    }

    public function log()
    {
        return $this->belongsTo(\App\Models\ChatbotLog::class, 'log_id', 'log_id');
    }

    public function toApiArray()
    {
        $log = $this->log;

        $date = '';
        $time = '';
        if ($log && $log->chat_time) {
            $chatTime = \Carbon\Carbon::parse($log->chat_time);
            $date = $chatTime->format('Y-m-d');
            $time = $chatTime->format('h:i A');
        }

        return [
            'dbId'       => $this->inquiry_id,
            'id'         => 'INQ-' . str_pad((string) $this->inquiry_id, 3, '0', STR_PAD_LEFT),
            'patientId'  => (string) ($this->patient_id ?? ''),
            'department' => $this->inquiry_type ?? 'General',
            'message'    => $log->user_message ?? '',
            'date'       => $date,
            'time'       => $time,
            // ✅ FIX: base natin ito sa kung meron nang reply, hindi sa
            // resolved_status — dahil 'Pending' pa rin ang status kahit
            // na-reply na (walang 'In Progress' sa enum ng database),
            // kaya dating hindi nawawala ang "NEW" tag pagkatapos mag-reply.
            'isNew'      => empty($this->inquiry_reply),
        ];
    }
}