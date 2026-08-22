<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use App\Services\ClinicInfoService;
use App\Mail\ConversationTranscriptMail;
use App\Models\Staff\AppNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Closure;

class AppointmentConversation extends Conversation
{
    protected $fname, $lname, $phone, $dob, $email;
    protected $service, $serviceKey, $serviceId;
    protected $doctor, $doctorId, $scheduleDay, $scheduleSlotIndex = 0;
    protected $scheduleSuggestion;

    /**
     * Full message-by-message log of this conversation, built up as we go
     * via sayLogged()/askLogged(). Used to email the whole transcript at
     * the end if the patient wants a copy.
     * @var array<int, array{role: string, text: string, time: string}>
     */
    protected $transcript = [];

    /**
     * @var string|null
     */
    protected $pendingAnswerCallback;

    /**
     * When this conversation is (re)constructed after being detoured
     * into InquiryConversation (see routeToInquiry()/resumeStep()), these
     * hold the step to jump back into and the state to restore, so run()
     * can resume the schedule visit instead of starting over at askName().
     */
    protected $pendingResumeStepKey;
    protected $pendingResumeState;

    public function __construct($resumeStepKey = null, $resumeState = null)
    {
        $this->pendingResumeStepKey = $resumeStepKey;
        $this->pendingResumeState = $resumeState;
    }

    // ---------------------------------------------------------------
    // TRANSCRIPT LOGGING
    //
    // sayLogged()/askLogged() are drop-in replacements for BotMan's
    // say()/ask() that also record what was sent/received into
    // $transcript, so we can email the full conversation at the end.
    //
    // IMPORTANT — why this looks the way it does:
    // The previous version of askLogged() passed BotMan a closure that
    // captured the step's own callback via `use ($callback)` — a
    // closure wrapping another closure. BotMan/laravel-serializable-
    // closure has to serialize whatever closure is passed to ask() so
    // it can be cached and resumed on the next HTTP request, and a
    // closure-capturing-a-closure via use() does not survive that
    // serialize/unserialize round-trip reliably. The result: on the
    // next request the resumed closure came back subtly broken and
    // never got properly re-bound to the live BotMan instance, so
    // $this->bot was null and reply() failed with
    // "Call to a member function reply() on null".
    //
    // The fix: askLogged() now passes BotMan a single, non-nested
    // closure — Closure::fromCallable([$this, 'handleLoggedAnswer']) —
    // which only references a real method by name, nothing captured
    // via use(). The actual per-step callback is serialized separately
    // with BotMan's own closure serializer and stored on a plain
    // property ($pendingAnswerCallback), which rides along safely as
    // normal object state whenever the Conversation itself is cached.
    // ---------------------------------------------------------------

    protected function sayLogged($message)
    {
        $this->logMessage('Bot', $message);
        $this->say($message);
    }

    protected function askLogged($question, callable $callback)
    {
        $this->logMessage('Bot', $question);

        // Serialize the step's callback now (we have a live $this->bot
        // here, since askLogged() is always called during an actual
        // request) and stash it as a string on a plain property.
        $this->pendingAnswerCallback = $this->bot->serializeClosure(
            Closure::fromCallable($callback)
        );

        // Pass BotMan a simple, single-level closure — no nested use().
        $this->ask($question, Closure::fromCallable([$this, 'handleLoggedAnswer']));
    }

    /**
     * The single, stable "next" callback BotMan actually stores/resumes
     * for every askLogged() call. It logs the answer, then unpacks and
     * invokes whatever step-specific callback was stashed by askLogged().
     *
     * @param Answer $answer
     * @return mixed
     */
    public function handleLoggedAnswer(Answer $answer)
    {
        $this->logMessage('You', $answer->getText());

        $callback = unserialize($this->pendingAnswerCallback)->getClosure();

        // IMPORTANT: unserializing a closure that was bound to $this
        // (the conversation) reconstructs a SEPARATE, stale copy of
        // that object — not the live $this we are currently executing
        // as, which BotMan has already re-attached a working bot to via
        // setBot() just before this method runs. If we invoked the
        // unserialized closure as-is, any $this->bot access inside it
        // would hit that stale copy's bot property, which is null
        // (Conversation::__sleep() strips 'bot' before caching).
        //
        // Rebinding the closure to the current, live $this fixes this:
        // the callback now runs against the object that actually has
        // a working bot attached.
        $callback = Closure::bind($callback, $this, static::class);

        return $callback($answer);
    }

