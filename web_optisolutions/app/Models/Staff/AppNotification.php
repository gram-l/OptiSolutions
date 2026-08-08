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
            'inquiry' => 'question_answer',
            'schedule visit' => 'calendar_today',
            'doctor' => 'medical_services',
            'patient' => 'person',
            default => 'notifications',
        };
    }

    private function colorForType(): string
    {
        return match ($this->type) {
            'inquiry' => '#1976D2',
            'schedule visit' => '#388E3C',
            'doctor' => '#8E24AA',
            'patient' => '#F57C00',
            default => '#607D8B',
        };
    }
}