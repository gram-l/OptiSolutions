<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'    => 'nullable|integer|exists:patients,patient_id',
            'log_id'        => 'nullable|integer|exists:chatbot_logs,log_id',
            'feedback_text' => 'nullable|string',
            'star_rating'   => 'required|integer|min:1|max:5',
        ]);

        $feedback = Feedback::create($validated);

        return response()->json(['feedback_id' => $feedback->feedback_id], 201);
    }
}