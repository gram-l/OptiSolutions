<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\admin_models\SentimentResult;
use App\Services\RatingSentimentFallback;
use App\Services\SentimentAnalysisService;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request, SentimentAnalysisService $sentimentService)
    {
        $validated = $request->validate([
            'patient_id'    => 'nullable|integer|exists:patients,patient_id',
            'log_id'        => 'nullable|integer|exists:chatbot_logs,log_id',
            'feedback_text' => 'nullable|string',
            'star_rating'   => 'required|integer|min:1|max:5',
        ]);

        $feedback = Feedback::create($validated);

        // Classify right away so the review can show up on the homepage
        // immediately, instead of waiting for `php artisan feedback:analyze`.
        if (trim($feedback->feedback_text ?? '') !== '') {
            $result = $sentimentService->analyze($feedback->feedback_text);

            // ML service down/unreachable? Fall back to a rating-based
            // label instead of leaving this feedback unclassified.
            $label = $result['sentiment_label'] ?? RatingSentimentFallback::labelFor($feedback->star_rating);
            $confidence = $result['confidence_score'] ?? null;

            SentimentResult::create([
                'feedback_id'      => $feedback->feedback_id,
                'sentiment_label'  => $label,
                'confidence_score' => $confidence,
                'analyzed_at'      => now(),
            ]);
        }

        return response()->json(['feedback_id' => $feedback->feedback_id], 201);
    }
}