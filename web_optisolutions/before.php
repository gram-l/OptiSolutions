<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\Staff\Inquiry;
use App\Models\Staff\InquiryReply;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Web (Blade) na bersyon ng "Chatbot Inquiries" page para sa Admin.
 * Ginagamit din ang parehong `inquiries` / `inquiry_replies` tables na
 * ginagamit ng Staff web panel at ng Admin/Staff Flutter apps (via
 * App\Http\Controllers\Api\InquiryController), kaya kahit saang
 * interface sumagot, iisa lang ang makikitang thread ng usapan.
 */
class ChatbotInquiryController extends Controller
{
    public function index()
    {
        $inquiries = Inquiry::with(['log', 'replies'])
            ->orderByDesc('inquiry_id')
            ->get()
            ->map(fn (Inquiry $inquiry) => $this->toChatLogArray($inquiry));

        return view('admin_acc.chatbot_logs', ['inquiriesData' => $inquiries->values()]);
    }

    public function reply(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);

        $inquiry = Inquiry::findOrFail($id);

        $reply = InquiryReply::create([
            'inquiry_id' => $inquiry->inquiry_id,
            'user_id'    => $request->user()->user_id ?? null,
            'sender'     => 'Admin',
            'message'    => $request->message,
        ]);

        // Panatilihing updated din ang legacy columns (ginagamit pa rin
        // ito ng ibang bahagi/reports ng system).
        $inquiry->inquiry_reply = $request->message;
        $inquiry->replied_at = now();
        if ($inquiry->resolved_status === 'Pending') {
            $inquiry->resolved_status = 'In Progress';
        }
        $inquiry->save();

        return response()->json([
            'message' => 'Reply sent!',
            'time'    => $reply->created_at->format('g:i A'),
            'status'  => $inquiry->resolved_status,
        ]);
    }

    public function resolve($id)
    {
        $inquiry = Inquiry::findOrFail($id);
        $inquiry->resolved_status = 'Resolved';
        $inquiry->save();

        return response()->json(['message' => 'Inquiry resolved!']);
    }

    /**
     * Reopen a resolved Inquiry. Mirrors resolve() — reverts the status to
     * 'In Progress' rather than 'Pending', since a resolved inquiry has
     * already been engaged with (matches the same status reply() sets
     * once a reply is sent to a previously-Pending inquiry).
     */
    public function unresolve($id)
    {
        $inquiry = Inquiry::findOrFail($id);
        $inquiry->resolved_status = 'In Progress';
        $inquiry->save();

        return response()->json(['message' => 'Inquiry reopened!']);
    }

    /**
     * Ayusin ang isang Inquiry papunta sa shape na inaasahan ng JS sa
     * chatbot_logs.blade.php (dating `chatLogs` mock array).
     */
    protected function toChatLogArray(Inquiry $inquiry): array
{
    $log = $inquiry->log;

    $timestamp = $log && $log->chat_time
        ? Carbon::parse($log->chat_time)
        : ($inquiry->created_at ? Carbon::parse($inquiry->created_at) : now());

    // Tunay na pangalan na ipapakita: kung may patient_id (naka-schedule
    // na), gamitin ang "Patient #ID". Kung guest lang pero may naibigay
    // nang pangalan, gamitin iyon. Kung wala talaga, "Guest".
    $displayName = $inquiry->patient_id
        ? ('Patient #' . $inquiry->patient_id)
        : ($inquiry->guest_name ?: 'Guest');

    $patientLabel = $inquiry->patient_id ? 'P-' . $inquiry->patient_id : 'Guest';
    $initials = $inquiry->patient_id
        ? 'P' . $inquiry->patient_id
        : strtoupper(substr($inquiry->guest_name ?: 'G', 0, 1));

    $statusMap = [
        'Pending'     => 'pending',
        'In Progress' => 'active',
        'Resolved'    => 'resolved',
    ];

    $conversation = [];

    if ($log) {
        $conversation[] = [
            'sender'      => 'patient',
            'senderLabel' => 'Patient',
            'text'        => $log->user_message ?? '',
            'time'        => $timestamp->format('g:i A'),
        ];
    }

    foreach ($inquiry->replies as $reply) {
        $conversation[] = [
            'sender'      => $reply->is_staff ? 'bot' : 'patient',
            'senderLabel' => $reply->sender,
            'text'        => $reply->message,
            'time'        => $reply->created_at->format('g:i A'),
        ];
    }

    $lastMessage = collect($conversation)->last()['text'] ?? '(no message)';

    return [
        'id'           => $inquiry->inquiry_id,
        'name'         => $displayName,
        'inquiryCode'  => 'INQ-' . str_pad((string) $inquiry->inquiry_id, 3, '0', STR_PAD_LEFT),
        'avatar'       => strtoupper(substr($initials, 0, 2)),
        'patientId'    => $patientLabel,
        'department'   => $inquiry->inquiry_type ?? 'General',
        'lastMessage'  => $lastMessage,
        'timestamp'    => $timestamp->toIso8601String(),
        'unread'       => $inquiry->resolved_status === 'Pending',
        'status'       => $statusMap[$inquiry->resolved_status] ?? 'pending',
        'rawStatus'    => $inquiry->resolved_status,
        'conversation' => $conversation,
    ];
}
}