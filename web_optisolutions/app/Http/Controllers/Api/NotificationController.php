<?php
// app/Http/Controllers/Api/NotificationController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff\AppNotification;

class NotificationController extends Controller
{
    /** GET /api/notifications */
    public function index()
    {
        return AppNotification::orderByDesc('created_at')->get()->map->toApiArray();
    }


    public function markRead($id)
    {
        $notification = AppNotification::findOrFail($id);
        $notification->is_read = true;
        $notification->save();

        return $notification->toApiArray();
    }

    public function markAllRead()
    {
        AppNotification::where('is_read', false)->update(['is_read' => true]);
        return response()->json(['message' => 'All marked as read']);
    }
}