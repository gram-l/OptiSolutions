<?php
// app/Http/Controllers/Api/NotificationController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationReadService;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /** GET /api/notifications */
    public function index()
    {
        return NotificationReadService::visibleTo(Auth::user())
            ->orderByDesc('n.created_at')
            ->get()
            ->map->toApiArray();
    }


    public function markRead($id)
    {
        $notification = NotificationReadService::markRead(Auth::user(), $id);

        return $notification->toApiArray();
    }

    public function markAllRead()
    {
        NotificationReadService::markAllRead(Auth::user());
        return response()->json(['message' => 'All marked as read']);
    }
}
