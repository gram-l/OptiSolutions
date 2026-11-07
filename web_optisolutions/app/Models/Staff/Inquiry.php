<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;
use App\Models\ChatbotLog;
use Carbon\Carbon;

class Inquiry extends Model
{
    protected $table = 'inquiries';
    protected $primaryKey = 'inquiry_id';
    public $timestamps = false;

    protected $fillable = [
        'inquiry_id',
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

    
    protected static function booted()
    {
        static::creating(function (Inquiry $inquiry) {
            if (empty($inquiry->inquiry_id)) {
                $inquiry->inquiry_id = (static::max('inquiry_id') ?? 0) + 1;
            }
        });

        static::created(function (Inquiry $inquiry) {
            $name = $inquiry->patient_id
                ? "Patient #{$inquiry->patient_id}"
                : ($inquiry->guest_name ?: 'A guest');

            \App\Services\NotificationService::newInquiry($name, $inquiry->inquiry_id);
        });
    }

    /**
     * Ang pinakahuling inquiry ng chat na ito, Resolved man o hindi.
     * Dito dapat bumalik ang patient kapag nag-inquire ulit: iisang inquiry
     * per chat, para hindi lumabas bilang bagong inquiry ang follow-up.
     */
    public static function latestForConversation(?string $conversationId): ?self
    {
        if ($conversationId === null || $conversationId === '') {
            return null;
        }

        return static::where('conversation_id', $conversationId)
            ->orderByDesc('inquiry_id')
            ->first();
    }

    /**
     * Kapag Resolved na ang inquiry at nag-message ulit ang patient, ibabalik
     * ito sa "Pending" (kailangan ulit ng atensyon ng staff). Walang bagong
     * inquiry na nagagawa. Ang resolve/unresolve ng Admin/Staff ay para sa
     * inquiry na ito mismo.
     */
    public function reopenIfResolved(): bool
    {
        if ($this->resolved_status !== 'Resolved') {
            return false;
        }

        $this->resolved_status = 'Pending';
        $this->save();

        return true;
    }

    public function log()
    {
        return $this->belongsTo(ChatbotLog::class, 'log_id', 'log_id');
    }

    public function replies()
    {
        // reply_id as a tie-breaker: a patient message and the chatbot's answer
        // are saved in the same second and must stay in the right order.
        return $this->hasMany(InquiryReply::class, 'inquiry_id', 'inquiry_id')
            ->orderBy('created_at', 'asc')
            ->orderBy('reply_id', 'asc');
    }

    public function toApiArray()
    {
        $log = $this->log;

        $date = '';
        $time = '';

        if ($log && $log->chat_time) {
            $chatTime = Carbon::parse($log->chat_time);
            $date = $chatTime->format('Y-m-d');
            $time = $chatTime->format('h:i A');
        }

        $latestPatientReply = $this->relationLoaded('replies')
            ? $this->replies->where('sender', 'Patient')->last()
            : $this->replies()
                ->where('sender', 'Patient')
                ->latest('created_at')
                ->first();

        $message = $latestPatientReply->message ?? ($log->user_message ?? '');

        $displayName = $this->patient_id
            ? 'Patient #' . $this->patient_id
            : ($this->guest_name ?: 'Guest');

        return [
            'dbId'       => $this->inquiry_id,
            'id'         => 'INQ-' . str_pad((string) $this->inquiry_id, 3, '0', STR_PAD_LEFT),
            'patientId'  => (string) ($this->patient_id ?? ''),
            'department' => $this->inquiry_type ?? 'General',
            'message'    => $log->user_message ?? '',
            'date'       => $date,
            'time'       => $time,
           
            'isNew'      => empty($this->inquiry_reply),
        ];
    }
}