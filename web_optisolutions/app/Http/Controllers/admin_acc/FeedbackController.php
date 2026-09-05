<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\admin_models\Complaint;

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
                'rating' => (int) $f->star_rating,
                'comment' => $f->feedback_text,
                'sentiment' => trim($f->feedback_text ?? '') === ''
                    ? 'no_comment'
                    : ($f->sentimentResult ? strtolower($f->sentimentResult->sentiment_label) : 'pending'),
                'date' => $f->submitted_at,
            ];
        });

    $complaints = Complaint::orderByDesc('created_at')
        ->get()
        ->map(function ($c) {
            return [
                'complaint_id' => $c->complaint_id,
                'complaint_text' => $c->complaint_text,
                'category' => $c->category ?? 'Uncategorized',
                'created_at' => $c->created_at,
            ];
        });

    return view('admin_acc.feedback', [
        'feedbackData' => $feedback,
        'complaintData' => $complaints,
    ]);
}
public function store(Request $request, SentimentAnalysisService $sentimentService)
{
    $validated = $request->validate([
        'log_id' => 'nullable|integer',
        'patient_id' => 'nullable|integer',
        'feedback_text' => 'nullable|string',
        'star_rating' => 'required|integer|min:1|max:5',
    ]);

    $feedback = Feedback::create(array_merge($validated, ['submitted_at' => now()]));

    // Only classify if there's actual comment text
    if (trim($feedback->feedback_text ?? '') !== '') {
        $result = $sentimentService->analyze($feedback->feedback_text);
        if ($result) {
            $feedback->sentimentResult()->create([
                'sentiment_label' => $result['sentiment_label'],
                'confidence_score' => $result['confidence_score'],
                'analyzed_at' => now(),
            ]);
        }
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

public function diagnoseRootCauses()
{
    // Negative-classified feedback
    $negativeFeedback = Feedback::whereHas('sentimentResult', function ($q) {
        $q->where('sentiment_label', 'Negative');
    })->get()->map(function ($f) {
        return [
            'id' => $f->feedback_id,
            'text' => $f->feedback_text,
            'source' => 'feedback',
        ];
    });

    // All complaints (inherently negative, no sentiment check needed)
    $complaints = Complaint::all()->map(function ($c) {
        return [
            'id' => $c->complaint_id,
            'text' => $c->complaint_text,
            'source' => 'complaint',
        ];
    });

    $items = $negativeFeedback->concat($complaints)->values();

    $response = Http::post('http://127.0.0.1:8001/api/diagnose/', [
        'items' => $items,
    ]);

    if ($response->successful()) {
        return response()->json($response->json());
    }

    return response()->json(['error' => 'Diagnosis failed'], 500);
}
}