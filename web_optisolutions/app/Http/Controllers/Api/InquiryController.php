<?php
// app/Http/Controllers/Api/InquiryController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatbotLog;
use App\Models\Staff\Inquiry;
use App\Models\Staff\InquiryReply;
use Illuminate\Http\Request;
use Carbon\Carbon;


class InquiryController extends Controller
{
    /** GET /api/inquiries  |  GET /api/admin/inquiries */
    public function index()
    {
        $inquiries = Inquiry::with(['log', 'replies'])
            ->orderBy('inquiry_id', 'desc')
            ->get();

        return response()->json($inquiries->map->toApiArray());
    }

    /** GET /api/inquiries/{id}  |  GET /api/admin/inquiries/{id} */
    public function show($id)
    {
        $inquiry = Inquiry::with(['log', 'replies'])->findOrFail($id);

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
        $inquiry = Inquiry::with('replies')->findOrFail($id);

        $messages = $inquiry->replies->map(fn (InquiryReply $r) => $r->toApiArray())->values();

        return response()->json($messages);
    }


    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $inquiry = Inquiry::findOrFail($id);
        $user = $request->user(); // Admin o Staff, parehong galing sa `users` table

        $senderLabel = $user->user_role ?? 'Staff'; // "Admin" o "Staff"

        $reply = InquiryReply::create([
            'inquiry_id' => $inquiry->inquiry_id,
            'user_id'    => $user->user_id ?? null,
            'sender'     => $senderLabel,
            'message'    => $request->message,
        ]);

    
        $inquiry->inquiry_reply = $request->message;
        $inquiry->replied_at = now();
        if ($inquiry->resolved_status === 'Pending') {
            $inquiry->resolved_status = 'In Progress';
        }
        $inquiry->save();

        return response()->json($reply->toApiArray());
    }


    public function resolve($id)
    {
        $inquiry = Inquiry::findOrFail($id);
        $inquiry->resolved_status = 'Resolved';
        $inquiry->save();

        return response()->json([
            'message' => 'Inquiry resolved!',
            'inquiry' => $inquiry->fresh(['log', 'replies'])->toApiArray(),
        ]);
    }

    public function publicUpdates(Request $request, string $conversationId)
    {
        $inquiries = Inquiry::with('replies')
            ->where('conversation_id', $conversationId)
            ->orderBy('inquiry_id')
            ->get();

        $updates = [];
        // Every inquiry from this chat session that Admin/Staff has
        // already marked "Resolved" — regardless of whether that came
        // with one final reply or via a plain "Resolve" click with no
        // extra message. The widget uses this (not just `updates`) to
        // know when to bring the 4-option main menu back, since a
        // resolve-with-no-message wouldn't otherwise produce anything
        // new for the patient to see.
        $resolvedInquiryIds = [];

        foreach ($inquiries as $inquiry) {
            foreach ($inquiry->replies->where('is_staff', true) as $reply) {
                $updates[] = [
                    'replyId'   => $reply->reply_id,
                    'inquiryId' => $inquiry->inquiry_id,
                    'sender'    => $reply->sender,
                    'message'   => $reply->message,
                    'time'      => $reply->created_at->format('g:i A'),
                    'status'    => $inquiry->resolved_status,
                ];
            }

            if ($inquiry->resolved_status === 'Resolved') {
                $resolvedInquiryIds[] = $inquiry->inquiry_id;
            }
        }

        return response()->json([
            'updates'            => $updates,
            'resolvedInquiryIds' => $resolvedInquiryIds,
        ]);
    }
}