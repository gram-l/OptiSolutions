<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class AppNotification extends Model
{
    protected $table = 'app_notifications';
    protected $primaryKey = 'notification_id';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'reference_type', 'reference_id', 'is_read',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->notification_id,
            'icon' => $this->iconForType(),
            'title' => $this->title,
            'message' => $this->message,
            'time' => $this->created_at?->diffForHumans(),
            'isRead' => (bool) $this->is_read,
            'color' => $this->colorForType(),
            'type' => $this->type,
            'referenceType' => $this->reference_type,
            'referenceId' => $this->reference_id,
        ];
    }

    private function iconForType(): string
    {
        return match ($this->type) {
            'chat_inquiry' => 'question_answer',
            'appointment' => 'calendar_today',
            'feedback' => 'star',
            'complaint' => 'report_problem',
            'system' => 'warning',
            default => 'notifications',
        };
    }

    private function colorForType(): string
    {
        return match ($this->type) {
            'chat_inquiry' => '#1976D2',
            'appointment' => '#388E3C',
            'feedback' => '#F9A825',
            'complaint' => '#D32F2F',
            'system' => '#F57C00',
            default => '#607D8B',
        };
    }
}