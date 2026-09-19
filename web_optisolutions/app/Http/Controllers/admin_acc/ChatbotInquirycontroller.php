<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\Staff\Inquiry;
use App\Models\Staff\InquiryReply;
use App\Services\TypingStatusService;
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

        // createUnlessDuplicate guards against a double-click on "Send" (or
        // a slow request retried) inserting the same reply twice — which
        // would otherwise show up as two identical bubbles on the
        // patient's side once the widget polls for updates.
        $reply = InquiryReply::createUnlessDuplicate([
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

        // The actual reply just landed, so the patient's "..." indicator
        // (driven by the typing flag below) should disappear immediately
        // rather than lingering until its TTL expires.
        if ($inquiry->conversation_id) {
            TypingStatusService::setTyping('staff', $inquiry->conversation_id, false);
        }

        return response()->json([
            'message' => 'Reply sent!',
            'time'    => $reply->created_at->format('g:i A'),
            'status'  => $inquiry->resolved_status,
        ]);
    }

    /**
     * Heartbeat hit from the reply input's keystrokes/blur in
     * chatbot_logs.blade.php. Lets the patient-facing widget show a real
     * "Admin is typing…" indicator instead of a fake one tied to the
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

    public function resolve($id)
    {
        $inquiry = Inquiry::findOrFail($id);
        $inquiry->resolved_status = 'Resolved';
        $inquiry->save();

        if ($inquiry->conversation_id) {
            TypingStatusService::setTyping('staff', $inquiry->conversation_id, false);
        }

        return response()->json(['message' => 'Inquiry resolved!']);
    }

    /**
     * Undo an accidental/premature "Resolve" — puts the inquiry back to
     * "In Progress" (not "Pending", since it's already been looked at)
     * so it reappears in the actionable list with the reply box back.
     */
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

    $replies = $inquiry->replies;
    $firstReply = $replies->first();

    // `BotManController::recordInquiryReply()` logs every unhandled /
    // off-topic patient message twice: once into `chatbot_logs`
    // (what `$inquiry->log` points to) and once into `inquiry_replies`
    // (sender "Patient") so it shows up in the same thread as
    // Admin/Staff's replies. For a brand-new inquiry those two records
    // hold the exact same text, which duplicated the very first bubble
    // in this view. If the first reply is a Patient message identical
    // to the log's text, it's that duplicate — skip the log-only entry
    // and let the (richer, attachment-aware) reply entry represent it
    // instead. Inquiries created via the standalone "Submit Inquiry"
    // flow have no matching reply at all, so they're untouched and
    // still fall back to the log entry below.
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
        ];
    }

    $lastMessage = collect($conversation)->last()['text'] ?? '(no message)';

    return [
        'id'           => $inquiry->inquiry_id,
        'name'         => $displayName,
        'inquiryCode'  => 'INQ-' . str_pad((string) $inquiry->inquiry_id, 3, '0', STR_PAD_LEFT),
        'avatar'       => strtoupper(substr($initials, 0, 2)),
        'patientId'    => $patientLabel,
        'department'   => $inquiry->inquiry_type,
        'lastMessage'  => $lastMessage,
        'timestamp'    => $timestamp->toIso8601String(),
        'unread'       => $inquiry->resolved_status === 'Pending',
        'status'       => $statusMap[$inquiry->resolved_status] ?? 'pending',
        'rawStatus'    => $inquiry->resolved_status,
        'conversation' => $conversation,
    ];
}
}