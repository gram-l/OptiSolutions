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
        // Eager-load ang 'log' relationship para makuha ang user_message
        $inquiries = Inquiry::with('log')->orderBy('inquiry_id', 'desc')->get();

        return view('staff.inquiries.index', compact('inquiries'));
    }

    public function show($id)
    {
        // 'replies' = buong thread ng Staff/Admin/Patient messages, hindi
        // lang yung dating iisang `inquiry_reply` column.
        $inquiry = Inquiry::with(['log', 'replies'])->findOrFail($id);

        return view('staff.inquiries.show', compact('inquiry'));
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $inquiry = Inquiry::findOrFail($id);

        // Isulat sa bagong thread table — parehong makikita ito ng Admin
        // web panel at ng Admin/Staff Flutter apps (iisang shared table).
        InquiryReply::create([
            'inquiry_id' => $inquiry->inquiry_id,
            'user_id'    => $request->user()->user_id ?? null,
            'sender'     => 'Staff',
            'message'    => $request->message,
        ]);

        // Panatilihin ding updated ang legacy columns para sa backward
        // compatibility ng ibang bahagi ng system na baka umaasa pa dito.
        $inquiry->inquiry_reply = $request->message;
        $inquiry->replied_at = now();

        // Tamang column name: resolved_status (hindi "status")
        if ($inquiry->resolved_status === 'Pending') {
            $inquiry->resolved_status = 'In Progress';
        }

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