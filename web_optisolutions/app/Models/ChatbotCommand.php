<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotCommand extends Model
{
    protected $table = 'chatbot_commands';
    protected $primaryKey = 'command_id';

    protected $fillable = [
        'label',
        'trigger_value',
        'reply_text',
        'show_in_menu',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'show_in_menu' => 'boolean',
        'is_active'    => 'boolean',
        'sort_order'   => 'integer',
    ];

    /**
     * Reserved trigger values that are already handled by hardcoded
     * BotManController flows / Conversation classes. Admins can't
     * create or rename a command into one of these, or the dynamic
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
    ];

    public static function normalizeTrigger(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInMenu($query)
    {
        return $query->where('is_active', true)->where('show_in_menu', true);
    }
}
