<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Inquiry;
use App\Models\Staff\InquiryReply;
use App\Services\TypingStatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function index()
    {
        return view('staff.inquiries.index', [
            'inquiriesData' => $this->inquiriesData(),
            'openId' => null,
        ]);
    }

    /** Deep link (e.g. from a notification): same page, conversation pre-opened. */
    public function show($id)
    {
        Inquiry::findOrFail($id);

        return view('staff.inquiries.index', [
            'inquiriesData' => $this->inquiriesData(),
            'openId' => (int) $id,
        ]);
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $inquiry = Inquiry::findOrFail($id);

        // createUnlessDuplicate guards against a double-click on "Send".
        $reply = InquiryReply::createUnlessDuplicate([
            'inquiry_id' => $inquiry->inquiry_id,
            'user_id'    => $request->user()->user_id ?? null,
            'sender'     => 'Staff',
            'message'    => $request->message,
        ]);

        // Keep legacy columns updated too, for backward compatibility
        $inquiry->inquiry_reply = $request->message;
        $inquiry->replied_at = now();
        // Same rule as the admin page: a first reply moves Pending -> In Progress
        // (this is what clears the "unread" dot).
        if ($inquiry->resolved_status === 'Pending') {
            $inquiry->resolved_status = 'In Progress';
        }
        $inquiry->save();

        if ($inquiry->conversation_id) {
            TypingStatusService::setTyping('staff', $inquiry->conversation_id, false);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message'   => 'Reply sent!',
                'time'      => $reply->created_at->format('g:i A'),
                'timestamp' => $reply->created_at->toIso8601String(),
                'status'    => $inquiry->resolved_status,
            ]);
        }

        return redirect()->route('staff.inquiries.show', $id)->with('success', 'Reply sent!');
    }

    public function resolve(Request $request, $id)
    {
        $inquiry = Inquiry::findOrFail($id);
        $inquiry->resolved_status = 'Resolved';
        $inquiry->save();

        if ($inquiry->conversation_id) {
            TypingStatusService::setTyping('staff', $inquiry->conversation_id, false);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Inquiry resolved!']);
        }

        return redirect()->route('staff.inquiries.show', $id)->with('success', 'Inquiry resolved!');
    }

    /** Undo a resolve: back to "In Progress" so the reply box returns. */
    public function unresolve($id)
    {
        $inquiry = Inquiry::findOrFail($id);
        $inquiry->resolved_status = 'In Progress';
        $inquiry->save();

        return response()->json([
            'message' => 'Inquiry marked as unresolved.',
            'status'  => $inquiry->resolved_status,
        ]);
    }

    private function inquiriesData()
    {
        return Inquiry::with(['log', 'replies'])
            ->orderByDesc('inquiry_id')
            ->get()
            ->map(fn (Inquiry $inquiry) => $this->toChatLogArray($inquiry))
            ->values();
    }

    /** Same shape the admin Chatbot Inquiries page uses, so both look and behave alike. */
    private function toChatLogArray(Inquiry $inquiry): array
    {
        $log = $inquiry->log;

        $timestamp = $log && $log->chat_time
            ? Carbon::parse($log->chat_time)
            : ($inquiry->created_at ? Carbon::parse($inquiry->created_at) : now());

        $displayName = $inquiry->patient_id
            ? ('Patient #' . $inquiry->patient_id)
            : ($inquiry->guest_name ?: 'Guest');

        $initials = $inquiry->patient_id
            ? 'P' . $inquiry->patient_id
            : strtoupper(substr($inquiry->guest_name ?: 'G', 0, 1));

        $statusMap = ['Pending' => 'pending', 'In Progress' => 'active', 'Resolved' => 'resolved'];

        $conversation = [];
        $replies = $inquiry->replies;
        $firstReply = $replies->first();

        // The first patient message is stored both in chatbot_logs and as the
        // first inquiry_reply; skip the log copy so it isn't shown twice.
        $logDuplicatedByFirstReply = $log
            && $firstReply
            && !$firstReply->is_staff
            && trim((string) $firstReply->message) === trim((string) ($log->user_message ?? ''));

        if ($log && !$logDuplicatedByFirstReply) {
            $conversation[] = [
                'sender'      => 'patient',
                'senderLabel' => 'Patient',
                'text'        => $log->user_message ?? '',
                'time'        => $timestamp->format('g:i A'),
                'timestamp'   => $timestamp->toIso8601String(),
            ];
        }

        foreach ($replies as $reply) {
            $conversation[] = [
                'sender'         => $reply->is_staff ? 'bot' : 'patient',
                'senderLabel'    => $reply->sender,
                'text'           => $reply->message,
                'attachmentUrl'  => $reply->attachment_url,
                'attachmentName' => $reply->attachment_name,
                'time'           => $reply->created_at->format('g:i A'),
                'timestamp'      => $reply->created_at->toIso8601String(),
            ];
        }

        return [
            'id'           => $inquiry->inquiry_id,
            'name'         => $displayName,
            'inquiryCode'  => 'INQ-' . str_pad((string) $inquiry->inquiry_id, 3, '0', STR_PAD_LEFT),
            'avatar'       => strtoupper(substr($initials, 0, 2)),
            'patientId'    => $inquiry->patient_id ? 'P-' . $inquiry->patient_id : 'Guest',
            'department'   => $inquiry->inquiry_type,
            'lastMessage'  => collect($conversation)->last()['text'] ?? '(no message)',
            'timestamp'    => $timestamp->toIso8601String(),
            'unread'       => $inquiry->resolved_status === 'Pending',
            'status'       => $statusMap[$inquiry->resolved_status] ?? 'pending',
            'rawStatus'    => $inquiry->resolved_status,
            'conversation' => $conversation,
        ];
    }

    /**
     * Heartbeat hit from the reply input's keystrokes on the Staff
     * inquiries page. Lets the patient-facing widget show a real
     * "Staff is typing…" indicator instead of a fake one tied to the
     * bot's own (instant) responses.
     */
    public function typing(Request $request, $id)
    {
        $request->validate(['typing' => 'required|boolean']);

        $inquiry = Inquiry::findOrFail($id);

        if ($inquiry->conversation_id) {
            TypingStatusService::setTyping('staff', $inquiry->conversation_id, $request->boolean('typing'));
        }

        return response()->json(['ok' => true]);
    }
}