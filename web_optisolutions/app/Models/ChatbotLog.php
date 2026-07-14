<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotLog extends Model
{
    protected $table = 'chatbot_logs';
    protected $primaryKey = 'log_id';

    const CREATED_AT = 'chat_time';
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'user_message', 'bot_message'];
}