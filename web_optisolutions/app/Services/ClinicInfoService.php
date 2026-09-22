<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\ChatbotCommand;
use App\Models\ChatbotCommandTrigger;

// Builds chatbot replies and detects the intent of user messages.
class ClinicInfoService
{
    const INFO_CARD_PREFIX = 'INFO_CARD::';

    // KEYWORD DETECTION

    protected static array $howQuestionWords = [
        'paano', 'how',
    ];

    protected static array $scheduleActionWords = [
        'schedule', 'mag-schedule', 'magschedule', 'magpa-schedule', 'magpaschedule',
        'book', 'booking', 'magbook', 'mag-book', 'appointment', 'pagpapatingin',
        'magpatingin', 'pumunta', 'bumisita', 'visit',
    ];

    protected static array $doctorKeywords = [
        'doctor', 'doctors', 'doktor', 'specialist', 'specialists',
        'pediatrician', 'surgeon', 'obgyne', 'ob-gyne', 'ophthalmologist',
        'pulmonologist', 'oncologist', 'physician',
    ];

    // Identifies explicit markers indicating that the text refers to a doctor's name.
    protected static array $doctorReferenceWords = [
        'doc', 'dr', 'dra', 'doctor', 'doktor',
    ];

    // Used together with a detected specialty so a question about a specific doctor is recognized even without the word "doctor" in it.
    protected static array $whoQuestionWords = [
        'sino', 'who',
    ];

    // Used together with a matched doctor name to recognize questions about that doctor's specialty/assignment.
    protected static array $doctorInfoQuestionWords = [
        'specialty', 'specialization', 'specialize', 'assigned', 'naka-assign',
        'nakaassign', 'department', 'sino', 'ano', 'what', 'saan',
    ];

    // Sub-intent: asking specifically about a doctor's clinic room where to find them physically.
    protected static array $doctorRoomWords = [
        'room', 'kwarto', 'clinic room', 'located', 'lokasyon niya',
        'saan siya makikita', 'saan makikita', 'nasaan siya',
    ];

    // Sub-intent: asking specifically about a doctor's years of experience.
    protected static array $doctorExperienceWords = [
        'experience', 'ilang taon', 'ilang years', 'how many years',
        'years of experience', 'karanasan',
    ];

    protected static array $serviceKeywords = [
        'service', 'services', 'serbisyo', 'offer', 'offers',
    ];

    protected static array $locationKeywords = [
        'address', 'location', 'located', 'where', 'saan', 'nasaan',
        'lokasyon', 'direction', 'directions', 'pupuntahan', 'papunta',
    ];

    protected static array $hoursKeywords = [
        'hours', 'oras', 'open', 'bukas', 'sarado', 'closed',
        'operating hours', 'schedule niyo', 'schedule ninyo', 'anong oras',
    ];

    protected static array $contactKeywords = [
        'contact', 'number', 'phone', 'tawag', 'call', 'telepono',
    ];

    // Words that signal the patient is complaining/venting rather than
    // asking for information — even when their message also happens to
    // contain an info keyword like "services" or "doctor". e.g.
    // "Bakit ang tagal ng services nyo?" contains "services" but is a
    // complaint about slow service, not a request for the services list.
    protected static array $complaintSignalWords = [
        // Tagalog
        'tagal', 'matagal', 'katagalan', 'ang bagal', 'mabagal', 'bagal',
        'reklamo', 'nagrereklamo', 'problema', 'ayaw', 'nakakainis',
        'nakakadismaya', 'pangit', 'masama', 'nagagalit', 'galit',
        'hindi maganda', 'di maganda', 'sobrang tagal', 'sobrang bagal',
        'walang pakundangan', 'bastos', 'walang kwenta', 'sayang',
        // English
        'slow', 'delay', 'delayed', 'delays', 'late', 'waiting for hours',
        'waited for hours', 'poor service', 'bad service', 'terrible',
        'awful', 'horrible', 'disappointed', 'disappointing', 'complaint',
        'complain', 'complaining', 'rude', 'unacceptable', 'worst',
        'never again', 'waste of time', 'wasted my time',
    ];

