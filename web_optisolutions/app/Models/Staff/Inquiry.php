<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $table = 'inquiries';
    protected $primaryKey = 'inquiry_id';
    public $timestamps = false; // palitan sa true kung may created_at/updated_at ang table mo

    protected $fillable = [
        'patient_id',
        'log_id',
        'inquiry_type',
        'resolved_status',
        'inquiry_reply',
        'replied_at',
    ];

    /**
     * Kumuha ng chatbot log entry na may kaugnayan sa inquiry na ito
     */
        public function log()
    {
        return $this->belongsTo(\App\Models\ChatbotLog::class, 'log_id', 'log_id');
    }

    /**
     * I-convert ang inquiry data papunta sa array format na inaasahan ng
     * Flutter app (InquiriesPage). Palaging may fallback value ang bawat
     * field para hindi mag-crash ang app kapag null sa database.
     */
    public function toApiArray()
    {
        $log = $this->log;

        // Hatiin ang chat_time papunta sa hiwalay na date at time string
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
            'isNew'      => $this->resolved_status === 'Pending',
        ];
    }
}