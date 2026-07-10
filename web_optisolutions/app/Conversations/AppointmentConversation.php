<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use Illuminate\Support\Facades\DB;

class AppointmentConversation extends Conversation
{
    protected $fname, $lname, $phone, $dob, $email;
    protected $service, $serviceKey, $serviceId;
    protected $doctor, $doctorId, $scheduleDay, $scheduleSlotIndex = 0;
    protected $scheduleSuggestion;

    // ================================================================
    // VALIDATION HELPERS
    // ================================================================

    protected function validateName($name)
    {
        $trimmed = trim($name);
        if (!$trimmed) return "⚠️ Name cannot be empty. Please enter your full name.";
        if (!preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ'\-. ]+$/u", $trimmed))
            return "⚠️ Name can only contain letters, spaces, hyphens, or apostrophes.";
        $words = array_filter(preg_split('/\s+/', $trimmed));
        if (count($words) < 2) return "⚠️ Please enter your full name.";
        foreach ($words as $w) {
            if (strlen(preg_replace("/['.]/", '', $w)) < 2)
                return "⚠️ Each part of your name must be at least 2 characters.";
        }
        return null;
    }

    protected function validatePhone($phone)
    {
        $trimmed = trim($phone);
        if (!$trimmed) return "⚠️ Contact number cannot be empty.";
        $digits = preg_replace('/[\s\-().+]/', '', $trimmed);
        if (!preg_match('/^\d+$/', $digits))
            return "⚠️ Phone number can only contain digits, spaces, dashes, or parentheses.";
        $mobileLocal = preg_match('/^09\d{9}$/', $digits);
        $mobileIntl  = preg_match('/^639\d{9}$/', $digits);
        $landline    = preg_match('/^(0\d{9,10}|\d{7,8})$/', $digits);
        if (!$mobileLocal && !$mobileIntl && !$landline)
            return "⚠️ Please enter a valid PH phone number (e.g. 09XX XXX XXXX or (043) 123-4567).";
        return null;
    }

    protected function validateDOB($dob)
    {
        $trimmed = trim($dob);
        if (!$trimmed) return "⚠️ Date of birth cannot be empty.";

        $parsed = $this->parseDOB($trimmed);
        if (!$parsed) return "⚠️ Please enter a valid date (MM/DD/YYYY, YYYY-MM-DD, or e.g. March 15, 2005).";

        $today = new \DateTime('today');
        if ($parsed > $today) return "⚠️ Date of birth cannot be in the future.";

        $age = $today->diff($parsed)->y;
        if ($age > 120) return "⚠️ Please enter a valid date of birth (age must be 120 or below).";

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
        if (!$trimmed) return "⚠️ Email address cannot be empty.";
        if (!filter_var($trimmed, FILTER_VALIDATE_EMAIL))
            return "⚠️ Please enter a valid email address (e.g. juan@example.com).";
        if (str_contains($trimmed, '..'))
            return "⚠️ Email address cannot contain consecutive dots.";
        return null;
    }

    // ================================================================
    // STEP 0: ENTRY POINT
    // ================================================================

    public function run()
    {
        $this->askName();
    }

    // ================================================================
    // STEP 1: PATIENT INFO (name → phone → dob → email)
    // ================================================================

    public function askName()
    {
        $this->ask('Please provide your full name (first and last name):', function (Answer $answer) {
            $error = $this->validateName($answer->getText());
            if ($error) {
                $this->say($error . "\n\nPlease enter your full name (e.g. Juan dela Cruz):");
                return $this->askName();
            }
            $parts = preg_split('/\s+/', trim($answer->getText()));
            $this->fname = array_shift($parts);
            $this->lname = implode(' ', $parts) ?: $this->fname;
            $this->say("Thanks, {$this->fname} {$this->lname}!");
            $this->askPhone();
        });
    }

    public function askPhone()
    {
        $this->ask('Please provide your contact number (e.g. 09XX XXX XXXX):', function (Answer $answer) {
            $error = $this->validatePhone($answer->getText());
            if ($error) {
                $this->say($error . "\n\nPlease enter a valid Philippine phone number:");
                return $this->askPhone();
            }
            $this->phone = trim($answer->getText());
            $this->askDob();
        });
    }

    public function askDob()
    {
        $this->ask('Please provide your date of birth (e.g. 05/15/1990 or May 15, 1990):', function (Answer $answer) {
            $error = $this->validateDOB($answer->getText());
            if ($error) {
                $this->say($error . "\n\nPlease enter your date of birth (e.g. 05/15/1990 or May 15, 1990):");
                return $this->askDob();
            }
            $this->dob = trim($answer->getText());
            $this->askEmail();
        });
    }

    public function askEmail()
    {
        $this->ask('Please provide your email address:', function (Answer $answer) {
            $error = $this->validateEmail($answer->getText());
            if ($error) {
                $this->say($error . "\n\nPlease enter a valid email address (e.g. juan@example.com):");
                return $this->askEmail();
            }
            $this->email = trim($answer->getText());
            $this->askService(); // → move to Step 2
        });
    }

    // ================================================================
    // STEP 2: SERVICE SELECTION
    // ================================================================

    public function askService()
    {
        $services = DB::table('services')->where('available', 1)->get();

        if ($services->isEmpty()) {
            $this->say("⚠️ We couldn't load our services right now. Please try again in a moment.");
            return;
        }

        $buttons = $services->map(fn($s) => Button::create($s->title)->value($s->service_key))->toArray();

        $question = Question::create('Please select the service you need:')
            ->fallback('Please select a service from the buttons above.')
            ->addButtons($buttons);

        $this->ask($question, function (Answer $answer) {
            $key = $answer->getValue();
            $svc = DB::table('services')->where('service_key', $key)->first();
            if (!$svc) {
                $this->say("Sorry, I didn't recognize that service. Please try again.");
                return $this->askService();
            }
            $this->service = $svc->title;
            $this->serviceKey = $svc->service_key;
            $this->serviceId = $svc->service_id;
            $this->askDoctor(); // → move to Step 3
        });
    }

    // ================================================================
    // STEP 3: DOCTOR SELECTION (matched by specialty = service title)
    // ================================================================

    public function askDoctor()
    {
        $doctors = DB::table('doctors')
            ->where('available', 1)
            ->where('specialty', $this->service)
            ->get();

        if ($doctors->isEmpty()) {
            $this->say("We currently don't have a specialist for {$this->service}. Returning to main menu.");
            return;
        }

        // IMPORTANT: i-cast ang doctor_id to string dito. Ang mga button values ay
        // laging bumabalik bilang STRING mula sa frontend/BotMan web driver, kaya kung
        // integer ang naka-store na value (galing DB), hindi ito matu-tugma (strict
        // type check sa BotMan) sa string na natatanggap pagbalik ng sagot ng user.
        $buttons = $doctors->map(
            fn($d) => Button::create("{$d->doctor_name} — {$d->specialty}")->value((string) $d->doctor_id)
        )->toArray();

        $question = Question::create("Great! Here are our specialists for {$this->service}:")
            ->fallback('Please select a doctor from the buttons above.')
            ->addButtons($buttons);

        $this->ask($question, function (Answer $answer) {
            // I-cast pabalik sa integer bago i-query, dahil ang column na doctor_id
            // ay int sa database.
            $doctorId = (int) $answer->getValue();
            $doc = DB::table('doctors')->where('doctor_id', $doctorId)->first();
            if (!$doc) {
                $this->say("Sorry, I couldn't find this doctor. Please try again.");
                return $this->askDoctor();
            }
            $this->doctor = $doc->doctor_name;
            $this->doctorId = $doc->doctor_id;
            $this->suggestSchedule(); // → move to Step 4
        });
    }

    // ================================================================
    // STEP 4: SCHEDULE SUGGESTION + CONFIRMATION
    // ================================================================

    protected function suggestSchedule()
    {
        $schedules = DB::table('doctor_schedules')->where('doctor_id', $this->doctorId)->get();

        if ($schedules->isEmpty()) {
            $this->say("Sorry, I couldn't find availability for this doctor. Please try another.");
            return $this->askDoctor();
        }

        $slot = $schedules[$this->scheduleSlotIndex] ?? $schedules[0];
        $this->scheduleDay = $slot->day;
        $suggestedDate = $this->getNextAvailableDate($slot->day);
        $this->scheduleSuggestion = "{$suggestedDate} at {$this->formatTime($slot->start_time)} - {$this->formatTime($slot->end_time)}";

        $question = Question::create("📅 Suggested schedule: {$this->scheduleSuggestion}\n(Based on Dr. {$this->doctor}'s availability — {$slot->day})")
            ->fallback('Please use the confirmation buttons above.')
            ->addButtons([
                Button::create('✅ Yes, confirm')->value('confirm_yes'),
                Button::create('🔄 Suggest alternative')->value('confirm_no'),
            ]);

        $this->ask($question, function (Answer $answer) {
            if ($answer->getValue() === 'confirm_yes') {
                $this->submitAppointment(); // → move to Step 5
            } else {
                $this->scheduleSlotIndex++;
                $this->suggestSchedule();
            }
        });
    }

    // ---------- Date/time helpers ----------

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

    // ================================================================
    // STEP 5: SUBMIT & SAVE TO DATABASE
    // ================================================================

    protected function submitAppointment()
    {
        $this->say('Booking your appointment...');

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

            $this->bot->userStorage()->save([
                'patient_id' => $patientId,
                'patient_name' => "{$this->fname} {$this->lname}",
            ]);

            $this->say("✅ **Appointment Confirmed, {$this->fname} {$this->lname}!**\n\n"
                . "📝 Patient: {$this->fname} {$this->lname}\n"
                . "📞 Contact: {$this->phone}\n"
                . "🎂 DOB: {$this->dob}\n"
                . "📧 Email: {$this->email}\n"
                . "🩺 Service: {$this->service}\n"
                . "👨‍⚕️ Doctor: {$this->doctor}\n"
                . "📅 Scheduled: {$this->scheduleSuggestion}\n\n"
                . "A confirmation will be sent within 24 hours.");

            $this->askPostAppointment(); // → move to Step 6
        } catch (\Exception $e) {
            $this->say("⚠️ We couldn't save your appointment right now. Please try again, or contact us directly at 0985 475 5511.");
        }
    }

    // ================================================================
    // STEP 6: POST-APPOINTMENT ACTIONS (complaint / review / done)
    // ================================================================

    protected function askPostAppointment()
    {
        $question = Question::create('✨ Thank you for scheduling with us! ✨')
            ->fallback('Please use the buttons above.')
            ->addButtons([
                Button::create('⚠️ File a Complaint')->value('complaint'),
                Button::create('⭐ Submit Review/Rating')->value('review'),
                Button::create("✅ No, I'm all set")->value('done'),
            ]);

        $this->ask($question, function (Answer $answer) {
            $patientId = $this->bot->userStorage()->find()['patient_id'] ?? null;

            if ($answer->getValue() === 'complaint') {
                $this->bot->startConversation(new ComplaintConversation($patientId));
            } elseif ($answer->getValue() === 'review') {
                $this->bot->startConversation(new ReviewConversation($patientId, "{$this->fname} {$this->lname}"));
            } else {
                $this->say('Thank you for choosing PolyClinic Lipa! Have a great day! 😊');
            }
        });
    }
}