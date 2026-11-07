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
use App\Services\NotificationService;
use App\Services\ScheduleVisitHold;
use App\Models\Staff\Inquiry;
use App\Models\Staff\InquiryReply;
use App\Models\Notification;

class InquiryConversation extends Conversation
{
    use HandlesGlobalCommands, HandlesRateLimit;

    // Tells the patient how to get the 4 main buttons back. Must match
    // MENU_HINT in chatbot-widget.blade.php.
    public const MENU_HINT = 'You can type "Menu" anytime to see the main options.';

    // Must match the text in FALLBACK_TEXTS in chatbot-widget.blade.php --
    // that is how the widget knows to remove this bubble as soon as
    // Admin/Staff sends their first reply.
    public const FORWARDED_TEXT = "Thanks for your message! I've forwarded it to our Admin/Staff team — they'll reply to you here shortly. " . self::MENU_HINT;

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

    /**
     * What the patient had already typed into the schedule-visit form
     * (not yet submitted), so it can be put back when they continue.
     * @var array|null
     */
    protected $resumeDraft;

    public function __construct($prefilledMessage = null, $resumeStepKey = null, $resumeState = null, $resumeDraft = null)
    {
        $this->prefilledMessage = $prefilledMessage;
        $this->resumeStepKey = $resumeStepKey;
        $this->resumeState = $resumeState;
        $this->resumeDraft = $resumeDraft;
    }

    public function run()
    {
        $this->patientId = $this->bot->userStorage()->find()['patient_id'] ?? null;
        $this->patientName = $this->bot->userStorage()->find()['patient_name'] ?? null;

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

        // Same conversation_id BotManController uses to log/attach
        // follow-up replies to the correct thread. Without this, a
        // patient's next message would create a duplicate inquiry
        // instead of continuing this one.
        $conversationId = (string) $this->bot->getMessage()->getSender();

        // The patient's schedule visit is paused (not abandoned) while this
        // inquiry is open; see ScheduleVisitHold.
        $holdVisit = (bool) ($this->resumeStepKey && $this->resumeState);
        $held = false;

        // Privacy: while the schedule visit is not finished (not yet confirmed),
        // Admin/Staff must not see who the patient is. So nothing that identifies
        // them is saved with this inquiry: no name typed into the form, and no
        // patient id left over from an earlier visit in the same chat. The inquiry
        // is linked to the patient only after the visit is confirmed
        // (see AppointmentConversation::submitAppointment).
        $recordPatientId = $holdVisit ? null : $this->patientId;
        $guestName = null;

        try {
            $logId = DB::table('chatbot_logs')->insertGetId([
                'user_id'         => $recordPatientId,
                'conversation_id' => $conversationId !== '' ? $conversationId : null,
                'user_message'    => $this->message,
                'bot_message'     => '(no reply)', // column is NOT NULL
                'chat_time'       => now(),
            ]);

            // A chat only ever has one open thread: a follow-up message goes
            // into the inquiry that is still open instead of starting a second
            // one (same rule BotManController uses for free-typed follow-ups).
            // Kahit Resolved na ang inquiry ng chat na ito, doon pa rin napupunta
            // ang follow-up (ire-reopen lang), hindi gagawa ng bagong inquiry.
            $existingInquiry = Inquiry::latestForConversation($conversationId);
            $existingInquiry?->reopenIfResolved();
            $inquiryId = $existingInquiry?->inquiry_id;

            if ($inquiryId !== null) {
                InquiryReply::create([
                    'inquiry_id' => $inquiryId,
                    'sender'     => 'Patient',
                    'message'    => $this->message,
                ]);
            } else {
                $inquiryId = DB::table('inquiries')->insertGetId([
                    'patient_id'      => $recordPatientId,
                    'guest_name'      => $guestName,
                    'log_id'          => $logId,
                    'conversation_id' => $conversationId !== '' ? $conversationId : null,
                    'inquiry_type'    => $this->inquiryType,
                    'resolved_status' => 'Pending',
                    'created_at'      => now(),
                ]);

                try {
                    $name = $holdVisit
                        ? 'A guest'
                        : ($this->patientName ?: ($this->patientId ? "Patient #{$this->patientId}" : 'A guest'));
                    NotificationService::newInquiry($name, $inquiryId);
                } catch (\Throwable $notifyError) {
                    Log::error('Failed to create inquiry notification: ' . $notifyError->getMessage());
                }
            }

            // Put the schedule visit form on hold until staff resolves this.
            if ($holdVisit) {
                $hold = ScheduleVisitHold::begin(
                    $conversationId,
                    $this->resumeStepKey,
                    $this->resumeState,
                    $this->resumeDraft,
                    ScheduleVisitHold::MODE_STAFF,
                    (int) $inquiryId
                );
                $this->say(ScheduleVisitHold::marker($hold));
                $held = true;
            }

            $this->say(self::FORWARDED_TEXT);
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

        // If the inquiry never reached staff there is nothing to wait for, so the
        // form comes straight back (with what the patient had typed).
        if (!$held) {
            $this->resumeAppointmentIfNeeded();
        }
    }

    /**
     * Only used when the inquiry could NOT be sent to staff (rate limit or a
     * save error): hand control straight back to a fresh AppointmentConversation
     * with the saved state, step and typed values, so the patient isn't left
     * waiting on a reply that will never come. A successfully sent inquiry puts
     * the visit on hold instead (see submitInquiry / ScheduleVisitHold).
     */
    protected function resumeAppointmentIfNeeded()
    {
        if ($this->resumeStepKey && $this->resumeState) {
            $this->bot->startConversation(
                new AppointmentConversation($this->resumeStepKey, $this->resumeState, $this->resumeDraft)
            );
        }
    }
}