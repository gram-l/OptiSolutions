<?php
namespace App\Http\Controllers;

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Web\WebDriver;
use BotMan\BotMan\Cache\LaravelCache;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use App\Conversations\AppointmentConversation;
use App\Conversations\ComplaintConversation;
use App\Conversations\ReviewConversation;
use App\Services\ClinicInfoService;
use App\Models\ChatbotLog;
use App\Models\Staff\Inquiry;
use App\Models\Staff\InquiryReply;
use App\Models\Staff\AppNotification;
use App\Middleware\CaptureReplyMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class BotManController extends Controller
{
    protected bool $isUnhandledInquiry = false;

    protected const IGNORED_INQUIRY_TEXTS = [
        'schedule visit',
        'general information',
        'complaint', 'submit complaint',
        'review', 'submit review/rating',
        'menu',
        "no, i'm all set",
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
                $bot->startConversation(new AppointmentConversation());
            });

            $botman->hears('general information', function ($bot) {
                Log::info('MATCHED: general information');
                $bot->reply(ClinicInfoService::infoCardMessage());
                // Re-show the menu so the buttons don't disappear.
                $this->sendGreeting($bot, false);
            });

            // Long-form and short-form phrasing both need to be recognized.
            $botman->hears('complaint', function ($bot) {
                Log::info('MATCHED: complaint');
                $bot->startConversation(new ComplaintConversation());
            });

            $botman->hears('submit complaint', function ($bot) {
                Log::info('MATCHED: submit complaint');
                $bot->startConversation(new ComplaintConversation());
            });

            $botman->hears('review', function ($bot) {
                Log::info('MATCHED: review');
                $bot->startConversation(new ReviewConversation(null, null));
            });

            $botman->hears('submit review/rating', function ($bot) {
                Log::info('MATCHED: submit review/rating');
                $bot->startConversation(new ReviewConversation(null, null));
            });

            $botman->hears('menu', function ($bot) {
                Log::info('MATCHED: menu');
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

        if (in_array(mb_strtolower($text), self::IGNORED_INQUIRY_TEXTS, true)) {
            Log::info('FALLBACK ignored (known command text): "' . $text . '"');
            return;
        }

        // Friendly acknowledgment, e.g. "ok", "thanks", "salamat".
        if (ClinicInfoService::looksLikeAcknowledgment($text)) {
            Log::info('FALLBACK answered as acknowledgment: "' . $text . '"');
            $bot->reply(ClinicInfoService::acknowledgmentReply());
            return;
        }

        // Try to answer directly from clinic data first.
        $infoReply = ClinicInfoService::answerForText($text);
        if ($infoReply !== null) {
            Log::info('FALLBACK answered from ClinicInfoService: "' . $text . '"');
            $bot->reply($infoReply);
            return;
        }

        $this->isUnhandledInquiry = true;
        $this->sendInquiryAck($bot);
    }

    protected function logExchange(string $conversationId, string $userMessage, array $capturedReplies, $botman)
    {
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
            $openInquiry = Inquiry::where('conversation_id', $conversationId)
                ->where('resolved_status', '!=', 'Resolved')
                ->orderByDesc('inquiry_id')
                ->first();

            if ($openInquiry) {
                InquiryReply::create([
                    'inquiry_id' => $openInquiry->inquiry_id,
                    'sender'     => 'Patient',
                    'message'    => $userMessage,
                    'is_staff'   => false,
                ]);

                $openInquiry->save();
                return;
            }

            Inquiry::create([
                'patient_id'      => $patientId,
                'guest_name'      => $guestName,
                'log_id'          => $log->log_id,
                'conversation_id' => $conversationId !== '' ? $conversationId : null,
                'inquiry_type'    => 'General',
                'resolved_status' => 'Pending',
                'created_at'      => now(),
            ]);

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
        $question = Question::create($showGreeting ? 'Hello! Welcome to PolyClinic Lipa. How can I help you today?' : '')
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