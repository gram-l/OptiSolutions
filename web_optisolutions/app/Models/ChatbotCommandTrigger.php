<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotCommandTrigger extends Model
{
    protected $table = 'chatbot_command_triggers';
    protected $primaryKey = 'trigger_id';

    protected $fillable = [
        'command_id',
        'trigger_value',
    ];

    public function command()
    {
        return $this->belongsTo(ChatbotCommand::class, 'command_id', 'command_id');
    }
}