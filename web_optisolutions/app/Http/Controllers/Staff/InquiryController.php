<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Inquiry;
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
        $inquiry = Inquiry::with('log')->findOrFail($id);

        return view('staff.inquiries.show', compact('inquiry'));
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $inquiry = Inquiry::findOrFail($id);

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