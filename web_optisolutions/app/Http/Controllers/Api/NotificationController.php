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

    /**
     * PATCH /api/notifications/{id}/read
     * ✅ FIX: gumagamit na ng manual findOrFail($id) sa halip na
     * implicit route-model binding — para siguradong gumagana ito
     * anuman ang pangalan ng route parameter, at para makumpirma
     * nating talagang na-save ang is_read papuntang database.
     */
    public function markRead($id)
    {
        $notification = AppNotification::findOrFail($id);
        $notification->is_read = true;
        $notification->save();

        return $notification->toApiArray();
    }

    /** POST /api/notifications/mark-all-read */
    public function markAllRead()
    {
        AppNotification::where('is_read', false)->update(['is_read' => true]);
        return response()->json(['message' => 'All marked as read']);
    }
}