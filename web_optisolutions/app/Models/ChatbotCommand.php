<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotCommand extends Model
{
    protected $table = 'chatbot_commands';
    protected $primaryKey = 'command_id';

    protected $fillable = [
        'label',
        'reply_text',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Reserved trigger values that are already handled by hardcoded
     * BotManController flows / Conversation classes. Admins can't
     * create or rename a trigger into one of these, or the dynamic
     * hears() registration would collide with the real flow.
     */
    public const RESERVED_TRIGGERS = [
        'schedule visit',
        'general information',
        'submit complaint',
        'submit review/rating',
        'menu',
        'cancel',
        'review',
        'complaint',
        "no, i'm all set",
        'yes, i need help',
        'anything_else_yes',
        'anything_else_no',
    ];

    public function triggers()
    {
        return $this->hasMany(ChatbotCommandTrigger::class, 'command_id', 'command_id');
    }

    public static function normalizeTrigger(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /**
     * Comma-joined trigger list, handy for table display / debugging.
     */
    public function getTriggerListAttribute(): string
    {
        return $this->triggers->pluck('trigger_value')->implode(', ');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}