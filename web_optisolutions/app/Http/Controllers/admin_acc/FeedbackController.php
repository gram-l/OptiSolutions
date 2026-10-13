<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\admin_models\Complaint;
use App\Services\SentimentAnalysisService;

class FeedbackController extends Controller
{
    public function index()
{
    $feedback = Feedback::with('sentimentResult')
        ->leftJoin('schedule_visit as review_visit', 'feedback.visit_id', '=', 'review_visit.visit_id')
        ->leftJoin('doctors as review_doctor', 'review_visit.doctor_id', '=', 'review_doctor.doctor_id')
        ->select('feedback.*', 'review_visit.doctor_id as review_doctor_id', 'review_doctor.doctor_name as review_doctor_name', 'review_visit.service_type as review_service')
        ->orderBy('feedback.submitted_at', 'desc')
        ->get()
        ->map(function ($f) {
            return [
                'id' => $f->feedback_id,
                'rating' => (int) $f->star_rating,
                'doctor_id' => $f->review_doctor_id,
                'doctor' => $f->review_doctor_name,
                'service' => $f->review_service,
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

    $items = $feedback->where('sentiment', 'negative')->map(fn ($entry) => [
        'id' => $entry['id'], 'text' => $entry['comment'], 'source' => 'feedback',
    ])->concat($complaints->map(fn ($entry) => [
        'id' => $entry['complaint_id'], 'text' => $entry['complaint_text'], 'source' => 'complaint',
    ]))->values();
    $diagnosis = $items->isEmpty() ? ['details' => []] : app(SentimentAnalysisService::class)->diagnose($items->all());
    $details = collect($diagnosis['details'] ?? [])->keyBy(fn ($entry) => $entry['source'] . ':' . $entry['id']);
    $feedback = $feedback->map(function ($entry) use ($details, $diagnosis) {
        $entry['issue_categories'] = $details->get('feedback:' . $entry['id'])['matched_categories'] ?? [];
        $entry['issues_analyzed'] = $diagnosis !== null;
        return $entry;
    });
    $complaints = $complaints->map(function ($entry) use ($details, $diagnosis) {
        $categories = $details->get('complaint:' . $entry['complaint_id'])['matched_categories'] ?? [];
        $stored = strtolower(trim($entry['category'] ?? ''));
        if ($categories) {
            $entry['category'] = $categories[0];
        }
        $entry['issue_categories'] = $categories ?: (!in_array($stored, ['', 'other', 'uncategorized'], true) ? [$stored] : []);
        $entry['issues_analyzed'] = $diagnosis !== null;
        return $entry;
    });

    return view('admin_acc.feedback', [
        'feedbackData' => $feedback,
        'complaintData' => $complaints,
    ]);
}
public function store(Request $request, SentimentAnalysisService $sentimentService)
{
    $validated = $request->validate([
        'transaction_code' => 'required|string|max:80',
        'log_id' => 'nullable|integer',
        'patient_id' => 'nullable|integer',
        'feedback_text' => 'nullable|string',
        'star_rating' => 'required|integer|min:1|max:5',
    ]);

    $feedback = app(\App\Services\VisitReviewService::class)->submit(
        $validated['transaction_code'], $validated['star_rating'], $validated['feedback_text'] ?? null, null, true,
    );

    // Only classify if there's actual comment text
    if (trim($feedback->feedback_text ?? '') !== '') {
      try {
        $result = $sentimentService->analyze($feedback->feedback_text);

        // Analysis can remain pending without losing the saved review.
        if (isset($result['sentiment_label'], $result['confidence_score'])) $feedback->sentimentResult()->create([
            'sentiment_label' => $result['sentiment_label'],
            'confidence_score' => $result['confidence_score'],
            'analyzed_at' => now(),
        ]);
      } catch (\Throwable $exception) {
        \Illuminate\Support\Facades\Log::warning('Review saved; sentiment analysis remains pending.', ['feedback_id' => $feedback->feedback_id]);
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
