<?php

namespace App\Services;

use App\Models\admin_models\Feedback;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VisitReviewService
{
    public const CODE_PATTERN = '/^(?:[A-Z0-9]{20}|[A-F0-9]{32})$/';

    public static function attendanceLabel(object $visit): string
    {
        if ($visit->visited) return 'Visited';
        if ($visit->visit_date && \Carbon\Carbon::parse($visit->visit_date, 'Asia/Manila')->toDateString() < now('Asia/Manila')->toDateString()) return 'Did not visit';
        return 'Pending';
    }

    public static function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 20; $i++) $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        return $code;
    }

    public static function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($code)));
    }

    private function eligibleVisit(string $code)
    {
        return DB::table('schedule_visit')
            ->where('review_pass_hash', hash('sha256', self::normalizeCode($code)))
            ->where('visited', true)->whereNotNull('review_pass_sent_at')
            ->whereNull('review_pass_used_at');
    }

    public function verify(string $code): ?object
    {
        if (!preg_match(self::CODE_PATTERN, self::normalizeCode($code))) return null;
        $visit = $this->eligibleVisit($code)->first(['visit_id', 'patient_id', 'doctor_id', 'service_type']);
        if (!$visit || DB::table('feedback')->where('visit_id', $visit->visit_id)->exists()) return null;
        return $visit;
    }

    public function submit(string $code, int $rating, ?string $comment, ?int $logId = null, bool $createLog = false): Feedback
    {
        Validator::make(['transaction_code' => self::normalizeCode($code), 'star_rating' => $rating], [
            'transaction_code' => ['required', 'regex:' . self::CODE_PATTERN], 'star_rating' => 'required|integer|min:1|max:5',
        ])->validate();

        return DB::transaction(function () use ($code, $rating, $comment, $logId, $createLog) {
            $visit = $this->eligibleVisit($code)->lockForUpdate()->first();
            if (!$visit || DB::table('feedback')->where('visit_id', $visit->visit_id)->exists()) {
                throw ValidationException::withMessages(['transaction_code' => 'The transaction code is invalid, has already been used, or does not belong to a completed visit.']);
            }
            $comment = trim($comment ?? '') ?: null;
            if ($createLog) {
                $logId = DB::table('chatbot_logs')->insertGetId([
                    'user_id' => $visit->patient_id, 'user_message' => $comment ?? '(no comment)',
                    'bot_message' => 'Review recorded', 'chat_time' => now(),
                ]);
            }
            $feedback = Feedback::create([
                'visit_id' => $visit->visit_id, 'patient_id' => $visit->patient_id,
                'log_id' => $logId, 'feedback_text' => $comment, 'star_rating' => $rating, 'submitted_at' => now(),
            ]);
            DB::table('schedule_visit')->where('visit_id', $visit->visit_id)->update(['review_pass_used_at' => now()]);
            return $feedback;
        });
    }
}
