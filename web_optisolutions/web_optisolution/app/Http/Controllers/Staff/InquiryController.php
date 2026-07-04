<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\InquiryReply;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function index()
    {
        $inquiries = Inquiry::all();
        return view('staff.inquiries.index', compact('inquiries'));
    }

    public function show($id)
    {
        $inquiry = Inquiry::with('replies')->findOrFail($id);
        return view('staff.inquiries.show', compact('inquiry'));
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $inquiry = Inquiry::findOrFail($id);

        // Save reply
        InquiryReply::create([
            'inquiry_id' => $id,
            'sender' => 'staff',
            'message' => $request->message,
        ]);

        // Update inquiry status to 'In Progress' if pending
        if ($inquiry->status === 'Pending') {
            $inquiry->status = 'In Progress';
            $inquiry->save();
        }

        return redirect()->route('staff.inquiries.show', $id)->with('success', 'Reply sent!');
    }

    public function resolve($id)
    {
        $inquiry = Inquiry::findOrFail($id);
        $inquiry->status = 'Resolved';
        $inquiry->save();

        return redirect()->route('staff.inquiries.show', $id)->with('success', 'Inquiry resolved!');
    }
}