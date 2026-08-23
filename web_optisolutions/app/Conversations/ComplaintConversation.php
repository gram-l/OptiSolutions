<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use Illuminate\Support\Facades\DB;
use App\Models\admin_models\Complaint;
use App\Services\ClinicInfoService;
use App\Services\SentimentAnalysisService;
use Closure;

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

    // Normalize the incoming answer for command matching.
    protected function normalizeCommand(Answer $answer): string
    {
        return strtolower(trim($answer->getValue() ?: $answer->getText()));
    }

    // Check if the answer is a menu command.
    protected function isGlobalCommand(Answer $answer): bool
    {
        return in_array($this->normalizeCommand($answer), [
            'schedule visit',
            'general information',
            'review', 'submit review/rating',
            'complaint', 'submit complaint',
            'cancel', 'menu',
        ], true);
    }

    // Route a menu command to the right handler.
    protected function handleGlobalCommand(Answer $answer, Closure $resumeCurrentStep)
    {
        $cmd = $this->normalizeCommand($answer);

        if ($cmd === 'schedule visit') {
            $this->bot->startConversation(new AppointmentConversation());
            return;
        }

        if ($cmd === 'general information') {
            $this->say(ClinicInfoService::infoCardMessage());
            $resumeCurrentStep();
            return;
        }

        if (in_array($cmd, ['review', 'submit review/rating'], true)) {
            $this->bot->startConversation(new ReviewConversation($this->patientId, $this->patientName));
            return;
        }

        if (in_array($cmd, ['complaint', 'submit complaint'], true)) {
            $resumeCurrentStep();
            return;
        }

        if (in_array($cmd, ['cancel', 'menu'], true)) {
            $this->say('Okay, cancelled. Type "Menu" anytime to start again.');
            return;
        }
    }

    // Ask the patient to describe their complaint.
    public function askComplaint(
        $prompt = 'Please describe your concern in detail. We would like to hear about your experience so we can address it properly.'
    ) {
        $this->ask($prompt, function (Answer $answer) use ($prompt) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, fn() => $this->askComplaint($prompt));
            }

            $text = trim($answer->getText());

            if (mb_strlen($text) < 10) {
                return $this->askComplaint('Please share a bit more detail about your concern (at least a full sentence) so we can better understand and address it:');
            }

            $this->submitComplaint($text);
        });
    }

    // Save the complaint and notify the patient.
    protected function submitComplaint($text)
    {
        try {
            $logId = DB::table('chatbot_logs')->insertGetId([
                'user_id'      => 1,
                'user_message' => $text,
                'bot_message'  => 'Complaint recorded',
            ]);

            // Create the complaint record.
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

    // Show the main menu and route the chosen action.
    protected function backToMainMenu()
    {
        $question = Question::create('Is there anything else I can help you with?')
            ->fallback('Please choose an option from the buttons above.')
            ->addButtons([
                Button::create('Schedule Visit')->value('schedule visit'),
                Button::create('General Information')->value('general information'),
                Button::create('Submit Review/Rating')->value('review'),
                Button::create('Submit Complaint')->value('complaint'),
            ]);

        $this->ask($question, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, fn() => $this->backToMainMenu());
            }

            $this->say('Please choose one of the options above.');
            $this->backToMainMenu();
        });
    }
}