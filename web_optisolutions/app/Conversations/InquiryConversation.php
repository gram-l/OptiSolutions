<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use App\Models\Staff\AppNotification;
use Illuminate\Support\Facades\DB;

class InquiryConversation extends Conversation
{
    protected $inquiryType;
    protected $message;
    protected $patientId;

    public function run()
    {
        $this->patientId = $this->bot->userStorage()->find()['patient_id'] ?? null;

        $this->askInquiryType();
    }

    protected function askInquiryType()
    {
        $question = Question::create('What is your inquiry about?')
            ->fallback('Please choose an option from the buttons above.')
            ->addButtons([
                Button::create('Billing')->value('Billing'),
                Button::create('Medical')->value('Medical'),
                Button::create('Appointment')->value('Appointment'),
                Button::create('General')->value('General'),
            ]);

        $this->ask($question, function (Answer $answer) {
            $this->inquiryType = $answer->getValue() ?: 'General';
            $this->askMessage();
        });
    }

    protected function askMessage($prompt = 'Please type your question or concern:')
    {
        $this->ask($prompt, function (Answer $answer) {
            $text = trim($answer->getText());

            if ($text === '') {
                return $this->askMessage('⚠️ Message cannot be empty. Please type your question or concern:');
            }

            $this->message = $text;
            $this->submitInquiry();
        });
    }

    protected function submitInquiry()
    {
        $this->say('Submitting your inquiry...');

        try {
            $logId = DB::table('chatbot_logs')->insertGetId([
                'user_id'      => $this->patientId,
                'user_message' => $this->message,
                'bot_message'  => null,
                'chat_time'    => now(),
            ]);

            DB::table('inquiries')->insert([
                'patient_id'      => $this->patientId,
                'log_id'          => $logId,
                'inquiry_type'    => $this->inquiryType,
                'resolved_status' => 'Pending',
            ]);

            AppNotification::create([
                'icon'    => 'question_answer',
                'title'   => 'New Inquiry',
                'message' => "A new {$this->inquiryType} inquiry has been submitted.",
                'is_read' => false,
                'color'   => '2196F3',
            ]);

            $this->say("Thank you! Your {$this->inquiryType} inquiry has been sent to our staff. We'll get back to you as soon as possible.");
        } catch (\Exception $e) {
            $this->say("⚠️ We couldn't submit your inquiry right now. Please try again, or contact us directly at 0985 475 5511.");
        }
    }
}