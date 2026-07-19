<?php
namespace App\Http\Controllers;

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Web\WebDriver;
use BotMan\BotMan\Cache\LaravelCache;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use App\Conversations\AppointmentConversation;
use App\Services\ClinicInfoService;
use App\Models\ChatbotLog;
use App\Models\Staff\Inquiry;
use App\Models\Staff\InquiryReply;
use App\Middleware\CaptureReplyMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class BotManController extends Controller
{
    protected bool $isUnhandledInquiry = false;

    public function handle(Request $request)
    {
        if ($request->isJson()) {
            $request->request->add($request->json()->all());
        }

        $incomingText = (string) $request->input('message', '');
        $conversationId = (string) $request->input('userId', '');

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

    /**
     * Sadyang hindi na tinatanong ang pangalan ng patient kapag inquiry
     * lang naman ito — direktang ini-forward agad sa Admin/Staff.
     */
    protected function handleFallback($bot)
    {
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
        try {
            // NOTE: hindi na natin ginagamit ang $botman->userStorage()
            // dito — may natukoy tayong bug kung saan "nagsha-share" ito
            // ng laman sa lahat ng magkaibang session (kaya lumalabas na
            // parehong patient_id=5 kahit magkaibang conversation_id).
            // Hanggang hindi pa naaayos ang totoong ugat nito sa BotMan
            // package mismo, direkta na lang nating i-null ito dito para
            // hindi tayo magkaroon ng maling naka-link na patient_id sa
            // mga guest inquiry.
            $patientId = null;
            $guestName = null;

            $openInquiry = Inquiry::where('conversation_id', $conversationId)
                ->where('resolved_status', '!=', 'Resolved')
                ->orderByDesc('inquiry_id')
                ->first();

            if ($openInquiry) {
                InquiryReply::create([
                    'inquiry_id' => $openInquiry->inquiry_id,
                    'sender'     => 'Patient',
                    'message'    => $userMessage,
                    'is_staff'   => false, // FIX: dati'y wala nito, kaya nagde-default sa 'true' (mali)
                ]);

                $openInquiry->save();
                return;
            }

            $newInquiry = Inquiry::create([
                'patient_id'      => $patientId,
                'guest_name'      => $guestName,
                'log_id'          => $log->log_id,
                'conversation_id' => $conversationId !== '' ? $conversationId : null,
                'inquiry_type'    => 'General',
                'resolved_status' => 'Pending',
                'created_at'      => now(),
            ]);
            // NOTE: HINDI na natin idadagdag ang unang mensahe sa
            // inquiry_replies — nasa chatbot_logs.user_message na ito
            // (konektado via log_id). Kung idadagdag pa natin dito,
            // doble itong lalabas sa Admin/Staff panel.
        } catch (\Throwable $e) {
            Log::error('Failed to record inquiry: ' . $e->getMessage());
        }
    }

    protected function sendGreeting($bot)
    {
        $question = Question::create('Hello! Welcome to PolyClinic Lipa. How can I help you today?')
            ->fallback('Please choose an option from the buttons above.')
            ->addButtons([
                Button::create('📋 Schedule Visit')->value('schedule visit'),
                Button::create('ℹ️ General Information')->value('general information'),
            ]);
        $bot->reply($question);
    }

    protected function sendInquiryAck($bot)
    {
        $bot->reply("Thanks for your message! I've forwarded it to our Admin/Staff team — they'll reply to you here shortly.");
    }
}