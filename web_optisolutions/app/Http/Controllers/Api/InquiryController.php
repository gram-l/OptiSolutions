<?php
// app/Http/Controllers/Api/InquiryController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    /** POST /api/inquiries/{id}/resolve  |  POST /api/admin/inquiries/{id}/resolve */
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
        }

        return response()->json(['updates' => $updates]);
    }
}