    protected function logMessage(string $role, $content)
    {
        $text = $this->extractText($content);
        if ($text === '') {
            return;
        }
        $this->transcript[] = [
            'role' => $role,
            'text' => $text,
            // FIX: now() was returning server/UTC time (e.g. 01:42 PM)
            // instead of local Philippine time (e.g. 09:42 PM) — an
            // 8-hour offset. Converting to Asia/Manila here guarantees
            // correct local time regardless of the app's default
            // timezone config.
            'time' => now()->timezone('Asia/Manila')->format('h:i A'),
        ];
    }

    protected function extractText($content): string
    {
        if ($content instanceof Question) {
            $text = method_exists($content, 'getText') ? $content->getText() : '';
        } elseif (is_string($content)) {
            $text = $content;
        } else {
            $text = (string) $content;
        }

        // Don't dump raw card JSON payloads into the emailed transcript -
        // summarize them instead so the email stays readable.
        if (str_starts_with($text, 'INFO_CARD::')) {
            return '[Sent: Clinic Information Card]';
        }
        if (str_starts_with($text, 'APPT_CARD::')) {
            return '[Sent: Schedule Visit Confirmation Card]';
        }

        return trim($text);
    }

   
    protected function normalizeCommand(Answer $answer): string
    {
        return strtolower(trim($answer->getValue() ?: $answer->getText()));
    }

    protected function isGlobalCommand(Answer $answer): bool
    {
        return in_array($this->normalizeCommand($answer), [
            'general information',
            'menu',
            'schedule visit',
            'cancel',
        ]);
    }

    /**
     * @param Answer $answer
     * @param string $stepKey 
     */
    protected function handleGlobalCommand(Answer $answer, string $stepKey)
    {
        $cmd = $this->normalizeCommand($answer);

        if ($cmd === 'general information') {
            $this->sayLogged(ClinicInfoService::infoCardMessage());
            $this->askContinueOrRestart($stepKey);
            return;
        }

        if ($cmd === 'cancel') {
            $this->sayLogged('Okay, your schedule visit has been cancelled. Type "Menu" anytime to start again.');
            return;
        }

        if ($cmd === 'menu') {
            $this->sayLogged('Okay, cancelling this schedule visit.');
            $this->sendMainMenu();
            return;
        }

        if ($cmd === 'schedule visit') {
            $this->sayLogged('Restarting your schedule visit from the beginning.');
            $this->resetState();
            $this->run();
            return;
        }
    }

    // ---------------------------------------------------------------
    // OFF-TOPIC / INQUIRY DETECTION
    //
    // If a patient types something instead of actually answering the
    // current step, we handle it in one of four ways, checked in this
    // order:
    //
    //   0. It's a simple acknowledgment/thank-you ("ok", "thanks",
    //      "salamat") — reply warmly and just re-ask the SAME question,
    //      since nothing actually needs derailing.
    //   1. It has an attachment (image/file) — a human has to look at
    //      that, so it ALWAYS goes to InquiryConversation → staff/admin,
    //      no matter what the accompanying text says.
    //   2. It's a text-only question we already have a canned answer
    //      for (clinic location, hours, contact, services, doctors,
    //      how-to-schedule) — the bot answers it directly using
    //      ClinicInfoService, no need to bother staff.
    //   3. Anything else that looks like a genuine question/off-topic
    //      remark — routed to InquiryConversation → staff/admin.
    //
    // Once handled: #0 and #2 just resume the same step immediately;
    // #1/#3 resume after the routed inquiry is submitted (see
    // routeToInquiry()/InquiryConversation).
    // ---------------------------------------------------------------

