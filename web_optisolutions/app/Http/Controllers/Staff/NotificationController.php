<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\AppNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    //(mika) excluded complaint and feedback notifications from staff 
    public function index()
    {
        $userId = Auth::user()->user_id;
        $visibleTypes = NotificationService::visibleTypesFor(Auth::user()->user_role ?? null);
 
        $notifications = AppNotification::where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->whereIn('type', $visibleTypes)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
 
        $unreadCount = AppNotification::where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->whereIn('type', $visibleTypes)
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