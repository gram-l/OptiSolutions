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

    /** PATCH /api/notifications/{notification}/read */
    public function markRead(AppNotification $notification)
    {
        $notification->update(['is_read' => true]);
        return $notification->toApiArray();
    }

    /** POST /api/notifications/mark-all-read */
    public function markAllRead()
    {
        AppNotification::where('is_read', false)->update(['is_read' => true]);
        return response()->json(['message' => 'All marked as read']);
    }
}