    /**
     * Best-effort attachment check across BotMan drivers. Different
     * drivers/webhook payloads expose attachments differently, so we
     * check the common possibilities. If your driver stores attachments
     * some other way (e.g. a custom "extras" key from your web widget),
     * add that check here too.
     */
    protected function hasAttachment(Answer $answer): bool
    {
        $message = $answer->getMessage();
        if (!$message) {
            return false;
        }

        foreach (['getImages', 'getFiles', 'getVideos', 'getAudio'] as $method) {
            if (method_exists($message, $method) && !empty($message->$method())) {
                return true;
            }
        }

        if (method_exists($message, 'getExtras')) {
            $extras = $message->getExtras() ?? [];
            foreach (['attachment', 'attachments', 'image', 'images', 'file', 'files'] as $key) {
                if (!empty($extras[$key])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Heuristic check: does this free-typed answer look like a question
     * or off-topic remark rather than an attempt to answer the current
     * step?
     */
    protected function looksLikeInquiry(Answer $answer): bool
    {
        // Button clicks always carry a matching value — never treat an
        // interactive reply as an inquiry, only free-typed text.
        if (method_exists($answer, 'isInteractiveMessageReply') && $answer->isInteractiveMessageReply()) {
            return false;
        }

        $text = trim($answer->getText());
        if ($text === '') {
            return false;
        }

        if (str_ends_with($text, '?')) {
            return true;
        }

        $questionWords = [
            'ano', 'anong', 'bakit', 'paano', 'paanong', 'saan', 'saang', 'nasaan',
            'kailan', 'sino', 'sinong', 'magkano', 'ilan', 'ilang', 'alin', 'alinng',
            'pwede', 'puwede', 'meron', 'mayroon',
            'what', 'why', 'how', 'when', 'where', 'who', 'which', 'can', 'could', 'is', 'does',
        ];
        $words = preg_split('/\s+/', $text);
        $firstWord = strtolower(rtrim($words[0] ?? '', '?.,!'));

        return in_array($firstWord, $questionWords, true);
    }

    /**
     * Does this question match something we already have a canned
     * answer for — clinic location/directions, hours, contact number,
     * services, doctors, or how to schedule a visit? If so we can
     * answer it ourselves instead of bothering staff with it.
     *
     * Delegates to ClinicInfoService so this stays in sync with what
     * BotManController::handleFallback() can also answer directly —
     * previously this had its own separate keyword list that could
     * drift out of sync.
     */
    protected function looksLikeInfoRequest(string $text): bool
    {
        return ClinicInfoService::looksLikeInfoRequest($text);
    }

    /**
     * Is this free-typed text just a plain acknowledgment/thank-you
     * ("ok", "thanks", "salamat") rather than an attempt to answer the
     * current step? Button clicks are never treated as acknowledgments,
     * same rule as looksLikeInquiry().
     */
    protected function looksLikeAcknowledgment(Answer $answer): bool
    {
        if (method_exists($answer, 'isInteractiveMessageReply') && $answer->isInteractiveMessageReply()) {
            return false;
        }

        $text = trim($answer->getText());
        if ($text === '') {
            return false;
        }

        return ClinicInfoService::looksLikeAcknowledgment($text);
    }

    /**
     * The single entry point every step calls before running its own
     * validation. Returns true if it fully handled the answer (either
     * acknowledged it, answered it directly, or routed it to staff) —
     * in that case the calling step should just `return` without doing
     * anything else. Returns false if the answer should go through
     * normal validation.
     */
    protected function handleOffTopicIfAny(Answer $answer, string $stepKey): bool
    {
        // #0 — plain "ok" / "thanks" / "salamat" typed instead of
        // actually answering the current question. Reply
        // conversationally and simply re-ask the SAME step — letting
        // this fall through to validation would otherwise reject it
        // with a confusing error (e.g. "thanks" as an invalid phone
        // number).
        if ($this->looksLikeAcknowledgment($answer)) {
            $this->logMessage('You', $answer->getText());
            $this->sayLogged(ClinicInfoService::acknowledgmentReply());
            $this->resumeStep($stepKey);
            return true;
        }

        // #1 — attachments always go to staff, regardless of any text.
        if ($this->hasAttachment($answer)) {
            $this->routeToInquiry($answer, $stepKey);
            return true;
        }

        if (!$this->looksLikeInquiry($answer)) {
            return false;
        }

        $text = trim($answer->getText());

        // #2 — text-only question we can answer ourselves. Use
        // answerForText() so a doctor/service/how-to question gets the
        // specific card instead of always the generic clinic-info card.
        if ($this->looksLikeInfoRequest($text)) {
            $this->logMessage('You', $text);
            $this->sayLogged(ClinicInfoService::answerForText($text) ?? ClinicInfoService::infoCardMessage());
            $this->askContinueOrRestart($stepKey);
            return true;
        }

        // #3 — anything else that looks like a genuine question.
        $this->routeToInquiry($answer, $stepKey);
        return true;
    }

    /**
     * Sends the patient's off-topic message to InquiryConversation (so
     * it reaches staff/admin the same way a normal inquiry would), and
     * stashes enough of the current progress so the schedule visit can
     * pick back up at the same step afterward.
     */
    protected function routeToInquiry(Answer $answer, string $stepKey)
    {
        $this->logMessage('You', $answer->getText() ?: '[Attachment]');
        $this->sayLogged("Got it — let me send that to our staff so a real person can take a look. We'll pick your schedule visit right back up after.");

        $state = [
            'fname' => $this->fname, 'lname' => $this->lname, 'phone' => $this->phone,
            'dob' => $this->dob, 'email' => $this->email,
            'service' => $this->service, 'serviceKey' => $this->serviceKey, 'serviceId' => $this->serviceId,
            'doctor' => $this->doctor, 'doctorId' => $this->doctorId,
            'scheduleDay' => $this->scheduleDay, 'scheduleSlotIndex' => $this->scheduleSlotIndex,
            'scheduleSuggestion' => $this->scheduleSuggestion,
            'transcript' => $this->transcript,
        ];

        $this->bot->startConversation(new InquiryConversation($answer->getText(), $stepKey, $state));
    }

    protected function resetState()
    {
        $this->fname = $this->lname = $this->phone = $this->dob = $this->email = null;
        $this->service = $this->serviceKey = $this->serviceId = null;
        $this->doctor = $this->doctorId = $this->scheduleDay = null;
        $this->scheduleSlotIndex = 0;
        $this->scheduleSuggestion = null;
        $this->transcript = [];
        $this->pendingAnswerCallback = null;
    }

    /**
     * Restores previously-saved state (used when resuming a schedule
     * visit that got detoured into InquiryConversation).
     */
    protected function restoreState(array $state)
    {
        foreach ($state as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    // Dispatches to the correct step for a given step key. Plain switch on a string — no closures involved.

    protected function resumeStep(string $stepKey)
    {
        switch ($stepKey) {
            case 'name':             $this->askName(); break;
            case 'phone':            $this->askPhone(); break;
            case 'dob':              $this->askDob(); break;
            case 'email':            $this->askEmail(); break;
            case 'service':          $this->askService(); break;
            case 'doctor':           $this->askDoctor(); break;
            case 'schedule':         $this->suggestSchedule(); break;
            case 'transcript_email': $this->askSendTranscriptEmail(); break;
            case 'post':             $this->askPostAppointment(); break;
            default:                 $this->askName(); break;
        }
    }

    // Shown right after the general-information card is displayed mid-schedule-visit. Lets the user decide what to do next instead of the bot silently deciding for them
    protected function askContinueOrRestart(string $stepKey)
    {
        $question = Question::create('Would you like to continue your schedule visit, or start over?')
            ->fallback('Please choose an option from the buttons above.')
            ->addButtons([
                Button::create('Continue Schedule Visit')->value('continue_schedule'),
                Button::create('Start Over')->value('restart_schedule'),
                Button::create('Cancel')->value('cancel_schedule'),
            ]);

        $this->askLogged($question, function (Answer $followUp) use ($stepKey) {
            $choice = $this->normalizeCommand($followUp);

            if ($choice === 'continue_schedule') {
                $this->resumeStep($stepKey);
                return;
            }

            if ($choice === 'restart_schedule') {
                $this->sayLogged('Okay! Restarting your schedule visit from the beginning.');
                $this->resetState();
                $this->run();
                return;
            }

            if ($choice === 'cancel_schedule') {
                $this->sayLogged('Okay, your schedule visit has been cancelled. Type "Menu" anytime to start again.');
                return;
            }

            $this->sayLogged('Please choose one of the options above.');
            $this->askContinueOrRestart($stepKey);
        });
    }

    protected function sendMainMenu()
    {
        $question = Question::create('What would you like to do?')
            ->fallback('Please choose an option from the buttons above.')
            ->addButtons([
                Button::create('Schedule Visit')->value('schedule visit'),
                Button::create('General Information')->value('general information'),
            ]);
        $this->sayLogged($question);
    }

    
    // Validation helpers
    

    protected function validateName($name)
    {
        $trimmed = trim($name);
        if (!$trimmed) return "Name cannot be empty.";
        if (!preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ'\-. ]+$/u", $trimmed))
            return "Name can only contain letters, spaces, hyphens, or apostrophes.";
        $words = array_filter(preg_split('/\s+/', $trimmed));
        if (count($words) < 2) return "Please enter your full name";
        foreach ($words as $w) {
            if (strlen(preg_replace("/['.]/", '', $w)) < 2)
                return "Each part of your name must be at least 2 characters.";
        }
        return null;
    }

    protected function validatePhone($phone)
    {
        $trimmed = trim($phone);
        if (!$trimmed) return "Contact number cannot be empty.";
        $digits = preg_replace('/[\s\-().+]/', '', $trimmed);
        if (!preg_match('/^\d+$/', $digits))
            return "Phone number can only contain digits, spaces, dashes, or parentheses.";
        $mobileLocal = preg_match('/^09\d{9}$/', $digits);
        $mobileIntl  = preg_match('/^639\d{9}$/', $digits);
        $landline    = preg_match('/^(0\d{9,10}|\d{7,8})$/', $digits);
        if (!$mobileLocal && !$mobileIntl && !$landline)
            return "Please enter a valid PH phone number";

        // Right shape, but obviously fake (all-same-digit, sequential
        // run, or a tiny block repeated to fill the number) — reject.
        if ($this->isFakeLookingNumber($digits))
            return "This doesn't look like a real phone number. Please double-check and enter it again.";

        return null;
    }

    /**
     * Flags numbers that pass the shape/prefix check above but are
     * clearly not real: all the same digit, a straight ascending or
     * descending run, or a short block (1-3 digits) repeated to fill
     * out the whole number. Also flags 6+ identical digits in a row
     * anywhere inside the number.
     */
    protected function isFakeLookingNumber($digits)
    {
        // Strip the leading "0" or "63" trunk/country code so we only
        // pattern-check the actual subscriber number — otherwise the
        // prefix itself could cause false positives/negatives.
        $core = $digits;
        if (str_starts_with($core, '63')) {
            $core = substr($core, 2);
        } elseif (str_starts_with($core, '0')) {
            $core = substr($core, 1);
        }

        if ($core === '') return false;

        // e.g. 9999999999
        if (preg_match('/^(\d)\1+$/', $core)) {
            return true;
        }

        // e.g. 123456789 or 987654321
        $isAscending = true;
        $isDescending = true;
        for ($i = 1; $i < strlen($core); $i++) {
            if ((int) $core[$i] !== (int) $core[$i - 1] + 1) $isAscending = false;
            if ((int) $core[$i] !== (int) $core[$i - 1] - 1) $isDescending = false;
        }
        if ($isAscending || $isDescending) {
            return true;
        }

        // e.g. 121212121 or 123123123 — a 1-3 digit block repeated to
        // fill the entire number exactly.
        for ($blockLen = 1; $blockLen <= 3; $blockLen++) {
            if (strlen($core) % $blockLen !== 0) continue;
            $block = substr($core, 0, $blockLen);
            if (str_repeat($block, (int) (strlen($core) / $blockLen)) === $core) {
                return true;
            }
        }

        // 6+ of the same digit in a row anywhere, e.g. 9111111123
        if (preg_match('/(\d)\1{5,}/', $core)) {
            return true;
        }

        return false;
    }

    protected function validateDOB($dob)
    {
        $trimmed = trim($dob);
        if (!$trimmed) return "Date of birth cannot be empty.";

        $parsed = $this->parseDOB($trimmed);
        if (!$parsed) return "Please enter a valid date";

        $today = new \DateTime('today');
        if ($parsed > $today) return "Date of birth cannot be in the future.";

        $age = $today->diff($parsed)->y;
        if ($age > 120) return "Please enter a valid date of birth (age must be 120 or below).";

        return null;
    }

    protected function parseDOB($dob)
    {
        $trimmed = trim($dob);
        $normalized = preg_replace('/[.\-\/]/', '-', $trimmed);

        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $normalized)) {
            [$y, $m, $d] = explode('-', $normalized);
            $parsed = \DateTime::createFromFormat('Y-n-j', "$y-$m-$d");
            if ($parsed) return $parsed;
        }

        if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $normalized)) {
            [$m, $d, $y] = explode('-', $normalized);
            $parsed = \DateTime::createFromFormat('Y-n-j', "$y-$m-$d");
            if ($parsed) return $parsed;
        }

        if (preg_match('/^[A-Za-z]+\s+\d{1,2},?\s+\d{4}$/', $trimmed) ||
            preg_match('/^\d{1,2}\s+[A-Za-z]+\s+\d{4}$/', $trimmed)) {
            $timestamp = strtotime($trimmed);
            if ($timestamp !== false) {
                $parsed = new \DateTime();
                $parsed->setTimestamp($timestamp);
                $parsed->setTime(0, 0, 0);
                return $parsed;
            }
        }

        return null;
    }

    protected function validateEmail($email)
    {
        $trimmed = trim($email);
        if (!$trimmed) return "Email address cannot be empty.";
        if (!filter_var($trimmed, FILTER_VALIDATE_EMAIL))
            return "Please enter a valid email address";
        if (str_contains($trimmed, '..'))
            return "Email address cannot contain consecutive dots.";
        return null;
    }

    public function run()
    {
        // If we were (re)constructed after a detour into
        // InquiryConversation, pick the schedule visit back up right
        // where the patient left off instead of starting over.
        if ($this->pendingResumeStepKey) {
            $this->restoreState($this->pendingResumeState ?? []);
            $stepKey = $this->pendingResumeStepKey;
            $this->pendingResumeStepKey = null;
            $this->pendingResumeState = null;
            $this->resumeStep($stepKey);
            return;
        }

        $this->askName();
    }

    // STEP 1: PATIENT INFO (name → phone → dob → email)

    public function askName($prompt = 'Please provide your full name (first and last name):')
    {
        $this->askLogged($prompt, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'name');
            }
            if ($this->handleOffTopicIfAny($answer, 'name')) {
                return;
            }

            $error = $this->validateName($answer->getText());
            if ($error) {
                return $this->askName($error . ' (e.g. Juan dela Cruz):');
            }
            $parts = preg_split('/\s+/', trim($answer->getText()));
            $this->fname = array_shift($parts);
            $this->lname = implode(' ', $parts) ?: $this->fname;
            $this->sayLogged("Thanks, {$this->fname} {$this->lname}!");
            $this->askPhone();
        });
    }

