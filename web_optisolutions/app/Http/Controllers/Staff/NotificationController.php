<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\AppNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $userId = Auth::user()->user_id;

        $notifications = AppNotification::where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $unreadCount = AppNotification::where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->where('is_read', 0)
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead($id)
    {
        $notification = AppNotification::findOrFail($id);
        $notification->is_read = 1;
        $notification->save();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        $userId = Auth::user()->user_id;

        AppNotification::where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->update(['is_read' => 1]);

        return response()->json(['success' => true]);
    }
}