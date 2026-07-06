<?php
// app/Models/AppNotification.php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = ['icon', 'title', 'message', 'is_read', 'color'];

    protected $casts = ['is_read' => 'boolean'];

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'icon' => $this->icon,
            'title' => $this->title,
            'message' => $this->message,
            'time' => $this->created_at->diffForHumans(),
            'isRead' => $this->is_read,
            'color' => $this->color,
        ];
    }
}