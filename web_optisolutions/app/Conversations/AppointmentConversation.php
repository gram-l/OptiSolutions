<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use App\Services\ClinicInfoService;
use App\Mail\VisitConfirmationMail;
use App\Models\Staff\AppNotification;
use Illuminate\Support\Facades\DB;
use App\Support\PublicCatalog;
use App\Support\ScheduleVisitCatalog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use App\Models\Notification;

use Closure;

class AppointmentConversation extends Conversation
{
    // Message prefixes shared with the chat widget (chatbot-widget.blade.php).
    // FORM_CARD:: = bot -> widget, renders the patient information form.
    // FORM_SUBMIT:: = widget -> bot, JSON with the submitted field values.
    public const FORM_CARD_PREFIX   = 'FORM_CARD::';
    public const FORM_SUBMIT_PREFIX = 'FORM_SUBMIT::';

    protected $fname, $lname, $phone, $dob, $email;
    protected $service, $serviceKey, $serviceId;
    protected $doctor, $doctorId, $scheduleDay, $scheduleSlotIndex = 0;
    protected $scheduleId;

    // Whether to email the patient their visit confirmation (checkbox on step 1).
    protected $sendCopy = true;
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
        $raw = (string) $answer->getText();
        if (str_starts_with($raw, self::FORM_SUBMIT_PREFIX)) {
            return $this->summarizeFormSubmission($this->decodeFormSubmission($raw) ?? []);
        }

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

        if (str_starts_with($text, self::FORM_CARD_PREFIX)) {
            $form = json_decode(substr($text, strlen(self::FORM_CARD_PREFIX)), true);
            $formTitle = is_array($form) ? trim((string) ($form['title'] ?? '')) : '';
            return $formTitle !== '' ? "[Sent: {$formTitle} form]" : '[Sent: Form]';
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
            'scheduleId' => $this->scheduleId, 'sendCopy' => $this->sendCopy,
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
        $this->scheduleId = null;
        $this->sendCopy = true;
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
            case 'name':
            case 'phone':
            case 'dob':
            case 'email':
            case 'patient_form':     $this->askPatientForm(); break;
            case 'service':
            case 'doctor':
            case 'schedule':
            case 'visit_form':       $this->askVisitForm(); break;
            case 'review':           $this->askReview(); break;
            case 'transcript_email': $this->askPostAppointment(); break;
            case 'post':             $this->askPostAppointment(); break;
            case 'menu':             $this->sendMainMenu(); break;
            default:                 $this->askPatientForm(); break;
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
                Button::create('Submit Review/Rating')->value('submit review/rating'),
                Button::create('Submit Complaint')->value('submit complaint'),
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
                $this->askPatientForm();
            } elseif ($choice === 'general information') {
                $this->sayLogged(ClinicInfoService::infoCardMessage());
                $this->sendMainMenu(false);
            // Checked against both the old short value ('review'/'complaint')
            // and the current long value ('submit review/rating'/'submit
            // complaint') that the button now sends. Every menu's buttons
            // were standardized to the long form, so matching only the short
            // form here would silently break these two options and fall
            // through to "Please choose one of the options above." below.
            } elseif (in_array($choice, ['review', 'submit review/rating'], true)) {
                $this->bot->startConversation(
                    new ReviewConversation($patientId, trim("{$this->fname} {$this->lname}"))
                );
            } elseif (in_array($choice, ['complaint', 'submit complaint'], true)) {
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

        $this->askPatientForm();
    }

    // STEP 1: YOUR DETAILS — one form (name, phone, dob, email) instead of four chat questions.
    // All the original validators still run, server-side, on every submission.

    // Builds the form values from whatever is already known (e.g. after a detour).
    protected function currentFormValues(): array
    {
        $fullName = trim("{$this->fname} {$this->lname}");
        if ($this->fname !== null && $this->fname === $this->lname) {
            $fullName = (string) $this->fname; // single-word fallback from older flow
        }

        return [
            'full_name' => $fullName,
            'phone'     => (string) $this->phone,
            'dob'       => (string) $this->dob,
            'email'     => (string) $this->email,
            'send_copy' => (bool) $this->sendCopy,
        ];
    }

    // "1990-05-15" (or any accepted format) -> "May 15, 1990"; unparseable text is returned as-is.
    protected function readableDob(string $dob): string
    {
        $parsed = $dob !== '' ? $this->parseDOB($dob) : null;
        return $parsed ? $parsed->format('F j, Y') : $dob;
    }

