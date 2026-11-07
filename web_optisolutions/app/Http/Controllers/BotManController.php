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
use App\Conversations\InquiryConversation;
use App\Services\InquiryThread;
use App\Services\ScheduleVisitHold;
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

    // Set when a message was already logged/recorded while it was being handled
    // (the schedule visit hold needs the inquiry to exist before the reply goes
    // out), so logExchange() must not log it a second time.
    protected bool $skipExchangeLog = false;

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

        // Hidden message the widget sends after an attachment upload while the
        // schedule visit form is open (puts the form on hold).
        'hold_schedule_for_inquiry',

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

        // A schedule visit that is on hold (paused for an inquiry) for this chat.
        $onHold = ScheduleVisitHold::has($conversationId);

        // Normalize free-typed command variants before BotMan reads the request.
        $canonicalCommand = $this->canonicalizeCommand($incomingText, $onHold);
        if ($canonicalCommand !== null) {
            $request->request->set('message', $canonicalCommand);
        }

        CaptureReplyMiddleware::reset();
        $this->isUnhandledInquiry = false;
        $this->skipExchangeLog = false;

        try {
            DriverManager::loadDriver(WebDriver::class);
            $botman = BotManFactory::create(config('botman'), new LaravelCache(), $request);
            $botman->middleware->sending(new CaptureReplyMiddleware());

            // Continue / Start Over / Cancel for a schedule visit that is on hold.
            // While a visit is on hold these win over any conversation still
            // waiting on the server, so a click is never swallowed.
            foreach ([
                ScheduleVisitHold::CONTINUE => fn ($bot) => $this->continueHeldSchedule($bot),
                ScheduleVisitHold::RESTART  => fn ($bot) => $this->restartHeldSchedule($bot),
                ScheduleVisitHold::CANCEL   => fn ($bot) => $this->cancelHeldSchedule($bot),
            ] as $command => $callback) {
                $hears = $botman->hears($command, $callback);
                if ($onHold) {
                    $hears->stopsConversation();
                }
            }

            // Sent by the widget after an attachment upload while a form is open.
            // (When the form's conversation is still active it handles this itself;
            // this only covers a visit that is already on hold.)
            $botman->hears(ScheduleVisitHold::FOR_INQUIRY_TOKEN, function ($bot) {
                $this->holdForUploadedAttachment($bot);
            });

            $botman->hears('schedule visit', function ($bot) {
                Log::info('MATCHED: schedule visit');
                // A new schedule visit replaces one that was on hold
                // (AppointmentConversation::run() clears the hold).
                $bot->startConversation(new AppointmentConversation());
            });

            $botman->hears('general information', function ($bot) {
                Log::info('MATCHED: general information');
                $bot->reply(ClinicInfoService::infoCardMessage());
                // Re-show the menu so the buttons don't disappear (or, if a
                // schedule visit is on hold, the Continue / Start Over / Cancel buttons).
                $this->afterBotAnswer($bot, true);
            });

            // Long-form and short-form phrasing both need to be recognized.
            $botman->hears('complaint', function ($bot) {
                Log::info('MATCHED: complaint');
                $this->dropHeldSchedule($bot);
                $bot->startConversation(new ComplaintConversation());
            });

            $botman->hears('submit complaint', function ($bot) {
                Log::info('MATCHED: submit complaint');
                $this->dropHeldSchedule($bot);
                $bot->startConversation(new ComplaintConversation());
            });

            $botman->hears('review', function ($bot) {
                Log::info('MATCHED: review');
                $this->dropHeldSchedule($bot);
                $stored = $bot->userStorage()->find() ?: [];
                $bot->startConversation(new ReviewConversation(
                    $stored['patient_id'] ?? null,
                    $stored['patient_name'] ?? null
                ));
            });

            $reviewCommand = $botman->hears('submit review/rating', function ($bot) {
                Log::info('MATCHED: submit review/rating');
                $this->dropHeldSchedule($bot);
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
                if ($this->dropHeldSchedule($bot)) {
                    $bot->reply('Okay, cancelling this schedule visit.');
                }
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
    protected function canonicalizeCommand(string $text, bool $onHold = false): ?string
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = rtrim($normalized, ".!? \t\n\r");
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = preg_replace('/\s*\/\s*/', '/', $normalized);

        // While a schedule visit is on hold, typing the choice works like the buttons.
        if ($onHold) {
            if (preg_match('/^(continue|resume)( (my |the )?(schedule visit|schedule|visit|booking))?$/', $normalized)) {
                return ScheduleVisitHold::CONTINUE;
            }
            if (preg_match('/^(start over|restart|start again)$/', $normalized)) {
                return ScheduleVisitHold::RESTART;
            }
            if (preg_match('/^cancel( (my |the )?(schedule visit|schedule|visit|booking))?$/', $normalized)) {
                return ScheduleVisitHold::CANCEL;
            }
        }

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
        $conversationId = (string) $bot->getMessage()->getSender();
        $onHold = $conversationId !== '' && ScheduleVisitHold::has($conversationId);

        // A schedule-visit form (or its Back button) used after its conversation expired.
        // Never forward it to staff as an inquiry.
        if (str_starts_with($text, AppointmentConversation::FORM_SUBMIT_PREFIX) || in_array($text, ['back_to_details', 'back_to_visit'], true)) {
            Log::info('FALLBACK ignored (stale form submission)');
            if ($onHold) {
                // The form is locked in the widget; this can only be a stale tab.
                $bot->reply('Your schedule visit is on hold right now.');
                $this->afterBotAnswer($bot, false);
                return;
            }
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
            $reply = ClinicInfoService::acknowledgmentReply();
            $bot->reply($reply);
            $this->recordBotAnswer($conversationId, $text, $reply);
            $this->afterBotAnswer($bot, false);
            $this->sendMenuHint($bot, $onHold);
            return;
        }

        // Plain greeting, e.g. "Hi", "Hello", "Kumusta".
        if (ClinicInfoService::looksLikeGreeting($text)) {
            Log::info('FALLBACK answered as greeting: "' . $text . '"');
            $reply = ClinicInfoService::greetingReply();
            $bot->reply($reply);
            $this->recordBotAnswer($conversationId, $text, $reply);
            $this->afterBotAnswer($bot, true);
            return;
        }

        // Patient asking permission to ask, e.g. "Pwede mag tanong?".
        if (ClinicInfoService::looksLikeAskingPermission($text)) {
            Log::info('FALLBACK answered as asking-permission: "' . $text . '"');
            $reply = ClinicInfoService::askingPermissionReply();
            $bot->reply($reply);
            $this->recordBotAnswer($conversationId, $text, $reply);
            $this->afterBotAnswer($bot, false);
            $this->sendMenuHint($bot, $onHold);
            return;
        }

        // Try to answer directly from clinic data first.
        $infoReply = ClinicInfoService::answerForText($text);
        if ($infoReply !== null) {
            Log::info('FALLBACK answered from ClinicInfoService: "' . $text . '"');
            $bot->reply($infoReply);
            $this->recordBotAnswer($conversationId, $text, $infoReply);
            $this->afterBotAnswer($bot, false);
            $this->sendMenuHint($bot, $onHold);
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
        $hasOpenInquiry = $conversationId !== '' && Inquiry::where('conversation_id', $conversationId)
            ->where('resolved_status', '!=', 'Resolved')
            ->exists();

        // The patient's schedule visit is on hold and the bot can't answer this:
        // it goes to staff, and the visit now waits for them (not for a button).
        if ($onHold) {
            $this->forwardToStaffWhileHeld($bot, $text, $conversationId, $hasOpenInquiry);
            return;
        }

        $this->isUnhandledInquiry = true;

        if (!$hasOpenInquiry) {
            $this->sendInquiryAck($bot);
        }
    }

    // CONTINUE / START OVER / CANCEL for a schedule visit that is on hold

    // Back to the exact step the patient left, with what they had typed.
    protected function continueHeldSchedule($bot)
    {
        $hold = ScheduleVisitHold::take((string) $bot->getMessage()->getSender());

        if ($hold === null) {
            $this->noHeldSchedule($bot);
            return;
        }

        $bot->startConversation(new AppointmentConversation(
            $hold['step'] ?? 'patient_form',
            $hold['state'] ?? [],
            $hold['draft'] ?? null
        ));
    }

    // Same schedule visit, from the very beginning.
    protected function restartHeldSchedule($bot)
    {
        $hold = ScheduleVisitHold::take((string) $bot->getMessage()->getSender());

        if ($hold === null) {
            $this->noHeldSchedule($bot);
            return;
        }

        $bot->reply('Okay! Restarting your schedule visit from the beginning.');
        $bot->startConversation(new AppointmentConversation());
    }

    protected function cancelHeldSchedule($bot)
    {
        $hold = ScheduleVisitHold::take((string) $bot->getMessage()->getSender());

        if ($hold === null) {
            $this->noHeldSchedule($bot);
            return;
        }

        $bot->reply('Okay, your schedule visit has been cancelled. Type "Menu" anytime to start again.');
        $this->sendGreeting($bot, false);
    }

    // A stale Continue / Start Over / Cancel button (the visit is no longer on hold).
    protected function noHeldSchedule($bot)
    {
        $bot->reply('That schedule visit is no longer active. Please tap "Schedule Visit" to start again.');
        $this->sendGreeting($bot, false);
    }

    // The patient moved on to another flow: the paused visit is dropped.
    protected function dropHeldSchedule($bot): bool
    {
        return ScheduleVisitHold::forget((string) $bot->getMessage()->getSender());
    }

    // If this chat has an open inquiry, the patient's message and the bot's answer
    // (a "thank you" and "you're welcome", a greeting, clinic info...) are also
    // written into that inquiry's thread, so Admin/Staff can read the whole
    // conversation and see that the patient is already satisfied.
    protected function recordBotAnswer(string $conversationId, string $patientText, string $botText): void
    {
        InquiryThread::recordBotExchange($conversationId, $patientText, $botText);
    }

    // The bot answered but no menu buttons came with the answer (the quick-reply
    // buttons disappear after any message without buttons), so remind the patient
    // how to bring them back. Not needed while a schedule visit is on hold: the
    // Continue / Start Over / Cancel buttons (or the staff wait) are on screen.
    protected function sendMenuHint($bot, bool $onHold): void
    {
        if (!$onHold) {
            $bot->reply(InquiryConversation::MENU_HINT);
        }
    }

    // After the bot answers something. With a schedule visit on hold and the
    // buttons due (bot-answered, resolved, or idle auto-release), the Continue /
    // Start Over / Cancel buttons come back; while still waiting on staff nothing
    // is added. With nothing on hold, the main menu returns when asked for.
    protected function afterBotAnswer($bot, bool $showMenu)
    {
        $conversationId = (string) $bot->getMessage()->getSender();

        if (!ScheduleVisitHold::has($conversationId)) {
            if ($showMenu) {
                $this->sendGreeting($bot, false);
            }
            return;
        }

        $status = ScheduleVisitHold::status($conversationId);
        if (!empty($status['ready'])) {
            $bot->reply(ScheduleVisitHold::choicesQuestion());
        }
    }

    // A free-typed message the bot can't answer, while the visit is on hold:
    // record it in the open thread (or start one), and make the visit wait for staff.
    protected function forwardToStaffWhileHeld($bot, string $text, string $conversationId, bool $hadOpenInquiry)
    {
        try {
            $log = ChatbotLog::create([
                'conversation_id' => $conversationId !== '' ? $conversationId : null,
                'user_message'    => $text !== '' ? $text : '(no text)',
                'bot_message'     => $hadOpenInquiry ? '(no reply)' : InquiryConversation::FORWARDED_TEXT,
            ]);

            $inquiry = $this->recordInquiryReply($conversationId, $text, $log);
            $this->skipExchangeLog = true;
        } catch (\Throwable $e) {
            Log::error('Failed to forward message while schedule visit on hold: ' . $e->getMessage());
            $inquiry = null;
        }

        $hold = $inquiry ? ScheduleVisitHold::linkInquiry($conversationId, (int) $inquiry->inquiry_id) : null;
        if ($hold) {
            $bot->reply(ScheduleVisitHold::marker($hold));
        }

        if (!$hadOpenInquiry) {
            $this->sendInquiryAck($bot);
        }
    }

    // Attachment uploaded while a visit is already on hold: it now waits for staff.
    protected function holdForUploadedAttachment($bot)
    {
        $conversationId = (string) $bot->getMessage()->getSender();
        $inquiryId = ScheduleVisitHold::openInquiryId($conversationId);

        if ($inquiryId === null) {
            return;
        }

        $hold = ScheduleVisitHold::linkInquiry($conversationId, $inquiryId);
        if ($hold) {
            $bot->reply(ScheduleVisitHold::marker($hold));
        }
    }

    protected function logExchange(string $conversationId, string $userMessage, array $capturedReplies, $botman)
    {
        // Already logged while the message was handled (see forwardToStaffWhileHeld).
        if ($this->skipExchangeLog) {
            return;
        }

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
        $this->recordInquiryReply($conversationId, $userMessage, $log);
    }

    protected function recordInquiryReply(
        string $conversationId,
        string $userMessage,
        ChatbotLog $log,
        ?string $attachmentPath = null,
        ?string $attachmentName = null
    ) {
        try {
            // Same chat = same inquiry, Resolved man o hindi. Kapag Resolved na
            // at nag-message ulit ang patient, ibabalik lang sa Pending ang
            // dating inquiry (hindi gagawa ng bago).
            $openInquiry = Inquiry::latestForConversation($conversationId);
            $openInquiry?->reopenIfResolved();

            if (!$openInquiry) {
                $openInquiry = Inquiry::create([
                    'patient_id'      => null,
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
        $bot->reply(InquiryConversation::FORWARDED_TEXT);
    }
}