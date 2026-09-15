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

    // Full message log for this conversation, used to email a transcript later.
    protected $transcript = [];

    // Serialized callback for the current askLogged() step.
    protected $pendingAnswerCallback;

    // value => label map for the buttons on the most recent question.
    protected $pendingButtonLabels = [];

    // Step/state to resume after a detour into InquiryConversation.
    protected $pendingResumeStepKey;
    protected $pendingResumeState;

    public function __construct($resumeStepKey = null, $resumeState = null)
    {
        $this->pendingResumeStepKey = $resumeStepKey;
        $this->pendingResumeState = $resumeState;
    }

    // sayLogged()/askLogged() wrap BotMan's say()/ask() and also log to $transcript.

    protected function sayLogged($message)
    {
        $this->logMessage('Bot', $message);
        $this->say($message);
    }

    protected function askLogged($question, callable $callback)
    {
        $this->logMessage('Bot', $question);

        // Save button labels for this question so answers log nicely.
        $this->pendingButtonLabels = $this->extractButtonLabels($question);

        // Serialize the step's callback and stash it on a plain property.
        $this->pendingAnswerCallback = $this->bot->serializeClosure(
            Closure::fromCallable($callback)
        );

        // Always pass BotMan the same stable handler.
        $this->ask($question, Closure::fromCallable([$this, 'handleLoggedAnswer']));
    }

    // The single "next" callback BotMan stores for every askLogged() call.
    public function handleLoggedAnswer(Answer $answer)
    {
        $this->logMessage('You', $this->displayTextForAnswer($answer));

        $callback = unserialize($this->pendingAnswerCallback)->getClosure();

        // Rebind to the live $this so $this->bot works correctly.
        $callback = Closure::bind($callback, $this, static::class);

        return $callback($answer);
    }

    // Builds a value => label map from a Question's buttons.
    protected function extractButtonLabels($question): array
    {
        if (!$question instanceof Question || !method_exists($question, 'getButtons')) {
            return [];
        }

        $labels = [];
        foreach ($question->getButtons() as $button) {
            $value = $button['value'] ?? null;
            $label = $button['text'] ?? $button['name'] ?? null;
            if ($value !== null && $label !== null && $label !== '') {
                $labels[(string) $value] = $label;
            }
        }

        return $labels;
    }

    // Returns the human-readable text to log for the patient's answer.
    protected function displayTextForAnswer(Answer $answer): string
    {
        $value = (string) ($answer->getValue() ?: $answer->getText());

        if (isset($this->pendingButtonLabels[$value])) {
            return $this->pendingButtonLabels[$value];
        }

        return $answer->getText();
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
            // Local Philippine time for the transcript.
            'time' => now()->timezone('Asia/Manila')->format('h:i A'),
        ];
    }

    protected function extractText($content): string
    {
        if ($content instanceof Question) {
            $text = method_exists($content, 'getText') ? $content->getText() : '';
            $text = trim((string) $text);

            // Include button options in the transcript text.
            if (method_exists($content, 'getButtons')) {
                $options = [];
                foreach ($content->getButtons() as $i => $button) {
                    $label = trim((string) ($button['text'] ?? $button['name'] ?? ''));
                    if ($label !== '') {
                        $options[] = ($i + 1) . ". {$label}";
                    }
                }
                if (!empty($options)) {
                    $text = ($text !== '' ? $text . "\n" : '') . implode("\n", $options);
                }
            }
        } elseif (is_string($content)) {
            $text = $content;
        } else {
            $text = (string) $content;
        }

        // Summarize card payloads instead of dumping raw JSON.
        if (str_starts_with($text, 'INFO_CARD::')) {
            return '[Sent: Clinic Information Card]';
        }

        if (str_starts_with($text, 'APPT_CARD::')) {
            $decoded = json_decode(substr($text, strlen('APPT_CARD::')), true);

            if (!$decoded) {
                return '[Sent: Schedule Visit Confirmation Card]';
            }

            $lines = [];

            if (!empty($decoded['title'])) {
                $lines[] = trim(str_replace("\n", ' ', $decoded['title']));
            }

            foreach (($decoded['sections'] ?? []) as $section) {
                foreach (($section['rows'] ?? []) as $row) {
                    $label = $row['label'] ?? '';
                    $value = $row['value'] ?? '';
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }
                    $lines[] = "{$label}: {$value}";
                }
            }

            if (!empty($decoded['footer'])) {
                $lines[] = trim($decoded['footer']);
            }

            return implode("\n", $lines);
        }

        // Keep ** markdown markers; the email view renders them as bold.
        return trim($text);
    }

   
    protected function normalizeCommand(Answer $answer): string
    {
        return strtolower(trim($answer->getValue() ?: $answer->getText()));
    }

    // Check if the answer is a menu command.
    protected function isGlobalCommand(Answer $answer): bool
    {
        return in_array($this->normalizeCommand($answer), [
            'general information',
            'menu',
            'schedule visit',
            'review', 'submit review/rating',
            'complaint', 'submit complaint',
            'cancel',
        ], true);
    }

    // Handles a global command typed/clicked during any step.
    protected function handleGlobalCommand(Answer $answer, string $stepKey)
    {
        $cmd = $this->normalizeCommand($answer);

        if ($cmd === 'general information') {
            $this->sayLogged(ClinicInfoService::infoCardMessage());
            $this->askContinueOrRestart($stepKey);
            return;
        }

        if (in_array($cmd, ['review', 'submit review/rating'], true)) {
            $patientId = $this->bot->userStorage()->find()['patient_id'] ?? null;
            $this->bot->startConversation(
                new ReviewConversation($patientId, trim("{$this->fname} {$this->lname}"))
            );
            return;
        }

        if (in_array($cmd, ['complaint', 'submit complaint'], true)) {
            $patientId = $this->bot->userStorage()->find()['patient_id'] ?? null;
            $this->bot->startConversation(new ComplaintConversation($patientId, trim("{$this->fname} {$this->lname}")));
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

    // Off-topic / inquiry detection helpers.

    // Checks common attachment shapes across BotMan drivers.
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

    // Heuristic: does this free-typed answer look like a question?
    protected function looksLikeInquiry(Answer $answer): bool
    {
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

    // Checks if the text matches a canned clinic-info answer.
    protected function looksLikeInfoRequest(string $text): bool
    {
        return ClinicInfoService::looksLikeInfoRequest($text);
    }

    // Checks if the text is just an acknowledgment/thank-you.
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

    // Entry point every step calls before its own validation.
    protected function handleOffTopicIfAny(Answer $answer, string $stepKey): bool
    {
        // Acknowledgment — reply and re-ask the same step.
        if ($this->looksLikeAcknowledgment($answer)) {
            $this->logMessage('You', $answer->getText());
            $this->sayLogged(ClinicInfoService::acknowledgmentReply());
            $this->resumeStep($stepKey);
            return true;
        }

        // Plain greeting ("Hi", "Hello", "Kumusta") — answered directly,
        // never forwarded to Admin/Staff.
        $isButtonTap = method_exists($answer, 'isInteractiveMessageReply') && $answer->isInteractiveMessageReply();
        if (!$isButtonTap) {
            $freeText = trim($answer->getText());

            if (ClinicInfoService::looksLikeGreeting($freeText)) {
                $this->logMessage('You', $freeText);
                $this->sayLogged(ClinicInfoService::greetingReply());
                $this->resumeStep($stepKey);
                return true;
            }

            if (ClinicInfoService::looksLikeAskingPermission($freeText)) {
                $this->logMessage('You', $freeText);
                $this->sayLogged(ClinicInfoService::askingPermissionReply());
                $this->resumeStep($stepKey);
                return true;
            }
        }

        // Attachments always go to staff.
        if ($this->hasAttachment($answer)) {
            $this->routeToInquiry($answer, $stepKey);
            return true;
        }

        if (!$this->looksLikeInquiry($answer)) {
            return false;
        }

        $text = trim($answer->getText());

        // Known info request — answer it directly.
        if ($this->looksLikeInfoRequest($text)) {
            $this->logMessage('You', $text);
            $this->sayLogged(ClinicInfoService::answerForText($text) ?? ClinicInfoService::infoCardMessage());
            $this->askContinueOrRestart($stepKey);
            return true;
        }

        // Anything else — route to staff.
        $this->routeToInquiry($answer, $stepKey);
        return true;
    }

    // Routes an off-topic message to staff and stashes progress to resume later.
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
        $this->pendingButtonLabels = [];
    }

    // Restores saved state after resuming from InquiryConversation.
    protected function restoreState(array $state)
    {
        foreach ($state as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    // Dispatches to the correct step for a given step key.
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
            case 'menu':             $this->sendMainMenu(); break;
            default:                 $this->askName(); break;
        }
    }

    // Lets the patient continue, restart, or cancel after a mid-flow info card.
    protected function askContinueOrRestart(string $stepKey)
    {
        // At the plain main menu there's no schedule-visit-in-progress to
        // "continue" or "restart" — just drop them back on the menu.
        if ($stepKey === 'menu') {
            $this->sendMainMenu();
            return;
        }

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

    // Shows the main menu and routes the chosen action. Pass
    // $showGreeting = false to re-show just the buttons without
    // repeating the "Hello! Welcome..." greeting bubble — used when
    // the menu is being re-displayed right after General Information
    // was already answered, so the greeting doesn't show up twice.
    protected function sendMainMenu(bool $showGreeting = true)
    {
        $question = Question::create($showGreeting ? 'Hello! Welcome to PolyClinic Lipa. How can I help you today?' : '')
            ->fallback('Please choose an option from the buttons above.')
            ->addButtons([
                Button::create('Schedule Visit')->value('schedule visit'),
                Button::create('General Information')->value('general information'),
                Button::create('Submit Review/Rating')->value('review'),
                Button::create('Submit Complaint')->value('complaint'),
            ]);

        $this->askLogged($question, function (Answer $answer) {
            // A genuine free-typed question right at the greeting screen
            // (instead of a button tap) now reaches staff via
            // InquiryConversation, rather than being silently ignored.
            if ($this->handleOffTopicIfAny($answer, 'menu')) {
                return;
            }

            $choice = $this->normalizeCommand($answer);
            $patientId = $this->bot->userStorage()->find()['patient_id'] ?? null;

            if ($choice === 'schedule visit') {
                $this->resetState();
                $this->askName();
            } elseif ($choice === 'general information') {
                $this->sayLogged(ClinicInfoService::infoCardMessage());
                $this->sendMainMenu(false);
            } elseif ($choice === 'review') {
                $this->bot->startConversation(
                    new ReviewConversation($patientId, trim("{$this->fname} {$this->lname}"))
                );
            } elseif ($choice === 'complaint') {
                $this->bot->startConversation(new ComplaintConversation($patientId));
            } else {
                $this->sayLogged('Please choose one of the options above.');
                $this->sendMainMenu();
            }
        });
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
            // Allow a single-letter middle initial, with or without a trailing
            // period (e.g. "D" or "D."), so names like "Juan D. Cruz" pass.
            if (preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ]\.?$/u", $w)) {
                continue;
            }
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
            return "Please enter a valid Philippine phone number.";

        // Reject numbers that pass the shape check but look fake.
        if ($this->isFakeLookingNumber($digits))
            return "This doesn't look like a real phone number. Please double-check and enter it again.";

        return null;
    }

    // Flags obviously fake numbers (repeated, sequential, or patterned digits).
    protected function isFakeLookingNumber($digits)
    {
        // Strip the leading trunk/country code before pattern-checking.
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

        // e.g. 121212121 or 123123123
        for ($blockLen = 1; $blockLen <= 3; $blockLen++) {
            if (strlen($core) % $blockLen !== 0) continue;
            $block = substr($core, 0, $blockLen);
            if (str_repeat($block, (int) (strlen($core) / $blockLen)) === $core) {
                return true;
            }
        }

        // 6+ of the same digit in a row anywhere.
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
        if (!$parsed) return "Please enter a valid date.";

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
            return "Please enter a valid email address.";
        if (str_contains($trimmed, '..'))
            return "Email address cannot contain consecutive dots.";
        return null;
    }

    public function run()
    {
        // Resume a schedule visit that detoured into InquiryConversation.
        if ($this->pendingResumeStepKey) {
            $this->restoreState($this->pendingResumeState ?? []);
            $stepKey = $this->pendingResumeStepKey;
            $this->pendingResumeStepKey = null;
            $this->pendingResumeState = null;
            $this->resumeStep($stepKey);
            return;
        }

        $this->sayLogged("🔒 **Reminder**\nThe information you provide (name, contact number, date of birth, and email) is kept confidential and will only be used for record-keeping and to manage your visit at PolyClinic Lipa.");

        $this->askName();
    }

    // STEP 1: PATIENT INFO (name → phone → dob → email)

    public function askName($prompt = 'Please provide your full name.')
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
                return $this->askName($error . "\nExample: Pedro Cruz");
            }
            $parts = preg_split('/\s+/', trim($answer->getText()));
            $this->fname = array_shift($parts);
            $this->lname = implode(' ', $parts) ?: $this->fname;
            $this->askPhone();
        });
    }

    public function askPhone($prompt = 'Please provide your contact number.')
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
                return $this->askPhone($error . "\nExample: 09XX XXX XXXX or (043) 123-4567");
            }
            $this->phone = trim($answer->getText());
            $this->askDob();
        });
    }

    public function askDob($prompt = 'Please provide your date of birth.')
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
                return $this->askDob($error . "\nExample: 05/15/1990 or May 15, 1990");
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
                return $this->askEmail($error . "\nExample: juan@example.com");
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
            ->where('status', 'Active')
            ->where('specialty', $this->service)
            ->get();

        if ($doctors->isEmpty()) {
            $this->sayLogged("We currently don't have a specialist for {$this->service}. Please choose another service:");
            $this->service = null;
            $this->serviceKey = null;
            $this->serviceId = null;
            $this->askService();
            return;
        }

        $buttons = $doctors->map(
            fn($d) => Button::create("{$d->doctor_name} — {$d->specialty}")->value((string) $d->doctor_id)
        )->toArray();

        $question = Question::create("Here are our specialists for {$this->service}:")
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

        // Once we run out of distinct slots, stop offering "suggest
        // alternative" instead of silently wrapping back to slot 0
        // (which used to look like the same time was re-suggested
        // forever with no explanation).
        $noMoreAlternatives = $this->scheduleSlotIndex >= $schedules->count();
        $slot = $schedules[$this->scheduleSlotIndex] ?? $schedules[0];
        $this->scheduleDay = $slot->day;
        $suggestedDate = $this->getNextAvailableDate($slot->day);
        $this->scheduleSuggestion = "{$suggestedDate} at {$this->formatTime($slot->start_time)} - {$this->formatTime($slot->end_time)}";

        // Bold header + date/time + note, each on its own line.
        $scheduleText = "**Suggested schedule:**\n{$this->scheduleSuggestion}.\nBased on {$this->doctor}'s availability.";

        if ($noMoreAlternatives) {
            $scheduleText .= "\n\nThis is the last available time slot we have for {$this->doctor}.";
        }

        $buttons = [Button::create('Yes, confirm')->value('confirm_yes')];
        if (!$noMoreAlternatives) {
            $buttons[] = Button::create('Suggest alternative')->value('confirm_no');
        }

        $question = Question::create($scheduleText)
            ->fallback('Please use the confirmation buttons above.')
            ->addButtons($buttons);

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

            // Notification failures should not affect the saved appointment.
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

            // Confirmation card: title + screenshot note, patient/service/schedule sections.
            $card = [
                'title' => "Schedule Visit Confirmed\nPlease take a screenshot of this confirmation for your records.",
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
            $this->sayLogged("Your conversation transcript has been sent to {$this->email}. Please check your inbox, including your spam folder.");
        } catch (\Throwable $e) {
            Log::error('Failed to send conversation transcript email: ' . $e->getMessage(), [
                'exception' => $e,
                'email' => $this->email,
            ]);
            $this->sayLogged("Sorry, we couldn't send that email right now. Please try again later, or contact us directly.");
        }
    }


    // STEP 7: POST-APPOINTMENT ACTIONS (schedule again / info / review / complaint)

    protected function askPostAppointment()
    {
        $this->sayLogged('Thank you for scheduling your visit with us.');

        $question = Question::create('Is there anything else I can help you with?')
            ->fallback('Please use the buttons above.')
            ->addButtons([
                Button::create('Schedule Visit')->value('post_schedule_visit'),
                Button::create('General Information')->value('post_general_information'),
                Button::create('Submit Review/Rating')->value('review'),
                Button::create('Submit Complaint')->value('complaint'),
            ]);

        $this->askLogged($question, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'post');
            }
            if ($this->handleOffTopicIfAny($answer, 'post')) {
                return;
            }

            $choice = $answer->getValue();
            $patientId = $this->bot->userStorage()->find()['patient_id'] ?? null;

            if ($choice === 'post_schedule_visit') {
                // Restart from the greeting + main menu instead of askName().
                $this->resetState();
                $this->sendMainMenu();
            } elseif ($choice === 'post_general_information') {
                $this->sayLogged(ClinicInfoService::infoCardMessage());
                $this->askPostAppointment();
            } elseif ($choice === 'review') {
                $this->bot->startConversation(new ReviewConversation($patientId, "{$this->fname} {$this->lname}"));
            } elseif ($choice === 'complaint') {
                $this->bot->startConversation(new ComplaintConversation($patientId));
            } else {
                $this->sayLogged('Please choose one of the options above.');
                $this->askPostAppointment();
            }
        });
    }
}