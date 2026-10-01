<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use Illuminate\Support\Facades\DB;
use App\Models\admin_models\Feedback;
use App\Conversations\Concerns\HandlesGlobalCommands;
use App\Conversations\Concerns\HandlesOffTopic;
use App\Conversations\Concerns\HandlesRateLimit;
use App\Services\SentimentAnalysisService;
use App\Services\RatingSentimentFallback;
use App\Services\NotificationService;
use App\Models\Notification;
use App\Models\Staff\InquiryReply;

class ReviewConversation extends Conversation
{
    use HandlesGlobalCommands, HandlesOffTopic, HandlesRateLimit;

    // Ratings at or below this get an apology + an offer for staff to
    // reach out.
    protected const LOW_RATING_MAX = 3;

    protected $patientId;
    protected $patientName;
    protected $rating;
    protected $feedbackId;
    protected $contactEmail;

    public function __construct($patientId = null, $patientName = null)
    {
        $this->patientId = $patientId;
        $this->patientName = $patientName;
    }

    public function run()
    {
        $this->askRating();
    }

    // Ask the patient for a star rating.
    public function askRating()
    {
        $question = Question::create('How would you rate your experience with us?')
            ->fallback('Please tap one of the star options above.')
            ->addButtons([
                Button::create('1')->value('1'),
                Button::create('2')->value('2'),
                Button::create('3')->value('3'),
                Button::create('4')->value('4'),
                Button::create('5')->value('5'),
            ]);

        $this->ask($question, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, fn() => $this->askRating());
            }

            // Free-typed question instead of a star tap → route to staff
            // rather than trying to cast it to a rating.
            if ($this->handleOffTopicIfAny($answer, fn() => $this->askRating())) {
                return;
            }

            $value = (int) $answer->getValue();

            if ($value < 1 || $value > 5) {
                $this->say('Please tap one of the star ratings from 1 to 5 above.');
                return $this->askRating();
            }

            $this->rating = $value;

            // 3 or below: no comment prompt. Apologize, tell the patient
            // Admin/Staff will email them, and open a follow-up for staff.
            if ($this->isLowRating()) {
                $this->say("We're truly sorry that your experience with us wasn't a good one. Thank you for being honest — we take this seriously. Our staff/admin will reach out to you via email.");
                return $this->startLowRatingFollowUp();
            }

