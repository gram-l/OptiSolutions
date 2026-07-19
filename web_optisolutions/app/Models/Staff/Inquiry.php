<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;
use App\Models\ChatbotLog;

class Inquiry extends Model
{
    protected $table = 'inquiries';
    protected $primaryKey = 'inquiry_id';
    public $timestamps = false;

    protected $fillable = [
        'patient_id',
        'guest_name',
        'log_id',
        'conversation_id',
        'inquiry_type',
        'resolved_status',
        'inquiry_reply',
        'replied_at',
        'created_at',
    ];

    public function log()
    {
        return $this->belongsTo(ChatbotLog::class, 'log_id', 'log_id');
    }

    public function replies()
    {
        return $this->hasMany(InquiryReply::class, 'inquiry_id', 'inquiry_id')
            ->orderBy('created_at', 'asc');
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

        $latestPatientReply = $this->relationLoaded('replies')
            ? $this->replies->where('sender', 'Patient')->last()
            : $this->replies()->where('sender', 'Patient')->latest('created_at')->first();

        $message = $latestPatientReply->message ?? ($log->user_message ?? '');

        // Kung may patient_id (naka-schedule na dati), gamitin ang ID.
        // Kung guest lang pero may naibigay nang pangalan, ipakita iyon.
        // Kung wala talaga, "Guest" na lang.
        $displayName = $this->patient_id
            ? ('Patient #' . $this->patient_id)
            : ($this->guest_name ?: 'Guest');

        return [
            'dbId'        => $this->inquiry_id,
            'id'          => 'INQ-' . str_pad((string) $this->inquiry_id, 3, '0', STR_PAD_LEFT),
            'patientId'   => (string) ($this->patient_id ?? 'Guest'),
            'patientName' => $displayName,
            'department'  => $this->inquiry_type ?? 'General',
            'message'     => $message,
            'date'        => $date,
            'time'        => $time,
            'status'      => $this->resolved_status ?? 'Pending',
            'isNew'       => $this->resolved_status === 'Pending',
        ];
    }
}