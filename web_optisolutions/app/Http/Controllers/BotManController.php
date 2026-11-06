<?php
namespace App\Http\Controllers;

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Web\WebDriver;
use BotMan\BotMan\Cache\LaravelCache;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use App\Conversations\AppointmentConversation;
use App\Conversations\AnythingElseConversation;
use App\Conversations\ComplaintConversation;
use App\Conversations\ReviewConversation;
use App\Services\ClinicInfoService;
use App\Models\ChatbotLog;
use App\Models\ChatbotCommand;
use App\Models\Staff\Inquiry;
use App\Models\Staff\InquiryReply;
use App\Models\Staff\AppNotification;
use App\Middleware\CaptureReplyMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class BotManController extends Controller
{
    protected bool $isUnhandledInquiry = false;

    // All button values used anywhere in the bot. These should never be
    // treated as an inquiry and forwarded to Admin/Staff.
    protected const IGNORED_INQUIRY_TEXTS = [
        // Main menu
        'schedule visit',
        'general information',
        'complaint', 'submit complaint',
        'review', 'submit review/rating',
        'menu',
        'cancel',
        "no, i'm all set", "yes, i need help",
        'anything_else_yes', 'anything_else_no',

        // Schedule visit: "continue / start over / cancel" prompt
        'continue schedule visit', 'continue_schedule',
        'start over', 'restart_schedule',
        'cancel_schedule',

        // Schedule visit: confirm suggested date & time
        'yes, confirm', 'confirm_yes',
        'suggest alternative', 'confirm_no',

        // Schedule visit: "email me the transcript?" prompt
        'yes, email it to me', 'yes_email',
        'no, skip', 'no_email',

        // Post-appointment menu
        'post_schedule_visit', 'post_general_information',

        // Submit Review/Rating: star buttons
        '1', '2', '3', '4', '5',

        // Standalone inquiry: category buttons
        'billing', 'medical', 'appointment', 'general',
    ];

    public function handle(Request $request)
    {
        if ($request->isJson()) {
            $request->request->add($request->json()->all());
        }

        $incomingText = (string) $request->input('message', '');
        $conversationId = (string) $request->input('userId', '');

        // Normalize free-typed command variants before BotMan reads the request.
        $canonicalCommand = $this->canonicalizeCommand($incomingText);
        if ($canonicalCommand !== null) {
            $request->request->set('message', $canonicalCommand);
        }

        CaptureReplyMiddleware::reset();
        $this->isUnhandledInquiry = false;

        try {
            DriverManager::loadDriver(WebDriver::class);
            $botman = BotManFactory::create(config('botman'), new LaravelCache(), $request);
            $botman->middleware->sending(new CaptureReplyMiddleware());

            $botman->hears('schedule visit', function ($bot) {
                Log::info('MATCHED: schedule visit');
                // A brand-new schedule visit replaces any paused one.
                $this->clearPendingSchedule($bot);
                $bot->startConversation(new AppointmentConversation());
            });

            // Buttons shown after a paused schedule visit's inquiry is answered
            // or resolved. No conversation is waiting for these on the server
            // (the old one ended when the inquiry was forwarded), so they are
            // handled here from the state saved in the cache.
            $botman->hears(['continue_schedule', 'continue schedule visit'], function ($bot) {
                Log::info('MATCHED: continue_schedule');
                $pending = $this->takePendingSchedule($bot);
                if ($pending === null) {
                    $this->replyScheduleExpired($bot);
                    return;
                }
                $bot->reply("Welcome back! Let's continue your schedule visit.");
                $bot->startConversation(new AppointmentConversation($pending['step'], $pending['state']));
            });

            $botman->hears(['restart_schedule', 'start over'], function ($bot) {
                Log::info('MATCHED: restart_schedule');
                $this->clearPendingSchedule($bot);
                $bot->reply('Okay! Restarting your schedule visit from the beginning.');
                $bot->startConversation(new AppointmentConversation());
            });

            $botman->hears('cancel_schedule', function ($bot) {
                Log::info('MATCHED: cancel_schedule');
                $this->clearPendingSchedule($bot);
                $bot->reply('Okay, your schedule visit has been cancelled. Type "Menu" anytime to start again.');
            });

            $botman->hears('general information', function ($bot) {
                Log::info('MATCHED: general information');
                $bot->reply(ClinicInfoService::infoCardMessage());
                // A paused schedule visit gets its Continue / Start Over / Cancel
                // buttons; otherwise re-show the menu so the buttons don't disappear.
                if (!$this->offerScheduleContinuation($bot)) {
                    $this->sendGreeting($bot, false);
                }
            });

            // Long-form and short-form phrasing both need to be recognized.
            $botman->hears('complaint', function ($bot) {
                Log::info('MATCHED: complaint');
                $this->clearPendingSchedule($bot);
                $bot->startConversation(new ComplaintConversation());
            });

            $botman->hears('submit complaint', function ($bot) {
                Log::info('MATCHED: submit complaint');
                $this->clearPendingSchedule($bot);
                $bot->startConversation(new ComplaintConversation());
            });

            $botman->hears('review', function ($bot) {
                Log::info('MATCHED: review');
                $this->clearPendingSchedule($bot);
                $stored = $bot->userStorage()->find() ?: [];
                $bot->startConversation(new ReviewConversation(
                    $stored['patient_id'] ?? null,
                    $stored['patient_name'] ?? null
                ));
            });

            $reviewCommand = $botman->hears('submit review/rating', function ($bot) {
                Log::info('MATCHED: submit review/rating');
                $this->clearPendingSchedule($bot);
                $stored = $bot->userStorage()->find() ?: [];
                $bot->startConversation(new ReviewConversation(
                    $stored['patient_id'] ?? null,
                    $stored['patient_name'] ?? null
                ));
            });
            if ($request->boolean('startReviewFromPass')) {
                $reviewCommand->stopsConversation();
            }

            // "Is there anything else you need?" buttons. The widget shows
            // these itself after Admin/Staff resolves an inquiry, so no
            // conversation is waiting for them on the server -- handle
            // them here instead of letting them fall through to the
            // inquiry fallback (which caused a 500 error).
            $botman->hears(AnythingElseConversation::NO, function ($bot) {
                Log::info('MATCHED: anything_else_no');
                // Thank-you, then the 4 main options again.
                $stored = $bot->userStorage()->find() ?: [];
                $bot->startConversation(new AnythingElseConversation(
                    $stored['patient_id'] ?? null,
                    $stored['patient_name'] ?? null,
                    true,
                    AnythingElseConversation::THANK_YOU_TEXT
                ));
            });

            $botman->hears(AnythingElseConversation::YES, function ($bot) {
                Log::info('MATCHED: anything_else_yes');
                $stored = $bot->userStorage()->find() ?: [];
                $bot->startConversation(new AnythingElseConversation(
                    $stored['patient_id'] ?? null,
                    $stored['patient_name'] ?? null,
                    true
                ));
            });

            $botman->hears('menu', function ($bot) {
                Log::info('MATCHED: menu');
                $this->clearPendingSchedule($bot);
                $this->sendGreeting($bot);
            });

            $botman->fallback(function ($bot) {
                Log::info('FALLBACK - Received text: "' . $bot->getMessage()->getText() . '"');
                $this->handleFallback($bot);
            });

            $botman->listen();

            $this->logExchange($conversationId, $incomingText, CaptureReplyMiddleware::$captured, $botman);

        } catch (\Throwable $e) {
            Log::error('BotMan error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Something went wrong reaching the chatbot. Please try again.'
            ], 500);
        }
    }

    // Maps a free-typed message onto its canonical command text.
    protected function canonicalizeCommand(string $text): ?string
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = rtrim($normalized, ".!? \t\n\r");
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = preg_replace('/\s*\/\s*/', '/', $normalized);

        // Review/rating variants.
        if (preg_match('/^(submit\s+)?(review|reviews|rating|ratings|review\/rating|review\/ratings)$/', $normalized)) {
            return 'submit review/rating';
        }

        // Complaint variants.
        if (preg_match('/^(submit\s+|file\s+(a\s+)?|make\s+(a\s+)?)?(complaint|complaints|complain)$/', $normalized)) {
            return 'submit complaint';
        }

        // Schedule visit variants.
        if (preg_match('/^(schedule|book)\s+(a\s+|an\s+|my\s+|the\s+)?(visit|visits|appointment|appointments)$/', $normalized)) {
            return 'schedule visit';
        }

        return null;
    }

    protected function handleFallback($bot)
    {
        $text = trim($bot->getMessage()->getText());

        // A schedule-visit form (or its Back button) used after its conversation expired.
        // Never forward it to staff as an inquiry.
        if (str_starts_with($text, AppointmentConversation::FORM_SUBMIT_PREFIX) || in_array($text, ['back_to_details', 'back_to_visit'], true)) {
            Log::info('FALLBACK ignored (stale form submission)');
            $bot->reply('That form has expired. Please tap "Schedule Visit" to start again.');
            $this->sendGreeting($bot, false);
            return;
        }

        if (in_array(mb_strtolower($text), self::IGNORED_INQUIRY_TEXTS, true)) {
            Log::info('FALLBACK ignored (known command text): "' . $text . '"');
            return;
        }

        // Friendly acknowledgment, e.g. "ok", "thanks", "salamat".
        if (ClinicInfoService::looksLikeAcknowledgment($text)) {
            Log::info('FALLBACK answered as acknowledgment: "' . $text . '"');
            $bot->reply(ClinicInfoService::acknowledgmentReply());
            $this->offerScheduleContinuation($bot);
            return;
        }

        // Plain greeting, e.g. "Hi", "Hello", "Kumusta".
        if (ClinicInfoService::looksLikeGreeting($text)) {
            Log::info('FALLBACK answered as greeting: "' . $text . '"');
            $bot->reply(ClinicInfoService::greetingReply());
            if (!$this->offerScheduleContinuation($bot)) {
                $this->sendGreeting($bot, false);
            }
            return;
        }

        // Patient asking permission to ask, e.g. "Pwede mag tanong?".
        if (ClinicInfoService::looksLikeAskingPermission($text)) {
            Log::info('FALLBACK answered as asking-permission: "' . $text . '"');
            $bot->reply(ClinicInfoService::askingPermissionReply());
            $this->offerScheduleContinuation($bot);
            return;
        }

        // Not a question or request at all (e.g. "/", "???", "hn", "asdfgh",
        // "hahaha", a bare number). Ask what they need instead; this is
        // NEVER forwarded to Admin/Staff and never creates an inquiry.
        if (ClinicInfoService::looksLikeNonInquiry($text)) {
            Log::info('FALLBACK ignored (not an inquiry): "' . $text . '"');
            $bot->reply(ClinicInfoService::nonInquiryReply($text));
            $conversationId = (string) $bot->getMessage()->getSender();
            $hasOpenInquiry = $conversationId !== '' && Inquiry::where('conversation_id', $conversationId)
                ->where('resolved_status', '!=', 'Resolved')
                ->exists();
            // A paused schedule visit takes priority over the menu. Otherwise,
            // don't push the menu on someone mid-chat with staff.
            if (!$this->offerScheduleContinuation($bot) && !$hasOpenInquiry) {
                $this->sendGreeting($bot, false);
            }
            return;
        }

        // Try to answer directly from clinic data first.
        $infoReply = ClinicInfoService::answerForText($text);
        if ($infoReply !== null) {
            Log::info('FALLBACK answered from ClinicInfoService: "' . $text . '"');
            $bot->reply($infoReply);
            // Answered by the bot -> if a schedule visit is paused, offer
            // Continue / Start Over / Cancel right away.
            $this->offerScheduleContinuation($bot);
            return;
        }

        // Once this conversation already has an open (not-yet-resolved)
        // inquiry with Admin/Staff, every further unhandled message the
        // patient types would otherwise re-trigger this same "forwarded
        // to Admin/Staff" acknowledgment — annoying if they're typing
        // several follow-up lines in a row while waiting for a reply.
        // The message itself is still recorded into that same open
        // inquiry's thread below (via recordInquiry/recordInquiryReply),
        // so Admin/Staff still sees it — the patient just isn't shown
        // the ack bubble more than once per open inquiry.
        $conversationId = (string) $bot->getMessage()->getSender();
        $hasOpenInquiry = $conversationId !== '' && Inquiry::where('conversation_id', $conversationId)
            ->where('resolved_status', '!=', 'Resolved')
            ->exists();

        $this->isUnhandledInquiry = true;

        if (!$hasOpenInquiry) {
            $this->sendInquiryAck($bot);
        }
    }

    protected function logExchange(string $conversationId, string $userMessage, array $capturedReplies, $botman)
    {
        // Review passes are private bearer codes, not inquiry text.
        if (preg_match(\App\Services\VisitReviewService::CODE_PATTERN, \App\Services\VisitReviewService::normalizeCode($userMessage))) {
            $userMessage = '[Review transaction code entered]';
        }
        try {
            $replies = collect($capturedReplies)->filter()->implode("\n---\n");

            if ($userMessage === '' && $replies === '') {
                return;
            }

            $log = ChatbotLog::create([
                'conversation_id' => $conversationId !== '' ? $conversationId : null,
                'user_message'    => $userMessage !== '' ? $userMessage : '(no text)',
                'bot_message'     => $replies !== '' ? $replies : '(no reply)',
            ]);

            if ($this->isUnhandledInquiry) {
                $this->recordInquiry($conversationId, $userMessage, $log, $botman);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to save chatbot log: ' . $e->getMessage());
        }
    }

    protected function recordInquiry(string $conversationId, string $userMessage, ChatbotLog $log, $botman)
    {
        // patient_id is only in storage once a schedule visit was confirmed;
        // before that the inquiry stays anonymous to Admin/Staff.
        $patientId = $botman->userStorage()->find()['patient_id'] ?? null;
        $this->recordInquiryReply($conversationId, $userMessage, $log, null, null, $patientId);
    }

    protected function recordInquiryReply(
        string $conversationId,
        string $userMessage,
        ChatbotLog $log,
        ?string $attachmentPath = null,
        ?string $attachmentName = null,
        $patientId = null
    ) {
        try {
            $openInquiry = Inquiry::where('conversation_id', $conversationId)
                ->where('resolved_status', '!=', 'Resolved')
                ->orderByDesc('inquiry_id')
                ->first();

            if (!$openInquiry) {
                // Attachments have no bot/storage here: reuse the patient this
                // chat was already linked to by a confirmed schedule visit.
                if (!$patientId && $conversationId !== '') {
                    $patientId = Inquiry::where('conversation_id', $conversationId)
                        ->whereNotNull('patient_id')
                        ->value('patient_id');
                }

                $openInquiry = Inquiry::create([
                    'patient_id'      => $patientId ?: null,
                    'guest_name'      => null,
                    'log_id'          => $log->log_id,
                    'conversation_id' => $conversationId !== '' ? $conversationId : null,
                    'inquiry_type'    => 'General',
                    'resolved_status' => 'Pending',
                    'created_at'      => now(),
                ]);
            }

            InquiryReply::create([
                'inquiry_id'      => $openInquiry->inquiry_id,
                'sender'          => 'Patient',
                'message'         => $userMessage,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
            ]);

            $openInquiry->save();

            return $openInquiry;
        } catch (\Throwable $e) {
            Log::error('Failed to record inquiry: ' . $e->getMessage());
            return null;
        }
    }

    // attachment upload

    public function attachment(Request $request)
    {
        $request->validate([
            'attachment' => 'required|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:5120',
            'userId'     => 'nullable|string',
            'caption'    => 'nullable|string|max:2000',
        ]);

        $conversationId = (string) $request->input('userId', '');
        $caption = trim((string) $request->input('caption', ''));

        try {
            $file = $request->file('attachment');
            $storedPath = $file->store('chat-attachments', 'public');
            $originalName = $file->getClientOriginalName();

            $displayMessage = $caption !== '' ? $caption : '(Sent an attachment)';

            $log = ChatbotLog::create([
                'conversation_id' => $conversationId !== '' ? $conversationId : null,
                'user_message'    => $displayMessage,
                'bot_message'     => '(no reply)',
            ]);

            $this->recordInquiryReply($conversationId, $displayMessage, $log, $storedPath, $originalName);

            return response()->json([
                'success'        => true,
                'attachmentUrl'  => asset('storage/' . $storedPath),
                'attachmentName' => $originalName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to save chatbot attachment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'Something went wrong uploading the attachment. Please try again.',
            ], 500);
        }
    }

    // PAUSED SCHEDULE VISIT HELPERS

    protected function conversationIdOf($bot): string
    {
        return (string) $bot->getMessage()->getSender();
    }

    protected function clearPendingSchedule($bot): void
    {
        AppointmentConversation::clearPendingSchedule($this->conversationIdOf($bot));
    }

    // Returns the paused schedule visit (and removes it) or null if none/expired.
    protected function takePendingSchedule($bot): ?array
    {
        $conversationId = $this->conversationIdOf($bot);
        $pending = AppointmentConversation::getPendingSchedule($conversationId);
        if ($pending !== null) {
            AppointmentConversation::clearPendingSchedule($conversationId);
        }
        return $pending;
    }

    protected function replyScheduleExpired($bot): void
    {
        $bot->reply('That schedule visit has expired. Please tap "Schedule Visit" to start again.');
        $this->sendGreeting($bot, false);
    }

    /**
     * If this chat has a paused schedule visit, show Continue / Start Over /
     * Cancel -- but only once no inquiry is still waiting on Admin/Staff
     * (in that case the widget shows them when the inquiry gets resolved).
     *
     * Returns true when a schedule visit is paused (whether or not the buttons
     * were shown right now), so callers know not to push the main menu.
     */
    protected function offerScheduleContinuation($bot): bool
    {
        $conversationId = $this->conversationIdOf($bot);
        if (!AppointmentConversation::hasPendingSchedule($conversationId)) {
            return false;
        }

        $hasOpenInquiry = Inquiry::where('conversation_id', $conversationId)
            ->where('resolved_status', '!=', 'Resolved')
            ->exists();

        if (!$hasOpenInquiry) {
            $bot->reply(
                Question::create('Would you like to continue your schedule visit, start over, or cancel?')
                    ->fallback('Please choose an option from the buttons above.')
                    ->addButtons([
                        Button::create('Continue Schedule Visit')->value('continue_schedule'),
                        Button::create('Start Over')->value('restart_schedule'),
                        Button::create('Cancel')->value('cancel_schedule'),
                    ])
            );
        }

        return true;
    }

    protected function sendGreeting($bot, bool $showGreeting = true)
    {
        $question = Question::create($showGreeting ? 'Welcome to PolyClinic Lipa. How can I help you today?' : '')
            ->fallback('Please choose an option from the buttons above.')
            ->addButtons([
                Button::create('Schedule Visit')->value('schedule visit'),
                Button::create('General Information')->value('general information'),
                Button::create('Submit Review/Rating')->value('submit review/rating'),
                Button::create('Submit Complaint')->value('submit complaint'),
            ]);
        $bot->reply($question);
    }

    protected function sendInquiryAck($bot)
    {
        $bot->reply("Thanks for your message! I've forwarded it to our Admin/Staff team — they'll reply to you here shortly.");
    }
}