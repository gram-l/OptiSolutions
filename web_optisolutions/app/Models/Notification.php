<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'app_notifications';

    protected $primaryKey = 'notification_id';

    public $timestamps = true;

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
        'user_id'       => 'integer',
        'reference_id'  => 'integer',
        'is_read'       => 'boolean',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    // Nullable, MUL (indexed) FK — a notification may target a specific
    // admin/staff user, or be null for "everyone sees this."
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}