            $this->askFeedbackText();
        });
    }

    // Uses the email already on file ONLY for a patient who has a schedule
    // visit history (a real patient record with an email). Anonymous
    // ratings (no patient, no visit history, or no usable email on file)
    // are asked for an email once, since staff will be reaching out by email.
    protected function startLowRatingFollowUp()
    {
        $email = null;

        if ($this->patientId) {
            try {
                $hasVisitHistory = DB::table('schedule_visit')
                    ->where('patient_id', $this->patientId)
                    ->exists();

                if ($hasVisitHistory) {
                    $email = DB::table('patients')
                        ->where('patient_id', $this->patientId)
                        ->value('patient_email');
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Could not load patient email: ' . $e->getMessage());
            }
        }

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->contactEmail = $email;
            return $this->submitReview(null);
        }

        // Anonymous (or nothing usable on file): ask for an email.
        return $this->askContactEmail();
    }

    protected function askContactEmail(string $prompt = 'Please type your email address so our staff can reach you:')
    {
        $this->ask($prompt, function (Answer $answer) use ($prompt) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, fn() => $this->askContactEmail($prompt));
            }

            $email = trim($answer->getText());

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->askContactEmail('That doesn\'t look like a valid email address. Please try again (e.g. name@example.com):');
            }

            $this->contactEmail = $email;
            $this->submitReview(null);
        });
    }

    protected function isLowRating(): bool
    {
        return $this->rating !== null && $this->rating <= self::LOW_RATING_MAX;
    }

    // Ask for optional comments and handle skip/yes/text replies.
    public function askFeedbackText(
        $prompt = 'Please add any comments about your experience, or type "skip" to continue without comments.'
    ) {
        $this->ask($prompt, function (Answer $answer) use ($prompt) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, fn() => $this->askFeedbackText($prompt));
            }

            $text = trim($answer->getText());
            $normalized = strtolower($text);

            $skipWords = ['skip', 'no', 'none', 'n/a', 'wala', 'hindi'];
            $yesWords  = ['yes', 'yep', 'yeah', 'sure', 'oo', 'opo', 'sige'];

            if (in_array($normalized, $skipWords, true)) {
                return $this->submitReview(null);
            }

            if (in_array($normalized, $yesWords, true)) {
                return $this->askFeedbackText('Great, please tell us your comments:');
            }

            if (mb_strlen($text) < 3) {
                return $this->askFeedbackText('Please share a bit more detail about your experience, or type "skip" to submit without comments:');
            }

            $this->submitReview($text);
        });
    }

    // Save the review and run sentiment analysis if there's a comment.
    protected function submitReview($text)
    {
        if ($this->tooManySubmissions('review')) {
            $this->say($this->submissionCooldownMessage());
            $this->askAnythingElse();
            return;
        }

        $saved = false;

        try {
            $logId = DB::table('chatbot_logs')->insertGetId([
                'user_id'      => $this->patientId,
                'user_message' => $text ?? '(no comment)',
                'bot_message'  => 'Review recorded',
            ]);

            // Create the feedback record.
            $feedback = Feedback::create([
                'log_id'        => $logId,
                'patient_id'    => $this->patientId,
                'feedback_text' => $text,
                'star_rating'   => $this->rating,
                'submitted_at'  => now(),
            ]);
            $this->feedbackId = $feedback->feedback_id;
            $saved = true;

            // Notification (flags low ratings so admin notices them).
            try {
                NotificationService::newFeedback($this->patientName ?: 'A patient', $feedback->feedback_id, $this->rating);
            } catch (\Throwable $notifyError) {
                \Illuminate\Support\Facades\Log::error('Failed to create feedback notification: ' . $notifyError->getMessage());
            }

            if (!empty($text)) {
                $result = app(SentimentAnalysisService::class)->analyze($text);

                // ML service down/unreachable? Fall back to a rating-based
                // label instead of leaving this feedback unclassified.
                $label = $result['sentiment_label'] ?? RatingSentimentFallback::labelFor($this->rating);
                $confidence = $result['confidence_score'] ?? null;

                $feedback->sentimentResult()->create([
                    'sentiment_label'  => $label,
                    'confidence_score' => $confidence,
                    'analyzed_at'      => now(),
                ]);
            }

            // Low ratings already got the apology message above.
            if (!$this->isLowRating()) {
                $this->say("Thank you for your feedback!\n\nYou rated us {$this->rating}/5. We appreciate you taking the time to help us improve.");
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('submitReview failed: ' . $e->getMessage());
            $this->say("⚠️ We couldn't record your review right now. Please try again, or contact us directly.");
        }

        // Low rating → open a follow-up for Admin/Staff (they'll email the patient).
        if ($saved && $this->isLowRating()) {
            return $this->createStaffFollowUp($text);
        }

        // Review saved (4-5 stars): end the conversation and bring the 4
        // main options straight back (no "anything else?" question).
        if ($saved) {
            $this->bot->startConversation(new AnythingElseConversation(
                $this->patientId,
                $this->patientName,
                true,
                ''
            ));
            return;
        }

        $this->askAnythingElse();
    }

    // Opens a staff-visible inquiry thread tied to this low rating. Staff
    // reply from the Inquiries page and the reply shows up in this same
    // chat (the widget polls /api/chat/{id}/updates).
    protected function createStaffFollowUp(?string $comment)
    {
        $saved = false;

        try {
            $conversationId = (string) $this->bot->getMessage()->getSender();
            $name = $this->patientName ?: ($this->patientId ? "Patient #{$this->patientId}" : 'A guest');

            $summary = "Low rating follow-up. The patient rated us {$this->rating}/5."
                . ($this->contactEmail ? " Please reach out by email: {$this->contactEmail}." : '')
                . ($comment ? " Comment: \"{$comment}\"" : ' No comment was left.');

            $logId = DB::table('chatbot_logs')->insertGetId([
                'user_id'         => $this->patientId,
                'conversation_id' => $conversationId !== '' ? $conversationId : null,
                'user_message'    => $summary,
                'bot_message'     => '(no reply)', // column is NOT NULL
                'chat_time'       => now(),
            ]);

            $inquiryId = DB::table('inquiries')->insertGetId([
                'patient_id'      => $this->patientId,
                'guest_name'      => $this->patientId ? null : ($this->patientName ?: null),
                'log_id'          => $logId,
                'conversation_id' => $conversationId !== '' ? $conversationId : null,
                'inquiry_type'    => 'Low Rating Follow-up',
                'resolved_status' => 'Pending',
                'created_at'      => now(),
            ]);

            InquiryReply::create([
                'inquiry_id' => $inquiryId,
                'sender'     => 'Patient',
                'message'    => $summary,
            ]);

            try {
                NotificationService::lowRatingFollowUp($name, (int) $this->rating, $inquiryId);
            } catch (\Throwable $notifyError) {
                \Illuminate\Support\Facades\Log::error('Failed to create low-rating notification: ' . $notifyError->getMessage());
            }

            $saved = true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('createStaffFollowUp failed: ' . $e->getMessage());
            $this->say("⚠️ We couldn't notify our staff right now. Please try again later, or contact us directly.");
        }

        if ($saved) {
            // Email received and follow-up opened: thank the patient, end
            // this flow, and bring the 4 main options back (no
            // "anything else?" question).
            $this->bot->startConversation(new AnythingElseConversation(
                $this->patientId,
                $this->patientName,
                true,
                'Thank you for your feedback! Our staff/admin will reach out to you through your email.'
            ));
            return;
        }

        $this->askAnythingElse();
    }

    // Ends the flow with the Yes/No "anything else?" prompt.
    protected function askAnythingElse()
    {
        $this->bot->startConversation(new AnythingElseConversation($this->patientId, $this->patientName));
    }
}