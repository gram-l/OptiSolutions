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

class ReviewConversation extends Conversation
{
    use HandlesGlobalCommands, HandlesOffTopic, HandlesRateLimit;

    protected $patientId;
    protected $patientName;
    protected $rating;

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
            $this->askFeedbackText();
        });
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
            $this->backToMainMenu();
            return;
        }

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
//notification
        try {
            NotificationService::newFeedback($this->patientName ?: 'A patient', $feedback->feedback_id);
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

            $this->say("Thank you for your feedback!\n\nYou rated us {$this->rating}/5. We appreciate you taking the time to help us improve.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('submitReview failed: ' . $e->getMessage());
            $this->say("⚠️ We couldn't record your review right now. Please try again, or contact us directly.");
        }

        $this->backToMainMenu();
    }

    // Show the main menu and route the chosen action.
    protected function backToMainMenu()
    {
        $question = Question::create('')
            ->fallback('Please choose an option from the buttons above.')
            ->addButtons([
                Button::create('Schedule Visit')->value('schedule visit'),
                Button::create('General Information')->value('general information'),
                Button::create('Submit Review/Rating')->value('submit review/rating'),
                Button::create('Submit Complaint')->value('submit complaint'),
            ]);

        $this->ask($question, function (Answer $answer) {
            // This menu is shown AFTER a review finishes (or fails). Tapping the
            // same button again should start a brand-new one. Without this,
            // handleGlobalCommand() sees "already in this conversation" and just
            // re-shows this menu, so the button appears to do nothing.
            if (in_array($this->normalizeCommand($answer), ['review', 'submit review/rating'], true)) {
                return $this->askRating();
            }

            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, fn() => $this->backToMainMenu());
            }

            if ($this->handleOffTopicIfAny($answer, fn() => $this->backToMainMenu())) {
                return;
            }

            $this->say('Please choose one of the options above.');
            $this->backToMainMenu();
        });
    }
}