<?php

namespace App\Http\Controllers;

use App\Models\ChatbotLog;
use Illuminate\Http\Request;

class ChatbotLogController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'      => 'required|integer|exists:users,user_id',
            'user_message' => 'nullable|string',
            'bot_message'  => 'nullable|string',
        ]);

        $log = ChatbotLog::create($validated);

        return response()->json(['log_id' => $log->log_id], 201);
    }
}