<?php
// app/Http/Controllers/admin_acc/NotificationController.php
namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function apiIndex()
    {
        $notifications = DB::table('app_notifications')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $unreadCount = DB::table('app_notifications')->where('is_read', 0)->count();

        return response()->json([
            'success'       => true,
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    public function markRead($id)
    {
        DB::table('app_notifications')->where('notification_id', $id)->update(['is_read' => 1]);
        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        DB::table('app_notifications')->where('is_read', 0)->update(['is_read' => 1]);
        return response()->json(['success' => true]);
    }
}