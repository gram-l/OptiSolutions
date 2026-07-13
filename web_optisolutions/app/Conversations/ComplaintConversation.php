<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use Illuminate\Support\Facades\DB;

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
        $this->ask('Please describe your concern in detail. Our patient relations team will respond within 24 hours:', function (Answer $answer) {
            $text = trim($answer->getText());

            if (strlen($text) < 10) {
                $this->say('⚠️ Please provide a more detailed description (at least 10 characters) so we can assist you better:');
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

            $complaintId = DB::table('complaints')->insertGetId([
                'patient_id'     => $this->patientId,
                'log_id'         => $logId,
                'complaint_text' => $text,
                'status'         => 'pending',
            ]);

            $this->say("Complaint Recorded\n\nReference #: {$complaintId}\nWe acknowledge receipt of your concern. Our team will reach out within 24 hours.");
        } catch (\Exception $e) {
            $this->say("⚠️ We couldn't record your complaint right now. Please try again, or contact us directly.");
        }

        $this->askWhatsNext();
    }

    protected function askWhatsNext()
    {
        $question = Question::create('Is there anything else you\'d like to do?')
            ->fallback('Please use the buttons above, or type "menu" anytime to return to the main menu.')
            ->addButtons([
                Button::create('⭐ Submit Review/Rating')->value('review'),
                Button::create("✅ No, I'm all set")->value('done'),
            ]);

        $this->ask($question, function (Answer $answer) {
            if ($answer->getValue() === 'review') {
                $this->bot->startConversation(new ReviewConversation($this->patientId, $this->patientName));
                return;
            }

            $this->say('Thank you for choosing PolyClinic Lipa! Have a great day! 😊');
        });
    }
}