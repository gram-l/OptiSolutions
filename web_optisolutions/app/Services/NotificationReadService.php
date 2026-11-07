<?php

namespace App\Services;

use App\Models\Staff\AppNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class NotificationReadService
{
    public static function visibleTo($user): Builder
    {
        $role = strtolower(trim($user->user_role ?? ''));
        abort_unless($user && in_array($role, ['admin', 'staff'], true) && $user->status === 'active', 403);
        $id = $user->getAuthIdentifier();
        $types = array_intersect(NotificationService::visibleTypesFor($role), NotificationPreferences::visibleTypes($id));

        return AppNotification::query()->from('app_notifications as n')
            ->leftJoin('notification_reads as nr', function ($join) use ($id) {
                $join->on('nr.notification_id', '=', 'n.notification_id')->where('nr.user_id', '=', $id);
            })
            ->where(fn ($q) => $q->whereNull('n.user_id')->orWhere('n.user_id', $id))
            ->whereIn('n.type', $types)
            ->select('n.*')
            ->selectRaw('CASE WHEN nr.user_id IS NULL THEN 0 ELSE 1 END as is_read');
    }

    public static function markRead($user, $notificationId): AppNotification
    {
        $notification = self::visibleTo($user)->where('n.notification_id', $notificationId)->firstOrFail();
        DB::table('notification_reads')->insertOrIgnore([
            'notification_id' => $notification->notification_id,
            'user_id' => $user->getAuthIdentifier(),
            'read_at' => now(),
        ]);
        $notification->is_read = 1;

        return $notification;
    }

    public static function markAllRead($user): void
    {
        $unread = self::visibleTo($user)->whereNull('nr.user_id')->toBase()
            ->select('n.notification_id')
            ->selectRaw('? as user_id, ? as read_at', [$user->getAuthIdentifier(), now()->toDateTimeString()]);
        DB::table('notification_reads')->insertOrIgnoreUsing(['notification_id', 'user_id', 'read_at'], $unread);
    }
}
