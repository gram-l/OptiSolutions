<?php

namespace App\Http\Controllers;

use App\Models\admin_models\SentimentResult;
use App\Services\SentimentAnalysisService;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request, SentimentAnalysisService $sentimentService)
    {
        $validated = $request->validate([
            'transaction_code' => 'required|string|max:80',
            'patient_id'    => 'nullable|integer|exists:patients,patient_id',
            'log_id'        => 'nullable|integer|exists:chatbot_logs,log_id',
            'feedback_text' => 'nullable|string',
            'star_rating'   => 'required|integer|min:1|max:5',
        ]);

        $feedback = app(\App\Services\VisitReviewService::class)->submit(
            $validated['transaction_code'], $validated['star_rating'], $validated['feedback_text'] ?? null, null, true,
        );

        // Classify right away so the review can show up on the homepage
        // immediately, instead of waiting for `php artisan feedback:analyze`.
        if (trim($feedback->feedback_text ?? '') !== '') {
          try {
            $result = $sentimentService->analyze($feedback->feedback_text);

            // Analysis can remain pending without losing the saved review.
            if (isset($result['sentiment_label'], $result['confidence_score'])) SentimentResult::create([
                'feedback_id'      => $feedback->feedback_id,
                'sentiment_label'  => $result['sentiment_label'],
                'confidence_score' => $result['confidence_score'],
                'analyzed_at'      => now(),
            ]);
          } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('Review saved; sentiment analysis remains pending.', ['feedback_id' => $feedback->feedback_id]);
          }
        }

        return response()->json(['feedback_id' => $feedback->feedback_id], 201);
    }
}
