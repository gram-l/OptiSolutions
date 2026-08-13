<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use Illuminate\Support\Facades\DB;
use App\Models\admin_models\Complaint;
use App\Services\SentimentAnalysisService;

class ComplaintConversation extends Conversation
{
    protected $patientId;
    protected $patientName;

    public function __construct($patientId = null, $patientName = null)
    {
        $this->patientId = $patientId;
        $this->patientName = $patientName;
    }

    public function run()
    {
        $this->askComplaint();
    }

    public function askComplaint()
    {
        $this->ask('Please describe your concern in detail. We would like to hear your experience from us.', function (Answer $answer) {
            $text = trim($answer->getText());

            if (strlen($text) < 10) {
                $this->say('Please provide a more detailed description (at least 10 characters).');
                return $this->askComplaint();
            }

            $this->submitComplaint($text);
        });
    }

    protected function submitComplaint($text)
    {
        try {
            $logId = DB::table('chatbot_logs')->insertGetId([
                'user_id'      => 1,
                'user_message' => $text,
                'bot_message'  => 'Complaint recorded',
            ]);

            // Complaint::create() instead of DB::table()->insert()
            // — gives us a model instance so we can attach a category
            $complaint = Complaint::create([
                'patient_id'     => $this->patientId,
                'log_id'         => $logId,
                'complaint_text' => $text,
                'category'       => app(SentimentAnalysisService::class)->categorize($text),
            ]);

            $this->say("Complaint Recorded\n\nReference #: {$complaint->complaint_id}\nWe acknowledge receipt of your concern.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('submitComplaint failed: ' . $e->getMessage());
            $this->say("We couldn't record your complaint right now. Please try again, or contact us directly.");
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