    // Maps common specialty terms to the corresponding doctor specialty.
    protected static array $specialtyAliases = [
        'Pediatrics' => [
            'pediatrics', 'pedia', 'pediatric', 'pediatrician',
            'bata', 'mga bata', 'child', 'children', 'baby', 'infant',
        ],
        'OB-Gyne' => [
            'ob-gyne', 'obgyne', 'ob gyne', 'ob-gynecology', 'obstetrics',
            'gynecology', 'gyne', 'pregnant', 'pregnancy', 'prenatal', 'buntis',
        ],
        'Surgery' => [
            'surgery', 'surgical', 'surgeon', 'operahan', 'opera', 'operation',
        ],
        'IM-Pulmonology' => [
            'pulmonology', 'pulmonologist', 'baga', 'lungs', 'lung',
            'hika', 'asthma', 'respiratory',
        ],
        'General / Adult Medicine' => [
            'general medicine', 'adult medicine', 'general checkup',
            'checkup', 'general consult', 'general consultation',
        ],
        'Internal Medicine' => [
            'internal medicine',
        ],
        'Medical Oncology' => [
            'oncology', 'oncologist', 'cancer', 'kanser', 'tumor',
        ],
        'Ophthalmology / General Medicine' => [
            'ophthalmology', 'ophthalmologist', 'mata', 'eye', 'eyes',
        ],
    ];

    // Plain-English explanations for each specialty, used to answer
    // "What is X? / Ano ang X?" style questions automatically instead
    // of forwarding them to staff.
    protected static array $specialtyDefinitions = [
        'Pediatrics' => 'Pediatrics is the branch of medicine that focuses on the health, growth, and medical care of infants, children, and adolescents.',
        'OB-Gyne' => "OB-Gyne (Obstetrics and Gynecology) is the branch of medicine that cares for women's reproductive health, including pregnancy, childbirth, and conditions of the female reproductive system.",
        'Surgery' => 'Surgery is the branch of medicine that treats diseases, injuries, or deformities through operative procedures performed by a surgeon.',
        'IM-Pulmonology' => 'Pulmonology is the branch of medicine that focuses on diagnosing and treating diseases of the respiratory system, including the lungs and airways.',
        'General / Adult Medicine' => 'General/Adult Medicine covers routine checkups and the diagnosis and treatment of common illnesses and health concerns in adults.',
        'Internal Medicine' => 'Internal Medicine is the branch of medicine focused on the prevention, diagnosis, and treatment of diseases in adults.',
        'Medical Oncology' => 'Medical Oncology is the branch of medicine that focuses on diagnosing and treating cancer, mainly through medication-based therapies.',
        'Ophthalmology / General Medicine' => 'Ophthalmology is the branch of medicine that deals with the diagnosis and treatment of eye diseases and vision conditions.',
    ];

    // Phrases that signal the patient is asking for a definition/explanation
    // (English or Tagalog) rather than trying to book or list doctors.
    protected static array $definitionQuestionMarkers = [
        'what is', 'what are', "what's", 'whats',
        'ano ang', 'ano yung', 'ano po ang', 'ano ba ang',
        'ibig sabihin', 'kahulugan', 'define', 'meaning of', 'explain',
    ];

    // Determines whether the text is asking "what is X" / "ano ang X".
    public static function looksLikeDefinitionQuestion(string $text): bool
    {
        $lower = mb_strtolower($text);
        foreach (self::$definitionQuestionMarkers as $marker) {
            if (str_contains($lower, $marker)) {
                return true;
            }
        }
        return false;
    }

    // Returns the plain-English definition reply for a specialty, plus a
    // pointer to booking/general info.
    public static function specialtyDefinitionReply(string $specialty): string
    {
        $definition = self::$specialtyDefinitions[$specialty]
            ?? "{$specialty} is one of our medical specialties here at PolyClinic Lipa.";

        return "{$definition}\n\nType \"Schedule Visit\" if you'd like to book a {$specialty} consultation, or \"General Information\" to see our full list of services.";
    }

    // GREETINGS / CONVERSATIONAL SMALL TALK

