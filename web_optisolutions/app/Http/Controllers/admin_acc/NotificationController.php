<?php
// app/Http/Controllers/admin_acc/NotificationController.php
namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    // ── Web view: /admin_acc/notifications ──────────────────────
    public function index(Request $request)
    {
        $notifications = DB::table('app_notifications')
            ->orderBy('created_at', 'desc')
            ->get();

        $notifications = $notifications->map(function ($notification) {
            $notification->icon = $this->iconForType($notification->type);
            return $notification;
        });

        $groupedNotifications = $notifications->groupBy(function ($notification) {
            $date = \Carbon\Carbon::parse($notification->created_at);

            if ($date->isToday()) {
                return 'Today';
            } elseif ($date->isYesterday()) {
                return 'Yesterday';
            }

            return 'Earlier';
        });

        // AJAX (panel opened over the current page) gets just the panel markup.
        // A direct page load/refresh still gets the full standalone page.
        if ($request->ajax()) {
            return view('admin_acc.partials.notifications_panel', [
                'groupedNotifications' => $groupedNotifications,
            ]);
        }

        return view('admin_acc.notifications', [
            'groupedNotifications' => $groupedNotifications,
        ]);
    }

    private function iconForType(string $type): string
    {
        return match ($type) {
            'chatbot'      => 'bi-chat-dots',
            'appointment', 'appointments' => 'bi-calendar-check',
            'patient', 'patients'         => 'bi-person-plus',
            'system'       => 'bi-exclamation-triangle',
            default        => 'bi-bell',
        };
    }

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