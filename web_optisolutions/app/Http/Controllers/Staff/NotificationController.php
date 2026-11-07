<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\NotificationReadService;
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
        $notifications = NotificationReadService::visibleTo(Auth::user())
            ->orderByDesc('n.created_at')
            ->limit(20)
            ->get();
 
        $unreadCount = NotificationReadService::visibleTo(Auth::user())->whereNull('nr.user_id')->count();
 
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead($id)
    {
        NotificationReadService::markRead(Auth::user(), $id);

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        NotificationReadService::markAllRead(Auth::user());

        return response()->json(['success' => true]);
    }
}
