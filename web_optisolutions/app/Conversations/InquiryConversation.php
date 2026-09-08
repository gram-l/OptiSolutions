<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use App\Conversations\Concerns\HandlesGlobalCommands;
use App\Conversations\Concerns\HandlesRateLimit;
use App\Models\Staff\AppNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InquiryConversation extends Conversation
{
    use HandlesGlobalCommands, HandlesRateLimit;

    protected $inquiryType;
    protected $message;
    protected $patientId;
    protected $patientName;

    /**
     * When AppointmentConversation auto-detects an off-topic message
     * (e.g. "ano to?") mid-schedule-visit, it routes here and passes the
     * patient's original text so we don't ask them to retype it.
     * @var string|null
     */
    protected $prefilledMessage;

    /**
     * The AppointmentConversation step to resume once this inquiry is
     * submitted (e.g. 'dob', 'service'). Null for a normal, standalone
     * inquiry started from the main menu.
     * @var string|null
     */
    protected $resumeStepKey;

    /**
     * Saved AppointmentConversation state (fname, phone, dob, service,
     * doctor, transcript, etc.) to restore when resuming.
     * @var array|null
     */
    protected $resumeState;

    public function __construct($prefilledMessage = null, $resumeStepKey = null, $resumeState = null)
    {
        $this->prefilledMessage = $prefilledMessage;
        $this->resumeStepKey = $resumeStepKey;
        $this->resumeState = $resumeState;
    }

    public function run()
    {
        $this->patientId = $this->bot->userStorage()->find()['patient_id'] ?? null;
        $this->patientName = $this->bot->userStorage()->find()['patient_name'] ?? null;

        if ($this->resumeStepKey) {
            $this->say("No worries, let's sort this out first — we'll pick your schedule visit right back up after.");
        }

        // If we already have the patient's original message (they typed
        // it while browsing the site / mid-schedule-visit, and got
        // auto-routed here by off-topic detection), skip the "what is
        // your inquiry about" category step entirely and send it
        // straight to staff — don't make them answer extra questions
        // about something they already asked once.
        if ($this->prefilledMessage !== null) {
            $this->inquiryType = 'General';
            $this->message = trim($this->prefilledMessage);
            $this->submitInquiry();
            return;
        }

        // Only reached for a standalone inquiry someone starts on
        // purpose (not auto-routed), where we don't yet have any text
        // from them — so it's fine to ask a category first.
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
            // If the patient types "Submit Complaint" / "Menu" / etc.
            // instead of picking an inquiry type, honor it immediately
            // rather than treating it as an invalid answer.
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, fn() => $this->askInquiryType());
            }

            $this->inquiryType = $answer->getValue() ?: 'General';
            $this->askMessage();
        });
    }

    protected function askMessage($prompt = 'Please type your question or concern:')
    {
        $this->ask($prompt, function (Answer $answer) use ($prompt) {
            // Same escape hatch here: "Submit Complaint" mid-inquiry
            // switches conversations instead of becoming the inquiry text.
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, fn() => $this->askMessage($prompt));
            }

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
        // Higher threshold than Review/Complaint (6 vs 3 per 10 min):
        // this step can be auto-triggered multiple times in one
        // legitimate visit (each off-topic detour mid-schedule-visit
        // routes here), not just from a deliberate one-time submission.
        if ($this->tooManySubmissions('inquiry', 6, 10)) {
            $this->say($this->submissionCooldownMessage());
            $this->resumeAppointmentIfNeeded();
            return;
        }

        $this->say('Submitting your inquiry...');

        // Same conversation_id BotManController uses to log/attach
        // follow-up replies to the correct thread. Without this, a
        // patient's next message would create a duplicate inquiry
        // instead of continuing this one.
        $conversationId = $this->bot->getMessage()->getSender();

        // Best-effort guest name: not collected in the standalone
        // "what's your inquiry about" flow, but available if this
        // inquiry was routed here mid-schedule-visit (resumeState
        // carries whatever name the patient had already typed).
        $guestName = trim(($this->resumeState['fname'] ?? '') . ' ' . ($this->resumeState['lname'] ?? '')) ?: null;

        try {
            $logId = DB::table('chatbot_logs')->insertGetId([
                'user_id'         => $this->patientId,
                'conversation_id' => $conversationId !== '' ? $conversationId : null,
                'user_message'    => $this->message,
                'bot_message'     => null,
                'chat_time'       => now(),
            ]);

            DB::table('inquiries')->insert([
                'patient_id'      => $this->patientId,
                'guest_name'      => $guestName,
                'log_id'          => $logId,
                'conversation_id' => $conversationId !== '' ? $conversationId : null,
                'inquiry_type'    => $this->inquiryType,
                'resolved_status' => 'Pending',
                'created_at'      => now(),
            ]);

            AppNotification::create([
                'icon'    => 'question_answer',
                'title'   => 'New Inquiry',
                'message' => "A new {$this->inquiryType} inquiry has been submitted.",
                'is_read' => false,
                'color'   => '2196F3',
            ]);

            $this->say("Thank you! Your {$this->inquiryType} inquiry has been sent to our staff. We'll get back to you as soon as possible.");
        } catch (\Throwable $e) {
            // IMPORTANT: this used to fail silently (no logging), so the
            // ONLY symptom was the generic "couldn't submit" message to
            // the patient with no way to tell why. A common cause: this
            // inquiry got triggered mid-schedule-visit, before the
            // patient record exists yet (patient_id is only saved to
            // userStorage inside AppointmentConversation::submitAppointment()).
            // If your `inquiries`/`chatbot_logs` tables have a NOT NULL
            // or foreign-key constraint on patient_id, insert fails here.
            // Fix: make patient_id nullable on those tables (a walk-in
            // inquiry sent before registration legitimately has no
            // patient yet), or drop the FK constraint in favor of a
            // plain, nullable column.
            Log::error('Failed to submit inquiry: ' . $e->getMessage(), [
                'exception' => $e,
                'patient_id' => $this->patientId,
                'inquiry_type' => $this->inquiryType,
                'message' => $this->message,
            ]);
            $this->say("⚠️ We couldn't submit your inquiry right now. Please try again, or contact us directly at 0985 475 5511.");
        }

        $this->resumeAppointmentIfNeeded();
    }

    /**
     * If this inquiry was triggered as a detour out of a schedule visit
     * in progress, hand control back to a fresh AppointmentConversation
     * pre-loaded with the saved state and step, so the patient resumes
     * exactly where they left off instead of starting over.
     */
    protected function resumeAppointmentIfNeeded()
    {
        if ($this->resumeStepKey && $this->resumeState) {
            $this->bot->startConversation(
                new AppointmentConversation($this->resumeStepKey, $this->resumeState)
            );
        }
    }
}