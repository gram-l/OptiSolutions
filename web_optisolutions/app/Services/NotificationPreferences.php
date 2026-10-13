<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationPreferences
{
    public const DEFAULTS = ['notify_appointments' => true, 'notify_chatbot' => true, 'notify_complaints' => true, 'notify_feedback' => true, 'notify_email' => true];

    public static function forUser(int $id): array
    {
        $stored = Schema::hasColumn('users', 'notification_preferences')
            ? DB::table('users')->where('user_id', $id)->value('notification_preferences') : null;
        return array_replace(self::DEFAULTS, array_intersect_key(json_decode($stored ?? '{}', true) ?: [], self::DEFAULTS));
    }

    public static function visibleTypes(int $id): array
    {
        $preferences = self::forUser($id);
        $types = ['system'];
        foreach (['appointment' => 'notify_appointments', 'chat_inquiry' => 'notify_chatbot', 'complaint' => 'notify_complaints', 'feedback' => 'notify_feedback'] as $type => $key) {
            if ($preferences[$key]) $types[] = $type;
        }
        return $types;
    }
}
