<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use Illuminate\Support\Facades\DB;
use App\Models\admin_models\Complaint;
use App\Conversations\Concerns\HandlesGlobalCommands;
use App\Conversations\Concerns\HandlesOffTopic;
use App\Conversations\Concerns\HandlesRateLimit;
use App\Services\SentimentAnalysisService;
use App\Services\NotificationService;
use App\Models\Notification;

class ComplaintConversation extends Conversation
{
    use HandlesGlobalCommands, HandlesOffTopic, HandlesRateLimit;

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
        if ($this->tooManySubmissions('complaint')) {
            $this->say($this->submissionCooldownMessage());
            $this->askAnythingElse();
            return;
        }

        $saved = false;

        try {
            $logId = DB::table('chatbot_logs')->insertGetId([
                'user_id'      => $this->patientId,
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

        try {
            NotificationService::newComplaint($this->patientName ?: 'A patient', $complaint->complaint_id);
        } catch (\Throwable $notifyError) {
            \Illuminate\Support\Facades\Log::error('Failed to create complaint notification: ' . $notifyError->getMessage());
        }

            $this->say("Complaint Recorded\n\nReference #: {$complaint->complaint_id}\nWe acknowledge receipt of your concern.");
            $saved = true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('submitComplaint failed: ' . $e->getMessage());
            $this->say("We couldn't record your complaint right now. Please try again, or contact us directly.");
        }

        if ($saved) {
            // Complaint recorded: end the conversation and bring the 4 main
            // options straight back (no "anything else?" question).
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

    // Ends the flow with the Yes/No "anything else?" prompt.
    protected function askAnythingElse()
    {
        $this->bot->startConversation(new AnythingElseConversation($this->patientId, $this->patientName));
    }
}