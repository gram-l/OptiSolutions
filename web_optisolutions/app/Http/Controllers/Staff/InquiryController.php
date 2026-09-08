<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Inquiry;
use App\Models\Staff\InquiryReply;
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

        // Shared table, visible to both Admin and Staff
        InquiryReply::create([
            'inquiry_id' => $inquiry->inquiry_id,
            'user_id'    => $request->user()->user_id ?? null,
            'sender'     => 'Staff',
            'message'    => $request->message,
        ]);

        // Keep legacy columns updated too, for backward compatibility
        $inquiry->inquiry_reply = $request->message;
        $inquiry->replied_at = now();

        $inquiry->save();

        return redirect()->route('staff.inquiries.show', $id)->with('success', 'Reply sent!');
    }

    public function resolve($id)
    {
        $inquiry = Inquiry::findOrFail($id);
        $inquiry->resolved_status = 'Resolved';
        $inquiry->save();

        return redirect()->route('staff.inquiries.show', $id)->with('success', 'Inquiry resolved!');
    }
}