    // Value for the <input type="date"> (needs YYYY-MM-DD); '' if it can't be parsed.
    protected function isoDobForInput(string $dob): string
    {
        $parsed = $dob !== '' ? $this->parseDOB($dob) : null;
        return $parsed ? $parsed->format('Y-m-d') : '';
    }

    // Decodes the widget's FORM_SUBMIT:: payload: strings are trimmed, booleans kept. Null if malformed.
    protected function decodeFormSubmission(string $text): ?array
    {
        if (!str_starts_with($text, self::FORM_SUBMIT_PREFIX)) {
            return null;
        }

        $data = json_decode(substr($text, strlen(self::FORM_SUBMIT_PREFIX)), true);
        if (!is_array($data)) {
            return null;
        }

        $values = [];
        foreach ($data as $key => $v) {
            if (!is_string($key)) {
                continue;
            }
            if (is_bool($v)) {
                $values[$key] = $v;
            } elseif (is_scalar($v)) {
                $values[$key] = trim((string) $v);
            } else {
                $values[$key] = '';
            }
        }

        return $values;
    }

    // String value of a decoded form field ('' when missing).
    protected function formString(array $values, string $key): string
    {
        return is_string($values[$key] ?? null) ? $values[$key] : '';
    }

    // Readable summary of a submitted form, for the transcript only (not shown in the chat).
    protected function summarizeFormSubmission(array $values): string
    {
        if (array_key_exists('confirm', $values)) {
            return 'Confirmed schedule visit';
        }

        if (array_key_exists('full_name', $values)) {
            return implode("\n", [
                'Full name: ' . $this->formString($values, 'full_name'),
                'Contact number: ' . $this->formString($values, 'phone'),
                'Date of birth: ' . $this->readableDob($this->formString($values, 'dob')),
                'Email: ' . $this->formString($values, 'email'),
            ]);
        }

        try {
            $service = DB::table('services')->where('service_key', $this->formString($values, 'service'))->value('title');
            $doctor  = DB::table('doctors')->where('doctor_id', (int) $this->formString($values, 'doctor'))->value('doctor_name');
            $slot    = DB::table('doctor_schedules')->where('schedule_id', (int) $this->formString($values, 'slot'))->first();
            $when    = $slot ? $this->slotLabel($slot) : '';
        } catch (\Throwable $e) {
            $service = $doctor = $when = '';
        }

        return implode("\n", [
            'Service: ' . ($service ?: ''),
            'Doctor: ' . ($doctor ?: ''),
            'Schedule: ' . $when,
        ]);
    }

