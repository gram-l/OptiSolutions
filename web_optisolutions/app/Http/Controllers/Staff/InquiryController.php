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
        // ✅ FIX: kailangan din ipasa ang buong listahan ($inquiries) dahil
        // ginagamit ito ng left panel (conversations-list partial) sa
        // bagong split-view na layout ng show.blade.php.
        $inquiries = Inquiry::with('log')->orderBy('inquiry_id', 'desc')->get();

        $inquiry = Inquiry::with('log')->findOrFail($id);

        return view('staff.inquiries.show', compact('inquiries', 'inquiry'));
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $inquiry = Inquiry::findOrFail($id);

        $inquiry->inquiry_reply = $request->message;
        $inquiry->replied_at = now();

        // ✅ FIX: 'In Progress' ay hindi valid enum value sa
        // `resolved_status` column ng database (Pending/Resolved lang),
        // kaya nagiging cause ito ng "Data truncated" SQL error.
        // Mananatiling 'Pending' ang status hanggang i-click ni staff
        // yung "Resolve" button — ang "may reply na" indicator ay base
        // na lang sa inquiry_reply (tingnan ang toApiArray() sa model).
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