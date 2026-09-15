<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Inquiry;
use App\Models\Staff\InquiryReply;
use App\Services\TypingStatusService;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function index()
    {
        // Eager-load log and replies, should be general for all inquiries
        $inquiries = Inquiry::with(['log', 'replies'])->orderBy('inquiry_id', 'desc')->get();

        return view('staff.inquiries.index', compact('inquiries'));
    }

    public function show($id)
    {
        $inquiries = Inquiry::with(['log', 'replies'])->orderBy('inquiry_id', 'desc')->get();

        $inquiry = Inquiry::with(['log', 'replies'])->findOrFail($id);

        return view('staff.inquiries.show', compact('inquiries', 'inquiry'));
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $inquiry = Inquiry::findOrFail($id);

        // Shared table, visible to both Admin and Staff.
        // createUnlessDuplicate guards against a double-click on "Send"
        // (or a retried request) inserting the same reply twice — which
        // would otherwise show up as two identical bubbles once the
        // patient's widget polls for updates.
        InquiryReply::createUnlessDuplicate([
            'inquiry_id' => $inquiry->inquiry_id,
            'user_id'    => $request->user()->user_id ?? null,
            'sender'     => 'Staff',
            'message'    => $request->message,
        ]);

        // Keep legacy columns updated too, for backward compatibility
        $inquiry->inquiry_reply = $request->message;
        $inquiry->replied_at = now();

        $inquiry->save();

        // Reply is in — drop the typing flag so the patient's indicator
        // doesn't sit there until it naturally expires.
        if ($inquiry->conversation_id) {
            TypingStatusService::setTyping('staff', $inquiry->conversation_id, false);
        }

        return redirect()->route('staff.inquiries.show', $id)->with('success', 'Reply sent!');
    }

    public function resolve($id)
    {
        $inquiry = Inquiry::findOrFail($id);
        $inquiry->resolved_status = 'Resolved';
        $inquiry->save();

        if ($inquiry->conversation_id) {
            TypingStatusService::setTyping('staff', $inquiry->conversation_id, false);
        }

        return redirect()->route('staff.inquiries.show', $id)->with('success', 'Inquiry resolved!');
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