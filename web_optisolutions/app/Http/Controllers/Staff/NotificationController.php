<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\AppNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationPreferences;

class NotificationController extends Controller
{
    public function savePreferences(Request $request)
    {
        $validated = $request->validate([
            'notify_appointments' => 'sometimes|required|boolean',
            'notify_chatbot' => 'sometimes|required|boolean',
            'notify_email' => 'sometimes|required|boolean',
        ]);
        $preferences = array_replace(NotificationPreferences::forUser($request->user()->user_id), $validated);
        DB::table('users')->where('user_id', $request->user()->user_id)->update(['notification_preferences' => json_encode($preferences)]);
        return response()->json($preferences);
    }
    //(mika) excluded complaint and feedback notifications from staff 
    public function index()
    {
        $userId = Auth::user()->user_id;
        $visibleTypes = array_intersect(NotificationService::visibleTypesFor(strtolower(trim(Auth::user()->user_role ?? ''))), NotificationPreferences::visibleTypes($userId));
 
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