    public function askPhone($prompt = 'Please provide your contact number (e.g. 09XX XXX XXXX):')
    {
        $this->askLogged($prompt, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'phone');
            }
            if ($this->handleOffTopicIfAny($answer, 'phone')) {
                return;
            }

            $error = $this->validatePhone($answer->getText());
            if ($error) {
                return $this->askPhone($error . ' (e.g. 09XX XXX XXXX or (043) 123-4567):');
            }
            $this->phone = trim($answer->getText());
            $this->askDob();
        });
    }

    public function askDob($prompt = 'Please provide your date of birth (e.g. 05/15/1990 or May 15, 1990):')
    {
        $this->askLogged($prompt, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'dob');
            }
            if ($this->handleOffTopicIfAny($answer, 'dob')) {
                return;
            }

            $error = $this->validateDOB($answer->getText());
            if ($error) {
                return $this->askDob($error . ' (e.g. 05/15/1990 or May 15, 1990):');
            }
            $this->dob = trim($answer->getText());
            $this->askEmail();
        });
    }

    public function askEmail($prompt = 'Please provide your email address:')
    {
        $this->askLogged($prompt, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'email');
            }
            if ($this->handleOffTopicIfAny($answer, 'email')) {
                return;
            }

            $error = $this->validateEmail($answer->getText());
            if ($error) {
                return $this->askEmail($error . ' (e.g. juan@example.com):');
            }
            $this->email = trim($answer->getText());
            $this->askService(); // → move to Step 2
        });
    }


    // STEP 2: SERVICE SELECTION


    public function askService()
    {
        $services = DB::table('services')->where('available', 1)->get();

        if ($services->isEmpty()) {
            $this->sayLogged("We couldn't load our services right now. Please try again in a moment.");
            return;
        }

        $buttons = $services->map(fn($s) => Button::create($s->title)->value($s->service_key))->toArray();

        $question = Question::create('Please select the service you need:')
            ->fallback('Please select a service from the buttons above.')
            ->addButtons($buttons);

        $this->askLogged($question, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'service');
            }
            if ($this->handleOffTopicIfAny($answer, 'service')) {
                return;
            }

            $key = $answer->getValue();
            $svc = DB::table('services')->where('service_key', $key)->first();
            if (!$svc) {
                $this->sayLogged("Sorry, I didn't recognize that service. Please try again.");
                return $this->askService();
            }
            $this->service = $svc->title;
            $this->serviceKey = $svc->service_key;
            $this->serviceId = $svc->service_id;
            $this->askDoctor(); // → move to Step 3
        });
    }


    // STEP 3: DOCTOR SELECTION


    public function askDoctor()
    {
        $doctors = DB::table('doctors')
            ->where('available', 1)
            ->where('specialty', $this->service)
            ->get();

        if ($doctors->isEmpty()) {
            $this->sayLogged("We currently don't have a specialist for {$this->service}. Returning to main menu.");
            return;
        }

        $buttons = $doctors->map(
            fn($d) => Button::create("{$d->doctor_name} — {$d->specialty}")->value((string) $d->doctor_id)
        )->toArray();

        $question = Question::create("Great! Here are our specialists for {$this->service}:")
            ->fallback('Please select a doctor from the buttons above.')
            ->addButtons($buttons);

        $this->askLogged($question, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'doctor');
            }
            if ($this->handleOffTopicIfAny($answer, 'doctor')) {
                return;
            }

            $doctorId = (int) $answer->getValue();
            $doc = DB::table('doctors')->where('doctor_id', $doctorId)->first();
            if (!$doc) {
                $this->sayLogged("Sorry, I couldn't find this doctor. Please try again.");
                return $this->askDoctor();
            }
            $this->doctor = $doc->doctor_name;
            $this->doctorId = $doc->doctor_id;
            $this->suggestSchedule(); // → move to Step 4
        });
    }

    // STEP 4: SCHEDULE SUGGESTION + CONFIRMATION


    protected function suggestSchedule()
    {
        $schedules = DB::table('doctor_schedules')->where('doctor_id', $this->doctorId)->get();

        if ($schedules->isEmpty()) {
            $this->sayLogged("Sorry, I couldn't find availability for this doctor. Please try another.");
            return $this->askDoctor();
        }

        $slot = $schedules[$this->scheduleSlotIndex] ?? $schedules[0];
        $this->scheduleDay = $slot->day;
        $suggestedDate = $this->getNextAvailableDate($slot->day);
        $this->scheduleSuggestion = "{$suggestedDate} at {$this->formatTime($slot->start_time)} - {$this->formatTime($slot->end_time)}";

        $question = Question::create("Suggested schedule: {$this->scheduleSuggestion}
        \n(Based on {$this->doctor}'s availability — {$slot->day})")
            ->fallback('Please use the confirmation buttons above.')
            ->addButtons([
                Button::create('Yes, confirm')->value('confirm_yes'),
                Button::create('Suggest alternative')->value('confirm_no'),
            ]);

        $this->askLogged($question, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'schedule');
            }
            if ($this->handleOffTopicIfAny($answer, 'schedule')) {
                return;
            }

            if ($answer->getValue() === 'confirm_yes') {
                $this->submitAppointment(); // → move to Step 5
            } else {
                $this->scheduleSlotIndex++;
                $this->suggestSchedule();
            }
        });
    }

    //  Date/time helpers

    protected function getNextAvailableDate($dayStr)
    {
        $dayMap = ['Sunday'=>0,'Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6];
        $target = trim(explode('-', explode(',', $dayStr)[0])[0]);
        $targetIndex = $dayMap[$target] ?? null;
        $now = new \DateTime();
        if ($targetIndex === null) {
            $now->modify('+1 day');
        } else {
            $daysUntil = ($targetIndex - (int)$now->format('w') + 7) % 7;
            if ($daysUntil === 0) $daysUntil = 7;
            $now->modify("+{$daysUntil} days");
        }
        return $now->format('l, F j, Y');
    }

    protected function getNextAvailableISODate($dayStr)
    {
        $dayMap = ['Sunday'=>0,'Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6];
        $target = trim(explode('-', explode(',', $dayStr)[0])[0]);
        $targetIndex = $dayMap[$target] ?? null;
        $now = new \DateTime();
        if ($targetIndex === null) {
            $now->modify('+1 day');
        } else {
            $daysUntil = ($targetIndex - (int)$now->format('w') + 7) % 7;
            if ($daysUntil === 0) $daysUntil = 7;
            $now->modify("+{$daysUntil} days");
        }
        return $now->format('Y-m-d');
    }

    protected function formatTime($t)
    {
        return date('gA', strtotime($t));
    }

    protected function normalizeDOB($dob)
    {
        $parsed = $this->parseDOB(trim($dob));
        if ($parsed) return $parsed->format('Y-m-d');
        return trim($dob);
    }


    // STEP 5: SUBMIT & SAVE TO DATABASE


    protected function submitAppointment()
    {
        $this->sayLogged('Saving your schedule visit...');

        try {
            $patientId = DB::table('patients')->insertGetId([
                'patient_fname'     => $this->fname,
                'patient_lname'     => $this->lname,
                'patient_birthdate' => $this->normalizeDOB($this->dob),
                'patient_email'     => $this->email,
                'patient_contact'   => $this->phone,
            ]);

            $visitDate = $this->getNextAvailableISODate($this->scheduleDay);

            DB::table('schedule_visit')->insert([
                'doctor_id'    => $this->doctorId,
                'patient_id'   => $patientId,
                'service_type' => $this->service,
                'visit_date'   => $visitDate,
                'notes'        => $this->scheduleSuggestion,
                'scheduled_at' => now(),
            ]);

            // Notification creation is intentionally isolated in its own
            // try/catch. It's a "nice to have" side effect for the staff
            // dashboard — if it fails (e.g. mass assignment guard, missing
            // column), it should NOT make the patient think their
            // appointment wasn't saved when it actually was.
            try {
                AppNotification::create([
                    'icon'    => 'calendar_today',
                    'title'   => 'New Schedule Visit',
                    'message' => "{$this->fname} {$this->lname} scheduled a visit with {$this->doctor} on "
                        . \Carbon\Carbon::parse($visitDate)->format('M d, Y') . '.',
                    'is_read' => false,
                    'color'   => '4CAF50',
                ]);
            } catch (\Throwable $notifyError) {
                Log::error('Failed to create appointment notification: ' . $notifyError->getMessage(), [
                    'exception' => $notifyError,
                    'patient_id' => $patientId,
                ]);
            }

            $this->bot->userStorage()->save([
                'patient_id' => $patientId,
                'patient_name' => "{$this->fname} {$this->lname}",
            ]);

            $card = [
                'title' => "Schedule Visit Confirmed, {$this->fname} {$this->lname}!\nKindly screenshot this confirmation for your reference.",
                'sections' => [
                    [
                        'rows' => [
                            ['label' => 'Patient',       'value' => "{$this->fname} {$this->lname}"],
                            ['label' => 'Contact',       'value' => $this->phone],
                            ['label' => 'Date of Birth', 'value' => $this->dob],
                            ['label' => 'Email',         'value' => $this->email],
                        ],
                    ],
                    [
                        'rows' => [
                            ['label' => 'Service', 'value' => $this->service],
                            ['label' => 'Doctor',  'value' => "{$this->doctor}"],
                        ],
                    ],
                    [
                        'rows' => [
                            ['label' => 'Scheduled', 'value' => $this->scheduleSuggestion],
                        ],
                    ],
                ],
            ];

            $this->sayLogged('APPT_CARD::' . json_encode($card));

            // → move to the transcript-email offer, THEN post-appointment options
            $this->askSendTranscriptEmail();
        } catch (\Throwable $e) {
            Log::error('Failed to save schedule visit: ' . $e->getMessage(), [
                'exception' => $e,
                'fname' => $this->fname,
                'lname' => $this->lname,
                'doctor_id' => $this->doctorId,
                'service' => $this->service,
            ]);
            $this->sayLogged("We couldn't save your schedule visit right now. Please try again, or contact us directly at 0985 475 5511.");
        }
    }


    // STEP 6: OFFER TO EMAIL THE FULL CONVERSATION TRANSCRIPT


    protected function askSendTranscriptEmail()
    {
        $question = Question::create("Would you like us to email you a copy of this whole conversation at {$this->email}?")
            ->fallback('Please choose an option from the buttons above.')
            ->addButtons([
                Button::create('Yes, email it to me')->value('yes_email'),
                Button::create('No, skip')->value('no_email'),
            ]);

        $this->askLogged($question, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'transcript_email');
            }
            if ($this->handleOffTopicIfAny($answer, 'transcript_email')) {
                return;
            }

            $choice = $this->normalizeCommand($answer);

            if ($choice === 'yes_email') {
                $this->sendTranscriptEmail();
            } else {
                $this->sayLogged("No problem, we won't send anything.");
            }

            $this->askPostAppointment(); // → move to Step 7
        });
    }

    protected function sendTranscriptEmail()
    {
        try {
            Mail::to($this->email)->send(
                new ConversationTranscriptMail($this->transcript, trim("{$this->fname} {$this->lname}"))
            );
            $this->sayLogged("Sent! Please check your inbox (and spam folder) at {$this->email}.");
        } catch (\Throwable $e) {
            Log::error('Failed to send conversation transcript email: ' . $e->getMessage(), [
                'exception' => $e,
                'email' => $this->email,
            ]);
            $this->sayLogged("Sorry, we couldn't send that email right now. Please try again later, or contact us directly.");
        }
    }


    // STEP 7: POST-APPOINTMENT ACTIONS (complaint / review / done)

    protected function askPostAppointment()
    {
        $question = Question::create('Thank you for scheduling with us!')
            ->fallback('Please use the buttons above.')
            ->addButtons([
                Button::create('File a Complaint')->value('complaint'),
                Button::create('Submit Review/Rating')->value('review'),
                Button::create("No, I'm all set")->value('done'),
            ]);

        $this->askLogged($question, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'post');
            }
            if ($this->handleOffTopicIfAny($answer, 'post')) {
                return;
            }

            $patientId = $this->bot->userStorage()->find()['patient_id'] ?? null;

            if ($answer->getValue() === 'complaint') {
                $this->bot->startConversation(new ComplaintConversation($patientId));
            } elseif ($answer->getValue() === 'review') {
                $this->bot->startConversation(new ReviewConversation($patientId, "{$this->fname} {$this->lname}"));
            } else {
                $this->sayLogged('Thank you for choosing PolyClinic Lipa! Have a great day!');
            }
        });
    }
}