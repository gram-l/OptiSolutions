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

    // ---------------------------------------------------------------
    // GLOBAL COMMAND INTERCEPTION
    //
    // BotMan routes every incoming message to whatever ask() callback
    // is currently waiting — it does NOT re-check the top-level
    // hears() patterns in BotManController while a conversation is
    // active. That's why clicking "General Information" mid-schedule
    // was previously being swallowed as the answer to "full name",
    // "contact number", etc.
    //
    // Each ask() callback below calls isGlobalCommand() first. If the
    // incoming answer is actually one of these commands rather than a
    // real answer, we hand off to handleGlobalCommand() instead of
    // running the step's normal validation/assignment logic.
    // ---------------------------------------------------------------

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
     * Dispatches to the correct step for a given step key. Plain switch
     * on a string — no closures involved.
     */
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

    /**
     * Shown right after the general-information card is displayed
     * mid-schedule-visit. Lets the user decide what to do next instead
     * of the bot silently deciding for them.
     */
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

    // ---------------------------------------------------------------
    // Validation helpers
    // ---------------------------------------------------------------

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
        return null;
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
        $this->askName();
    }

    // STEP 1: PATIENT INFO (name → phone → dob → email)

    public function askName($prompt = 'Please provide your full name (first and last name):')
    {
        $this->askLogged($prompt, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, 'name');
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
                'title' => "Schedule Visit Confirmed, {$this->fname} {$this->lname}!",
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