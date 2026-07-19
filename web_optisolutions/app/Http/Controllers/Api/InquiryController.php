<?php
// app/Http/Controllers/Api/InquiryController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatbotLog;
use App\Models\Staff\Inquiry;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InquiryController extends Controller
{
    /** GET /api/inquiries */
    public function index()
    {
        $inquiries = Inquiry::with('log')->orderBy('inquiry_id', 'desc')->get();

        return response()->json($inquiries->map->toApiArray());
    }

    /** GET /api/inquiries/{id} */
    public function show($id)
    {
        $inquiry = Inquiry::with('log')->findOrFail($id);

        return response()->json($inquiry->toApiArray());
    }

    /**
     * POST /api/inquiries
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'    => 'nullable|integer|exists:patients,patient_id',
            'inquiry_type'  => 'nullable|string',
            'user_message'  => 'required|string|min:1',
        ]);

        $log = ChatbotLog::create([
            'user_id'      => $validated['patient_id'] ?? null,
            'user_message' => $validated['user_message'],
            'bot_message'  => '',
            'chat_time'    => now(),
        ]);

        $inquiry = Inquiry::create([
            'patient_id'      => $validated['patient_id'] ?? null,
            'log_id'          => $log->log_id,
            'inquiry_type'    => $validated['inquiry_type'] ?? 'General',
            'resolved_status' => 'Pending',
        ]);

        return response()->json($inquiry->fresh('log')->toApiArray(), 201);
    }

    /** GET /api/inquiries/{inquiry}/messages */
    public function messages($id)
    {
        $inquiry = Inquiry::findOrFail($id);

        $messages = [];

        if (!empty($inquiry->inquiry_reply)) {
            $messages[] = [
                'sender'  => 'Staff',
                'message' => $inquiry->inquiry_reply,
                'time'    => $inquiry->replied_at
                    ? Carbon::parse($inquiry->replied_at)->format('g:i A')
                    : '',
                'isStaff' => true,
            ];
        }

        return response()->json($messages);
    }


    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $inquiry = Inquiry::findOrFail($id);

        $inquiry->inquiry_reply = $request->message;
        $inquiry->replied_at = now();
        $inquiry->save();

        return response()->json([
            'sender'  => 'Staff',
            'message' => $inquiry->inquiry_reply,
            'time'    => Carbon::parse($inquiry->replied_at)->format('g:i A'),
            'isStaff' => true,
        ]);
    }


    public function resolve($id)
    {
        $inquiry = Inquiry::findOrFail($id);
        $inquiry->resolved_status = 'Resolved';
        $inquiry->save();

        return response()->json([
            'message' => 'Inquiry resolved!',
            'inquiry' => $inquiry->fresh('log')->toApiArray(),
        ]);
    }
}