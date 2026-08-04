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
}