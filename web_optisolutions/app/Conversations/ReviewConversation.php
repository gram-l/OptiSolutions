<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use Illuminate\Support\Facades\DB;
use App\Models\admin_models\Feedback;
use App\Services\SentimentAnalysisService;

class ReviewConversation extends Conversation
{
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
            $this->rating = (int) $answer->getValue();
            $this->askFeedbackText();
        });
    }

    public function askFeedbackText()
    {
        $this->ask('Would you like to add any comments? (Or type "skip" to submit without comments)', function (Answer $answer) {
            $text = trim($answer->getText());

            if (strtolower($text) === 'skip') {
                $text = null;
            }

            $this->submitReview($text);
        });
    }

    protected function submitReview($text)
    {
        try {
            $logId = DB::table('chatbot_logs')->insertGetId([
                'user_id'      => 1,
                'user_message' => $text ?? '(no comment)',
                'bot_message'  => 'Review recorded',
            ]);

            // Feedback::create() instead of DB::table()->insert()
            // — this gives us a model instance back so we can attach
            // the sentiment result to it below
            $feedback = Feedback::create([
                'log_id'        => $logId,
                'patient_id'    => $this->patientId,
                'feedback_text' => $text,
                'star_rating'   => $this->rating,
                'submitted_at'  => now(),
            ]);

            if (!empty($text)) {
                $result = app(SentimentAnalysisService::class)->analyze($text);

                if ($result) {
                    $feedback->sentimentResult()->create([
                        'sentiment_label'  => $result['sentiment_label'],
                        'confidence_score' => $result['confidence_score'],
                        'analyzed_at'      => now(),
                    ]);
                }
            }

            $this->say("Thank you for your feedback!\n\nYou rated us {$this->rating}/5. We appreciate you taking the time to help us improve.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('submitReview failed: ' . $e->getMessage());
            $this->say("⚠️ We couldn't record your review right now. Please try again, or contact us directly.");
        }

        $this->backToMainMenu();
    }

    protected function backToMainMenu()
    {
        $question = Question::create('Is there anything else I can help you with?')
            ->fallback('Please choose an option from the buttons above.')
            ->addButtons([
                Button::create('Schedule Visit')->value('schedule visit'),
                Button::create('General Information')->value('general information'),
                Button::create('Submit Review/Rating')->value('submit review/rating'),
                Button::create('Submit Complaint')->value('submit complaint'),
            ]);

        $this->bot->reply($question);
    }
}