    protected static array $greetingPhrases = [
        'hi', 'hello', 'hey', 'yo', 'kumusta', 'kamusta', 'kumusta po', 'kamusta po',
        'good morning', 'good afternoon', 'good evening', 'good day',
        'magandang umaga', 'magandang hapon', 'magandang gabi', 'magandang araw',
    ];

    // Phrases where the patient is simply asking permission to ask a
    // question ("Pwede mag tanong?", "Can I ask?") rather than asking an
    // actual inquiry yet — the bot should invite the question, not forward
    // this to Admin/Staff.
    protected static array $askingPermissionPhrases = [
        'pwede mag tanong', 'pwede po mag tanong', 'pwede ba mag tanong',
        'puwede mag tanong', 'puwede po mag tanong', 'puwede ba mag tanong',
        'pwede magtanong', 'puwede magtanong', 'pwede ba akong magtanong',
        'pwede mag ask', 'puwede mag ask', 'pwede po mag ask',
        'can i ask', 'may i ask', 'can i ask a question', 'could i ask',
    ];

    protected static function normalizeConversational(string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        return trim(preg_replace('/[.!?,]+$/u', '', $normalized));
    }

    // Determines whether the text is just a greeting ("Hi", "Hello",
    // "Kumusta") rather than an actual inquiry.
    public static function looksLikeGreeting(string $text): bool
    {
        $normalized = self::normalizeConversational($text);
        if ($normalized === '') {
            return false;
        }

        foreach (self::$greetingPhrases as $phrase) {
            if ($normalized === $phrase || str_starts_with($normalized, $phrase . ' ')) {
                return true;
            }
        }
        return false;
    }

