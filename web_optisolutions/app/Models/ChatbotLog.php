<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;

class ChatbotLog extends Model
{
    protected $table = 'chatbot_logs';
    protected $primaryKey = 'log_id';
    public $timestamps = false; // change if there's created_at/updated_at on your table

    protected $fillable = [
        'user_id',
        'user_message',
        'bot_message',
        'chat_time',
    ];

    public function inquiry()
    {
        return $this->hasOne(Inquiry::class, 'log_id', 'log_id');
    }
}