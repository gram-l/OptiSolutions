<?php
// app/Http/Controllers/Api/InquiryController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff\Inquiry;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InquiryController extends Controller
{
    /** GET /api/inquiries */
    public function index()
    {
        $inquiries = Inquiry::with('log')->orderBy('inquiry_id', 'desc')->get();

        return response()->json($inquiries->map->toApiArray());
    }

    /** GET /api/inquiries/{id} */
    public function show($id)
    {
        $inquiry = Inquiry::with('log')->findOrFail($id);

        return response()->json($inquiry->toApiArray());
    }

    /**
     * GET /api/inquiries/{inquiry}/messages
     * Ibinabalik ang staff reply bilang listahan ng messages (0 o 1 item
     * lang, dahil iisang 'inquiry_reply' column lang ang meron tayo sa
     * ngayon, hindi buong thread). Ang orihinal na patient message ay
     * hinahandle na sa Flutter side gamit ang widget.initialMessage.
     */
    public function messages($id)
    {
        $inquiry = Inquiry::findOrFail($id);

        $messages = [];

        if (!empty($inquiry->inquiry_reply)) {
            $messages[] = [
                'sender'  => 'Staff',
                'message' => $inquiry->inquiry_reply,
                'time'    => $inquiry->replied_at
                    ? Carbon::parse($inquiry->replied_at)->format('g:i A')
                    : '',
                'isStaff' => true,
            ];
        }

        return response()->json($messages);
    }

    /**
     * POST /api/inquiries/{inquiry}/messages
     * Isusulat ang bagong reply papunta sa 'inquiry_reply' column.
     * PAALALA: kapag may bago pang reply, papalitan lang nito ang luma
     * (dahil isang column lang ito) — hindi ito totoong multi-message
     * thread. Kung kailangan mo ng buong history, gagawa tayo ng hiwalay
     * na 'inquiry_replies' table.
     */
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $inquiry = Inquiry::findOrFail($id);

        $inquiry->inquiry_reply = $request->message;
        $inquiry->replied_at = now();

        if ($inquiry->resolved_status === 'Pending') {
            $inquiry->resolved_status = 'In Progress';
        }

        $inquiry->save();

        return response()->json([
            'sender'  => 'Staff',
            'message' => $inquiry->inquiry_reply,
            'time'    => Carbon::parse($inquiry->replied_at)->format('g:i A'),
            'isStaff' => true,
        ]);
    }

    /** POST /api/inquiries/{id}/resolve */
    public function resolve($id)
    {
        $inquiry = Inquiry::findOrFail($id);
        $inquiry->resolved_status = 'Resolved';
        $inquiry->save();

        return response()->json([
            'message' => 'Inquiry resolved!',
            'inquiry' => $inquiry->fresh('log')->toApiArray(),
        ]);
    }
}