    // Sends the "Your details" form. $errors is a field => message map; $values pre-fills it.
    public function askPatientForm(array $errors = [], ?array $values = null, ?string $notice = null)
    {
        $values = $values ?? $this->currentFormValues();
        $values['send_copy'] = $values['send_copy'] ?? true;

        $today = now()->timezone('Asia/Manila');
        $fields = [
            ['name' => 'full_name', 'label' => 'Full name',      'type' => 'text',  'placeholder' => 'e.g. Maria Santos',              'autocomplete' => 'name',  'maxlength' => 100],
            ['name' => 'phone',     'label' => 'Contact number', 'type' => 'tel',   'placeholder' => '09XX XXX XXXX',                  'autocomplete' => 'tel',   'maxlength' => 20],
            ['name' => 'dob',       'label' => 'Date of birth',  'type' => 'date',  'placeholder' => '',                               'autocomplete' => 'bday',
                'max' => $today->format('Y-m-d'), 'min' => $today->copy()->subYears(120)->format('Y-m-d')],
            ['name' => 'email',     'label' => 'Email address',  'type' => 'email', 'placeholder' => 'e.g. maria.santos@example.com',  'autocomplete' => 'email', 'maxlength' => 254],
            ['name' => 'send_copy', 'label' => 'Email me my visit confirmation', 'type' => 'checkbox'],
        ];

        if (isset($values['dob'])) {
            $values['dob'] = $this->isoDobForInput((string) $values['dob']);
        }

        foreach ($fields as &$field) {
            $field['value'] = $values[$field['name']] ?? '';
            $field['error'] = $errors[$field['name']] ?? null;
        }
        unset($field);

        $payload = [
            'title'  => 'Your details',
            'step'   => 'Step 1 of 3',
            'notice' => $notice ?? (!empty($errors) ? 'Please fix the highlighted fields and try again.' : null),
            'footer' => 'Your information is kept confidential and only used for record-keeping and to manage your visit at PolyClinic Lipa.',
            'submit' => 'Next',
            'fields' => $fields,
        ];

        $this->askLogged(self::FORM_CARD_PREFIX . json_encode($payload, JSON_UNESCAPED_UNICODE), function (Answer $answer) {
            $text = (string) $answer->getText();

            // Form submission: validate every field with the original validators.
            if (str_starts_with($text, self::FORM_SUBMIT_PREFIX)) {
                $values = $this->decodeFormSubmission($text);
                if ($values === null) {
                    return $this->askPatientForm([], $this->currentFormValues(), 'Something went wrong with that form. Please fill it out again.');
                }

                $name  = $this->formString($values, 'full_name');
                $phone = $this->formString($values, 'phone');
                $dob   = $this->formString($values, 'dob');
                $email = $this->formString($values, 'email');
                $values['send_copy'] = !empty($values['send_copy']);

                $errors = [];
                if ($e = $this->validateName($name)) {
                    $errors['full_name'] = $e . ' Example: Maria Santos';
                }
                if ($e = $this->validatePhone($phone)) {
                    $errors['phone'] = $e . ' Example: 09XX XXX XXXX or (043) 123-4567';
                }
                if ($e = $this->validateDOB($dob)) {
                    $errors['dob'] = $e . ' Please pick your date of birth from the calendar.';
                }
                if ($e = $this->validateEmail($email)) {
                    $errors['email'] = $e . ' Example: maria.santos@example.com';
                }

                if (!empty($errors)) {
                    return $this->askPatientForm($errors, $values);
                }

                $parts = preg_split('/\s+/', $name);
                $this->fname = array_shift($parts);
                $this->lname = implode(' ', $parts) ?: $this->fname;
                $this->phone = $phone;
                $this->dob   = $this->readableDob($dob);
                $this->email = $email;
                $this->sendCopy = $values['send_copy'];
                return $this->askVisitForm(); // → move to Step 2
            }

            // Anything typed in the chat box instead of using the form.
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'patient_form');
            }
            if ($this->handleOffTopicIfAny($answer, 'patient_form')) {
                return;
            }

            return $this->askPatientForm([], $this->currentFormValues(), 'Please fill out and submit the form below to continue.');
        });
    }


    // STEP 2: CHOOSE YOUR VISIT — service, doctor, and date/time in one card.
    // The widget filters doctors by service and times by doctor; the server re-checks everything.

    // "Monday, October 30, 2026 · 9AM - 12PM" for a doctor_schedules row.
    protected function slotLabel($slot): string
    {
        return $this->getNextAvailableDate($slot->day) . ' · ' . $this->formatTime($slot->start_time) . ' - ' . $this->formatTime($slot->end_time);
    }

    // Bookable services, their doctors, and each doctor's time slots (flat option lists with a parent value).
    protected function buildScheduleVisitCatalog(): array
    {
        return ScheduleVisitCatalog::build();
    }

    public function askVisitForm(array $errors = [], ?array $values = null, ?string $notice = null)
    {
        $catalog = $this->buildScheduleVisitCatalog();

        if (empty($catalog['services'])) {
            $this->sayLogged("We couldn't find any available doctors right now. Please try again later, or contact us directly at 0985 475 5511.");
            return;
        }

        $values = $values ?? [
            'service' => (string) $this->serviceKey,
            'doctor'  => $this->doctorId ? (string) $this->doctorId : '',
            'slot'    => $this->scheduleId ? (string) $this->scheduleId : '',
        ];

        $fields = [
            ['name' => 'service', 'label' => 'Service',        'type' => 'select', 'placeholder' => 'Select a service',        'options' => $catalog['services']],
            ['name' => 'doctor',  'label' => 'Doctor',         'type' => 'select', 'placeholder' => 'Select a doctor',         'options' => $catalog['doctors'], 'depends_on' => 'service'],
            ['name' => 'slot',    'label' => 'Date and time',  'type' => 'select', 'placeholder' => 'Select a date and time',  'options' => $catalog['slots'],   'depends_on' => 'doctor'],
        ];

        foreach ($fields as &$field) {
            $field['value'] = $values[$field['name']] ?? '';
            $field['error'] = $errors[$field['name']] ?? null;
        }
        unset($field);

        $patientName = trim("{$this->fname} {$this->lname}");

        $payload = [
            'title'  => 'Choose your visit',
            'step'   => 'Step 2 of 3',
            'notice' => $notice ?? (!empty($errors) ? 'Please fix the highlighted fields and try again.' : ($patientName !== '' ? "Visit for {$patientName}" : null)),
            'submit' => 'Next',
            'secondary' => [['label' => 'Back', 'value' => 'back_to_details']],
            'fields' => $fields,
        ];

        $this->askLogged(self::FORM_CARD_PREFIX . json_encode($payload, JSON_UNESCAPED_UNICODE), function (Answer $answer) {
            $text = (string) $answer->getText();

            if (str_starts_with($text, self::FORM_SUBMIT_PREFIX)) {
                $values = $this->decodeFormSubmission($text);
                if ($values === null) {
                    return $this->askVisitForm([], null, 'Something went wrong with that form. Please try again.');
                }

                $serviceKey = $this->formString($values, 'service');
                $doctorId   = $this->formString($values, 'doctor');
                $slotId     = $this->formString($values, 'slot');
                $errors = [];

                $svc = $serviceKey !== ''
                    ? PublicCatalog::services()->where('service_key', $serviceKey)->first()
                    : null;
                if (!$svc) {
                    $errors['service'] = 'Please select a service.';
                }

                $doc = null;
                if ($svc && ctype_digit($doctorId)) {
                    $doc = PublicCatalog::doctors()
                        ->where('doctor_id', (int) $doctorId)
                        ->where('specialty', $svc->title)
                        ->first();
                }
                if (!$doc) {
                    $errors['doctor'] = 'Please select a doctor.';
                }

                $slot = null;
                if ($doc && ctype_digit($slotId)) {
                    $slot = DB::table('doctor_schedules')
                        ->where('schedule_id', (int) $slotId)
                        ->where('doctor_id', $doc->doctor_id)
                        ->first();
                }
                if (!$slot) {
                    $errors['slot'] = 'Please select an available date and time.';
                }

                if (!empty($errors)) {
                    return $this->askVisitForm($errors, [
                        'service' => $serviceKey, 'doctor' => $doctorId, 'slot' => $slotId,
                    ]);
                }

                $this->service    = $svc->title;
                $this->serviceKey = $svc->service_key;
                $this->serviceId  = $svc->service_id;
                $this->doctor     = $doc->doctor_name;
                $this->doctorId   = $doc->doctor_id;
                $this->scheduleId = $slot->schedule_id;
                $this->scheduleDay = $slot->day;
                $this->scheduleSuggestion = $this->getNextAvailableDate($slot->day)
                    . ' at ' . $this->formatTime($slot->start_time) . ' - ' . $this->formatTime($slot->end_time);

                return $this->askReview(); // → move to Step 3 (review before saving)
            }

            // "Back" button: return to the details form with everything still filled in.
            if (trim($text) === 'back_to_details') {
                return $this->askPatientForm();
            }

            // Anything typed in the chat box instead of using the form.
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'visit_form');
            }
            if ($this->handleOffTopicIfAny($answer, 'visit_form')) {
                return;
            }

            return $this->askVisitForm([], null, 'Please choose your service, doctor, and schedule below to continue.');
        });
    }

    // STEP 3: REVIEW — read-only summary of everything entered, then "Confirm Schedule Visit".

    public function askReview(?string $notice = null)
    {
        // Nothing to review yet (e.g. resumed mid-way): go back to the step that is missing.
        if (!$this->fname || !$this->scheduleId) {
            return $this->fname ? $this->askVisitForm() : $this->askPatientForm();
        }

        $fullName = trim("{$this->fname} {$this->lname}");
        if ($this->fname === $this->lname) {
            $fullName = (string) $this->fname;
        }

        $payload = [
            'title'   => 'Review your visit',
            'step'    => 'Step 3 of 3',
            'notice'  => $notice ?? 'Please check that everything is correct before confirming.',
            'summary' => [
                ['heading' => 'Your details', 'rows' => [
                    ['label' => 'Full name',      'value' => $fullName],
                    ['label' => 'Contact number', 'value' => (string) $this->phone],
                    ['label' => 'Date of birth',  'value' => (string) $this->dob],
                    ['label' => 'Email',          'value' => (string) $this->email],
                ]],
                ['heading' => 'Your visit', 'rows' => [
                    ['label' => 'Service',       'value' => (string) $this->service],
                    ['label' => 'Doctor',        'value' => (string) $this->doctor],
                    ['label' => 'Date and time', 'value' => (string) $this->scheduleSuggestion],
                ]],
            ],
            'fields'  => [],
            'confirm' => true,
            'saving'  => true,
            'submit'  => 'Confirm Schedule Visit',
            'secondary' => [
                ['label' => 'Edit details', 'value' => 'back_to_details'],
                ['label' => 'Change visit', 'value' => 'back_to_visit'],
            ],
        ];

        $this->askLogged(self::FORM_CARD_PREFIX . json_encode($payload, JSON_UNESCAPED_UNICODE), function (Answer $answer) {
            $text = (string) $answer->getText();

            if (str_starts_with($text, self::FORM_SUBMIT_PREFIX)) {
                $values = $this->decodeFormSubmission($text);
                if (is_array($values) && !empty($values['confirm'])) {
                    return $this->submitAppointment(); // → move to Step 4
                }
                return $this->askReview('Something went wrong. Please try confirming again.');
            }

            // The Edit / Change buttons: go back with everything still filled in.
            if (trim($text) === 'back_to_details') {
                return $this->askPatientForm();
            }
            if (trim($text) === 'back_to_visit') {
                return $this->askVisitForm();
            }

            // Anything typed in the chat box instead of using the buttons.
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'review');
            }
            if ($this->handleOffTopicIfAny($answer, 'review')) {
                return;
            }

            return $this->askReview('Please use the buttons below to confirm or edit your visit.');
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


    // STEP 4: SUBMIT & SAVE TO DATABASE


    protected function submitAppointment()
    {
        // NOTE: the "Saving your schedule visit..." indicator is shown by the
        // chat widget only while this request is actually in flight, and
        // removed as soon as it finishes (see sendMessage() in
        // chatbot-widget.blade.php). It is intentionally NOT a bot message.
        try {
            $patientId = DB::table('patients')->insertGetId([
                'patient_fname'     => $this->fname,
                'patient_lname'     => $this->lname,
                'patient_birthdate' => $this->normalizeDOB($this->dob),
                'patient_email'     => $this->email,
                'patient_contact'   => $this->phone,
            ]);

            $visitDate = $this->getNextAvailableISODate($this->scheduleDay);

            $visitId = DB::table('schedule_visit')->insertGetId([
                'doctor_id'    => $this->doctorId,
                'patient_id'   => $patientId,
                'service_type' => $this->service,
                'visit_date'   => $visitDate,
                'notes'        => $this->scheduleSuggestion,
                'scheduled_at' => now(),
            ]);

            // Notification failures should not affect the saved appointment.
            // Uses NotificationService so the row gets the required `type`
            // ('appointment') plus reference_type/reference_id.
            try {
                NotificationService::newVisit(
                    trim("{$this->fname} {$this->lname}"),
                    (string) $this->doctor,
                    $visitDate,
                    (int) $visitId
                );
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

            // Email the visit confirmation if the box on the details form was left ticked.
            if ($this->sendCopy) {
                $this->sendConfirmationEmail((int) $visitId);
            }

            $this->askPostAppointment(); // → move to the final step
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


    // STEP 5: EMAIL THE VISIT CONFIRMATION (only if the patient ticked the box on step 1)

    protected function sendConfirmationEmail(int $visitId)
    {
        try {
            Mail::to($this->email)->send(
                new VisitConfirmationMail(
                    $this->fname === $this->lname ? (string) $this->fname : trim("{$this->fname} {$this->lname}"),
                    (string) $this->service,
                    (string) $this->doctor,
                    (string) $this->scheduleSuggestion,
                    $visitId
                )
            );
            $this->sayLogged("Your visit confirmation has been sent to {$this->email}. Please check your inbox, including your spam folder.");
        } catch (\Throwable $e) {
            Log::error('Failed to send visit confirmation email: ' . $e->getMessage(), [
                'exception' => $e,
                'email' => $this->email,
            ]);
            $this->sayLogged("Sorry, we couldn't send the confirmation email right now. Your visit is still saved, so please take a screenshot of the confirmation above, or contact us at 0985 475 5511.");
        }
    }


    // STEP 6: POST-APPOINTMENT ACTIONS (schedule again / info / review / complaint)

    protected function askPostAppointment()
    {
        // No "Thank you for scheduling your visit" here anymore. First ask if
        // there's anything else. "No" → thank-you + end. "Yes" → counts as an
        // inquiry, and the thank-you only appears once staff resolves it.
        $patientId = $this->bot->userStorage()->find()['patient_id'] ?? null;
        $this->bot->startConversation(
            new AnythingElseConversation(
                $patientId,
                trim("{$this->fname} {$this->lname}")
            )
        );
    }
}