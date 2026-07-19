<?php
// app/Models/Staff/AppNotification.php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notifications';
    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'reference_type',
        'reference_id',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function toApiArray(): array
    {
        return [
            'id'            => $this->notification_id,
            'type'          => $this->type,
            'title'         => $this->title,
            'message'       => $this->message,
            'referenceType' => $this->reference_type,
            'referenceId'   => $this->reference_id,
            'time'          => $this->created_at?->diffForHumans(),
            'isRead'        => $this->is_read,
        ];
    }
}