    // Determines whether the text is the patient asking permission to ask
    // a question, without an actual question in it yet.
    public static function looksLikeAskingPermission(string $text): bool
    {
        $normalized = self::normalizeConversational($text);
        if ($normalized === '') {
            return false;
        }

        foreach (self::$askingPermissionPhrases as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return true;
            }
        }
        return false;
    }

    public static function greetingReply(): string
    {
        // Deliberately different from the initial widget-open welcome
        // text ("Hello! Welcome to PolyClinic Lipa. How can I help you
        // today?") so a patient typing "Hi" as their first message
        // doesn't see the exact same greeting sentence twice in a row.
        return 'Hi there! 😊 What can I help you with today?';
    }

    public static function askingPermissionReply(): string
    {
        return "Of course! Go ahead and type your question — I'll do my best to help. 😊";
    }

    // Detects complaint/negative-sentiment tone so a message that merely
    // mentions an info keyword ("services", "doctor", "hours", etc.)
    // alongside a complaint word is routed to staff instead of being
    // answered with the matching info card. This is the fast, free
    // first pass — checked before the ML sentiment call below.
    public static function looksLikeComplaint(string $text): bool
    {
        $lower = mb_strtolower($text);
        foreach (self::$complaintSignalWords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }
        return false;
    }

    // Second-pass classifier for messages that matched an info keyword
    // but weren't caught by the static complaint word list — e.g.
    // phrasing that's negative in tone without using any of the words
    // above. Only called for keyword-matched text (see
    // looksLikeInfoRequest()) so ordinary "does not match anything"
    // messages skip the extra API round-trip and go straight to staff
    // as before. Fails safe: if the ML service is unreachable or
    // returns nothing, we don't block a legitimate info answer over it.
    public static function isNegativeSentiment(string $text): bool
    {
        $result = app(SentimentAnalysisService::class)->analyze($text);

        if (!$result) {
            return false;
        }

        $label = $result['sentiment_label'] ?? null;
        $confidence = (float) ($result['confidence_score'] ?? 1);

        return $label === 'Negative' && $confidence >= 0.5;
    }

    // ACKNOWLEDGMENT / GRATITUDE DETECTION

    protected static array $acknowledgmentExactPhrases = [
        'ok', 'okay', 'oks', 'okie', 'okey', 'alright', 'aight', 'sure',
        'noted', 'got it', 'gets', 'gets ko', 'understood', 'i see', 'ic',
        'sige', 'sige po', 'ayos', 'ayos na', 'okay lang', 'ok lang',
        'ok po', 'okay po', 'cool', 'nice', 'great', 'perfect',
        'yes', 'yep', 'yeah', 'oo', 'opo', 'yup',
    ];

    protected static array $acknowledgmentSubstringPhrases = [
        'thank you', 'thanks', 'thankyou', 'thank u', 'tysm', 'ty so much',
        'salamat', 'maraming salamat', 'appreciate it', 'appreciated',
    ];

    protected static array $acknowledgmentReplies = [
        "You're welcome! 😊 Is there anything else I can help you with?",
        "Glad I could help! Let me know if you need anything else.",
        "No problem at all! Feel free to ask if you have more questions.",
        "You're welcome! Just let me know if you have any other questions.",
        'Happy to help! 😊 Just type "Menu" anytime to see what I can do.',
    ];

    // Determines whether the given text is a simple acknowledgment or thank-you message rather than an actual question or concern.
    public static function looksLikeAcknowledgment(string $text): bool
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = trim(preg_replace('/[.!?,]+$/u', '', $normalized));

        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, self::$acknowledgmentExactPhrases, true)) {
            return true;
        }

        foreach (self::$acknowledgmentSubstringPhrases as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return true;
            }
        }

        return false;
    }

    // Returns a random, friendly acknowledgment reply.
    public static function acknowledgmentReply(): string
    {
        return self::$acknowledgmentReplies[array_rand(self::$acknowledgmentReplies)];
    }

    // Splits a doctor's full name into lowercase name tokens.
    protected static function normalizeDoctorTokens(string $doctorName): array
    {
        $name = str_ireplace('Dr.', '', $doctorName);
        $name = str_replace('-', ' ', $name);
        $name = trim($name);

        $tokens = preg_split('/\s+/', mb_strtolower($name));

        return array_values(array_filter($tokens, fn ($t) => mb_strlen($t) > 1));
    }

   // Finds available doctors mentioned in the given text.
    public static function matchDoctorsInText(string $text): \Illuminate\Support\Collection
    {
        $lower = mb_strtolower($text);
        $words = preg_split('/[^\p{L}\p{N}]+/u', $lower, -1, PREG_SPLIT_NO_EMPTY);
        $wordSet = array_flip($words);

        $doctors = DB::table('doctors')->where('available', 1)->get();

        $scored = $doctors->map(function ($doctor) use ($wordSet) {
            $tokens = self::normalizeDoctorTokens($doctor->doctor_name);
            $matchCount = 0;
            foreach ($tokens as $token) {
                if (isset($wordSet[$token])) {
                    $matchCount++;
                }
            }
            $doctor->match_count = $matchCount;
            return $doctor;
        });

        $withMatches = $scored->filter(fn ($doctor) => $doctor->match_count >= 1);

        if ($withMatches->isEmpty()) {
            return collect();
        }

        $topScore = $withMatches->max('match_count');
        $topMatches = $withMatches->filter(fn ($doctor) => $doctor->match_count === $topScore)->values();

        // Two or more matched tokens (e.g. first name + surname) is a strong, unambiguous signal on its own.
        if ($topScore >= 2) {
            return $topMatches;
        }

        if ($topMatches->count() === 1 && self::mentionsDoctorReference($lower)) {
            return $topMatches;
        }

        return collect();
    }

    // Checks whether the text contains a standalone doctor marker.
    protected static function mentionsDoctorReference(string $lowerText): bool
    {
        foreach (self::$doctorReferenceWords as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/u', $lowerText)) {
                return true;
            }
        }

        return false;
    }

    // Determines whether the text is asking about a specific doctor's specialty.
    protected static function looksLikeDoctorInfoQuestion(string $text): bool
    {
        $lower = mb_strtolower($text);

        foreach (self::$doctorInfoQuestionWords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }

        return false;
    }

    // Resolves free text to the matching doctor specialty, if any.
    public static function detectSpecialty(string $text, bool $includeDbFallback = true): ?string
    {
        $lower = mb_strtolower($text);

        foreach (self::$specialtyAliases as $specialty => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($lower, $alias)) {
                    return $specialty;
                }
            }
        }

        if (!$includeDbFallback) {
            return null;
        }

        $distinctSpecialties = DB::table('doctors')->distinct()->pluck('specialty');
        foreach ($distinctSpecialties as $specialty) {
            if ($specialty && str_contains($lower, mb_strtolower($specialty))) {
                return $specialty;
            }
        }

        return null;
    }

    // Matches free text against the service_conditions table (condition
    // name → service), e.g. "asthma" → IM-Pulmonology, "hernia repair"
    // → Surgery. This is driven entirely by the DB table your admin
    // panel manages, so any condition an admin adds there becomes
    // something the bot can answer for immediately — no code change
    // needed. Longer/more specific condition names are checked first
    // so e.g. "cataract treatment" wins over a shorter partial overlap.
    public static function matchServiceByCondition(string $text): ?object
    {
        $lower = mb_strtolower($text);

        $rows = DB::table('service_conditions')
            ->join('services', 'service_conditions.service_id', '=', 'services.service_id')
            ->where('services.available', 1)
            ->select('service_conditions.condition_name', 'services.*')
            ->get()
            ->sortByDesc(fn ($row) => mb_strlen($row->condition_name));

        foreach ($rows as $row) {
            if (str_contains($lower, mb_strtolower($row->condition_name))) {
                return $row;
            }
        }

        return null;
    }

    // Looks up an admin-managed quick-reply command whose trigger matches
    // the full normalized text (e.g. "pharmacy", "clinic hours"). These are
    // exact-phrase matches, not the fuzzy keyword checks below — an admin
    // can add/edit these from the Chatbot Commands panel with no deploy.
    //(mika)
    public static function matchCustomCommand(string $text): ?ChatbotCommand
    {
        $normalized = ChatbotCommand::normalizeTrigger($text);

        if ($normalized === '') {
            return null;
        }

        $trigger = ChatbotCommandTrigger::where('trigger_value', $normalized)
            ->whereHas('command', fn ($q) => $q->active())
            ->with('command')
            ->first();

        return $trigger?->command;
    }

    

    // Determines which information category the given text is asking about.
    
    public static function detectInfoIntent(string $text): ?string
    {
        $lower = mb_strtolower($text);

        // Exact admin-defined trigger match takes priority over the fuzzy
        // keyword checks below — it's a specific phrase an admin chose on
        // purpose, not a loose str_contains() guess.
        //(mika)
        if (self::matchCustomCommand($text) !== null) {
            return 'custom_command';
        }    $lower = mb_strtolower($text);

        $hasHowWord = false;
        foreach (self::$howQuestionWords as $kw) {
            if (str_contains($lower, $kw)) { $hasHowWord = true; break; }
        }
        if ($hasHowWord) {
            foreach (self::$scheduleActionWords as $kw) {
                if (str_contains($lower, $kw)) return 'how_to_schedule';
            }
        }

        // "What is X? / Ano ang X?" style definition questions about a
        // specialty (e.g. "What is pediatrics?", "Ano ang OB-Gyne?") are
        // answered directly, before falling through to the doctor/service
        // keyword checks below.
        if (self::looksLikeDefinitionQuestion($text)) {
            $specialty = self::detectSpecialty($text, false);
            if ($specialty !== null && isset(self::$specialtyDefinitions[$specialty])) {
                return 'specialty_definition';
            }
        }

       // Checks for specific doctor inquiries before handling more general requests.

        if (self::looksLikeDoctorInfoQuestion($text)) {
            $doctorMatches = self::matchDoctorsInText($text);
            if ($doctorMatches->isNotEmpty()) {
                return 'doctor_specialty';
            }
        }

        $hasDoctorWord = false;
        foreach (self::$doctorKeywords as $kw) {
            if (str_contains($lower, $kw)) { $hasDoctorWord = true; break; }
        }

        $hasWhoWord = false;
        foreach (self::$whoQuestionWords as $kw) {
            if (str_contains($lower, $kw)) { $hasWhoWord = true; break; }
        }

        // Only run the specialty lookup when the message actually looks like a doctor question.
        if ($hasDoctorWord || $hasWhoWord) {
            $specialty = self::detectSpecialty($text, true);
            if ($specialty !== null) {
                return 'doctors_for_specialty';
            }
        }

        if ($hasDoctorWord) {
            return 'doctors';
        }

        // Condition/symptom lookup driven by the service_conditions
        // table — catches things like "Do you treat asthma?" or "May
        // sakit ako sa puso" that don't contain any of the generic
        // "service" keywords below, but are answerable straight from
        // data your admin panel already manages.
        if (self::matchServiceByCondition($text) !== null) {
            return 'service_for_condition';
        }

        foreach (self::$serviceKeywords as $kw) {
            if (str_contains($lower, $kw)) return 'services';
        }
        foreach (array_merge(self::$locationKeywords, self::$hoursKeywords, self::$contactKeywords) as $kw) {
            if (str_contains($lower, $kw)) return 'clinic';
        }

        return null;
    }

    // Central "info request vs. inquiry" classifier used by every step
    // in the bot (main menu, name/phone/dob/email, service/doctor
    // selection, Review, Complaint). Anything that returns false here
    // falls through to routeToInquiry() and is auto-forwarded to staff.
    public static function looksLikeInfoRequest(string $text): bool
    {
        // No matching topic at all (services/doctors/hours/location/
        // contact/how-to-schedule) — not an info request, regardless
        // of tone. Goes straight to staff.
        if (self::detectInfoIntent($text) === null) {
            return false;
        }

        // A topic keyword matched, but the message reads like a
        // complaint riding on that same word (e.g. "ang tagal ng
        // services nyo?" mentions "services" but isn't asking what
        // services you offer). Cheap keyword check first...
        if (self::looksLikeComplaint($text)) {
            return false;
        }

        // ...then the ML sentiment check for negative-toned phrasing
        // the static word list doesn't catch. Only runs for
        // keyword-matched text, so plainly off-topic messages skip
        // the extra API round-trip.
        if (self::isNegativeSentiment($text)) {
            return false;
        }

        return true;
    }

    // Returns the ready-to-send reply for free text, or null if it isn't an info request.

    public static function answerForText(string $text): ?string
    {
        $intent = self::detectInfoIntent($text);
        //mika: check for custom command first, then specialty definition, then doctor specialty, then doctors for specialty, then service for condition, then the rest
        if ($intent === 'custom_command') {
            return self::matchCustomCommand($text)?->reply_text;
        }
        if ($intent === 'specialty_definition') {
            $specialty = self::detectSpecialty($text, false);
            return self::specialtyDefinitionReply($specialty ?? '');
        }

        if ($intent === 'doctor_specialty') {
            return self::doctorSpecialtyReply($text, self::matchDoctorsInText($text));
        }

        if ($intent === 'doctors_for_specialty') {
            $specialty = self::detectSpecialty($text, true);
            return $specialty !== null
                ? self::doctorsForSpecialtyMessage($specialty)
                : self::doctorsListMessage();
        }

        if ($intent === 'service_for_condition') {
            $match = self::matchServiceByCondition($text);
            return $match !== null
                ? self::serviceForConditionMessage($match)
                : self::servicesListMessage();
        }

        return match ($intent) {
            'how_to_schedule' => self::howToScheduleMessage(),
            'doctors'         => self::doctorsListMessage(),
            'services'        => self::servicesListMessage(),
            'clinic'          => self::infoCardMessage(),
            default           => null,
        };
    }

    // Returns a step-by-step walkthrough of how to schedule a visit.

    public static function howToScheduleMessage(): string
    {
        $card = [
            'title' => 'How to Schedule a Visit',
            'sections' => [
                [
                    'rows' => [
                        [
                            'label' => 'Steps',
                            'type'  => 'multiline',
                            'value' => [
                                '1. Type "Schedule Visit" to begin.',
                                '2. Enter your full name (first and last).',
                                '3. Enter your contact number.',
                                '4. Enter your date of birth.',
                                '5. Enter your email address.',
                                '6. Choose the service you need (e.g. Pediatrics, OB-Gyne, Surgery).',
                                '7. Choose your preferred doctor/specialist.',
                                '8. Confirm the suggested schedule, or ask for an alternative.',
                                "9. We'll save your visit and send you a confirmation card.",
                                '10. Optional: get the full conversation emailed to you.',
                            ],
                        ],
                    ],
                ],
            ],
            'footer' => 'Type "Schedule Visit" anytime to start booking now.',
        ];

        return self::INFO_CARD_PREFIX . json_encode($card);
    }

    // Returns the clinic's general information (name, address, contact details, operating hours, and social links).
    public static function infoCardMessage(): string
    {
        $info = DB::table('clinic_info')->first();

        if (!$info) {
            return "⚠️ Sorry, we couldn't load our clinic information right now. Please try again later.";
        }

        $data = (array) $info;
        $sections = [];

        $identityRows = [];
        $identityMap = [
            'clinic_name' => 'Clinic Name',
            'address'     => 'Address',
            'email'       => 'Email',
            'contact_no'  => 'Contact No',
        ];
        foreach ($identityMap as $key => $label) {
            if (!empty($data[$key])) {
                $identityRows[] = ['label' => $label, 'value' => $data[$key], 'type' => 'text'];
            }
        }
        if (!empty($identityRows)) {
            $sections[] = ['rows' => $identityRows];
        }

        if (!empty($data['operating_hours'])) {
            $lines = array_map('trim', explode('|', $data['operating_hours']));
            $sections[] = [
                'rows' => [
                    ['label' => 'Operating Hours', 'value' => $lines, 'type' => 'multiline'],
                ],
            ];
        }

        if (!empty($data['facebook_link'])) {
            $sections[] = [
                'rows' => [
                    ['label' => 'Facebook Link', 'value' => $data['facebook_link'], 'type' => 'link'],
                ],
            ];
        }

        $card = [
            'title' => 'PolyClinic Lipa - Clinic Information',
            'sections' => $sections,
        ];

        return self::INFO_CARD_PREFIX . json_encode($card);
    }

    // Returns the list of available doctors, grouped by specialty.
    public static function doctorsListMessage(): string
    {
        $doctors = DB::table('doctors')
            ->where('available', 1)
            ->orderBy('specialty')
            ->orderBy('doctor_name')
            ->get();

        if ($doctors->isEmpty()) {
            return "⚠️ Sorry, we couldn't load our list of doctors right now. Please try again later.";
        }

        $sections = [];
        foreach ($doctors->groupBy('specialty') as $specialty => $group) {
            $sections[] = [
                'rows' => [
                    ['label' => $specialty, 'value' => $group->pluck('doctor_name')->all(), 'type' => 'multiline'],
                ],
            ];
        }

        $card = [
            'title' => 'PolyClinic Lipa - Our Doctors',
            'sections' => $sections,
            'footer' => 'Type "Schedule Visit" to book with any of our specialists.',
        ];

        return self::INFO_CARD_PREFIX . json_encode($card);
    }

    // Determines which specific doctor information is being asked for (room, experience, or specialty/department).
    protected static function detectDoctorInfoSubIntent(string $text): string
    {
        $lower = mb_strtolower($text);

        foreach (self::$doctorRoomWords as $kw) {
            if (str_contains($lower, $kw)) {
                return 'room';
            }
        }

        foreach (self::$doctorExperienceWords as $kw) {
            if (str_contains($lower, $kw)) {
                return 'experience';
            }
        }

        return 'specialty';
    }

    // Returns a conversational (plain text, not a card) reply to a question about a specific doctor. Handles an unambiguous match, an ambiguous match (e.g. two doctors share a surname), and the edge case of no match. The reply is scoped to whichever detail was actually asked about (specialty/department, clinic room, or years of experience) rather than the doctor's full profile.
     
    public static function doctorSpecialtyReply(string $text, \Illuminate\Support\Collection $doctors): string
    {
        if ($doctors->isEmpty()) {
            return "I couldn't quite tell which doctor you mean. Could you give me the doctor's full name?";
        }

        if ($doctors->count() > 1) {
            $names = $doctors->pluck('doctor_name')->implode('", "');
            return "We have a few doctors matching that name: \"{$names}\". Which one are you asking about? Please provide the full name.";
        }

        $doctor = $doctors->first();
        $subIntent = self::detectDoctorInfoSubIntent($text);

        if ($subIntent === 'room') {
            return !empty($doctor->clinic_room)
                ? "You can find {$doctor->doctor_name} at {$doctor->clinic_room}."
                : "We don't have a clinic room listed for {$doctor->doctor_name} yet. Please contact the clinic directly.";
        }

        if ($subIntent === 'experience') {
            return !empty($doctor->years_experience)
                ? "{$doctor->doctor_name} has {$doctor->years_experience} years of experience in {$doctor->specialty}."
                : "We don't have experience details listed for {$doctor->doctor_name} yet.";
        }

        // Default: specialty/department question.
        return "{$doctor->doctor_name} is under {$doctor->specialty}.";
    }

    public static function doctorsForSpecialtyMessage(string $specialty): string
    {
        $doctors = DB::table('doctors')
            ->where('available', 1)
            ->where('specialty', $specialty)
            ->orderBy('doctor_name')
            ->get();

        if ($doctors->isEmpty()) {
            $card = [
                'title' => "PolyClinic Lipa - {$specialty} Doctors",
                'sections' => [
                    [
                        'rows' => [
                            [
                                'label' => 'Doctors',
                                'type'  => 'multiline',
                                'value' => ["We currently have no available {$specialty} doctor. Please check back later or contact the clinic directly."],
                            ],
                        ],
                    ],
                ],
                'footer' => 'Type "General Information" to see our full list of doctors.',
            ];

            return self::INFO_CARD_PREFIX . json_encode($card);
        }

        $rows = [];
        foreach ($doctors as $doc) {
            $lines = [];

            if (!empty($doc->years_experience)) {
                $lines[] = $doc->years_experience . ' years of experience';
            }
            if (!empty($doc->education)) {
                $lines[] = $doc->education;
            }
            if (!empty($doc->license)) {
                $lines[] = $doc->license;
            }
            if (!empty($doc->clinic_room)) {
                $lines[] = 'Clinic Room: ' . $doc->clinic_room;
            }

            $rows[] = [
                'label' => $doc->doctor_name,
                'type'  => 'multiline',
                'value' => $lines ?: ['Available for consultation'],
            ];
        }

        $card = [
            'title' => "PolyClinic Lipa - {$specialty} Doctors",
            'sections' => [['rows' => $rows]],
            'footer' => 'Type "Schedule Visit" to book with any of these doctors.',
        ];

        return self::INFO_CARD_PREFIX . json_encode($card);
    }

    public static function servicesListMessage(): string
    {
        $services = DB::table('services')
            ->where('available', 1)
            ->orderBy('title')
            ->get();

        if ($services->isEmpty()) {
            return "⚠️ Sorry, we couldn't load our list of services right now. Please try again later.";
        }

        $rows = [];
        foreach ($services as $svc) {
            $rows[] = [
                'label' => $svc->title,
                'value' => $svc->description ?: 'Available at PolyClinic Lipa',
                'type'  => 'text',
            ];
        }

        $card = [
            'title' => 'PolyClinic Lipa - Our Services',
            'sections' => [['rows' => $rows]],
            'footer' => 'Type "Schedule Visit" to book any of these services.',
        ];

        return self::INFO_CARD_PREFIX . json_encode($card);
    }

    // Builds the reply for a matched service_conditions row — tells the patient which department handles their condition, plus the room and schedule, straight from the services table.
    public static function serviceForConditionMessage(object $match): string
    {
        $rows = [
            ['label' => 'Department', 'value' => $match->title, 'type' => 'text'],
        ];

        if (!empty($match->description)) {
            $rows[] = ['label' => 'About', 'value' => $match->description, 'type' => 'text'];
        }

        $rows[] = ['label' => 'Room', 'value' => $match->room ?: 'Please ask our staff', 'type' => 'text'];
        $rows[] = ['label' => 'Schedule', 'value' => $match->schedule ?: 'Please ask our staff for availability', 'type' => 'text'];

        $card = [
            'title' => "PolyClinic Lipa - {$match->condition_name}",
            'sections' => [['rows' => $rows]],
            'footer' => 'Type "Schedule Visit" to book with this department.',
        ];

        return self::INFO_CARD_PREFIX . json_encode($card);
    }
}