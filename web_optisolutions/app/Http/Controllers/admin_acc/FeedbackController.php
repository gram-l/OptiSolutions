<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FeedbackController extends Controller
{
    public function index()
    {
        $feedback = Feedback::with('sentimentResult')
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(function ($f) {
                return [
                    'id' => $f->feedback_id,
                    'patient' => 'Patient #' . $f->patient_id,
                    'rating' => (int) $f->star_rating,
                    'comment' => $f->feedback_text,
                    'sentiment' => $f->sentimentResult ? strtolower($f->sentimentResult->sentiment_label) : 'pending',
                    'date' => $f->submitted_at,
                ];
            });

        return view('admin_acc.feedback', ['feedbackData' => $feedback]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'log_id' => 'nullable|integer',
            'patient_id' => 'nullable|integer',
            'feedback_text' => 'required|string',
            'star_rating' => 'required|integer|min:1|max:5',
        ]);

        $feedback = Feedback::create(array_merge($validated, [
            'submitted_at' => now(),
        ]));

        // Call Django sentiment API
        $response = Http::post('http://127.0.0.1:8000/api/predict/', [
            'feedback_text' => $feedback->feedback_text,
        ]);

        if ($response->successful()) {
            $result = $response->json();

            $feedback->sentimentResult()->create([
                'sentiment_label' => $result['sentiment_label'],
                'confidence_score' => $result['confidence_score'],
                'analyzed_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Feedback submitted.');
    }

    // JSON endpoint for the Flutter app (mirrors DoctorController@apiIndex)
public function apiIndex()
{
    $feedback = Feedback::with('sentimentResult')
        ->orderBy('submitted_at', 'desc')
        ->get()
        ->map(function ($f) {
            return [
                'feedback_id' => $f->feedback_id,
                'patient_id' => $f->patient_id,
                'rating' => (int) $f->star_rating,
                'comment' => $f->feedback_text,
                'sentiment' => $f->sentimentResult
                    ? strtolower($f->sentimentResult->sentiment_label)
                    : 'pending',
                'confidence' => $f->sentimentResult
                    ? (float) $f->sentimentResult->confidence_score
                    : null,
                'date' => $f->submitted_at,
            ];
        });

    return response()->json($feedback);
}
}