<?php
// app/Http/Controllers/Api/InquiryController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff\Inquiry;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    /** GET /api/inquiries */
    public function index()
    {
        return Inquiry::all()->map->toApiArray();
    }

    /** GET /api/inquiries/{inquiry}/messages — used by inquiry_chat.dart */
    public function messages(Inquiry $inquiry)
    {
        // Note: dating may $inquiry->update(['is_new' => false]) dito, pero
        // walang 'is_new' column sa table at wala rin ito sa $fillable ng
        // Inquiry model — nagdudulot ito ng MassAssignmentException sa
        // Laravel 12 (naka-enable ang strict mode by default), kaya
        // nag-e-error ang buong request na ito. Tinanggal muna.
        return $inquiry->messages()->get()->map->toApiArray();
    }

    /** POST /api/inquiries/{inquiry}/messages — sending a staff reply */
    public function sendMessage(Request $request, Inquiry $inquiry)
    {
        $request->validate(['message' => 'required|string']);

        $message = $inquiry->messages()->create([
            'sender' => 'Staff',
            'message' => $request->message,
            'is_staff' => true,
        ]);

        return $message->toApiArray();
    }
}