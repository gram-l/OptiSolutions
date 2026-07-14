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
use App\Middleware\CaptureReplyMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class BotManController extends Controller
{
    public function handle(Request $request)
    {
        if ($request->isJson()) {
            $request->request->add($request->json()->all());
        }

        $incomingText = (string) $request->input('message', '');
        $conversationId = (string) $request->input('userId', '');

        CaptureReplyMiddleware::reset();

        try {
            DriverManager::loadDriver(WebDriver::class);
            $botman = BotManFactory::create(config('botman'), new LaravelCache(), $request);
            $botman->middleware->sending(new CaptureReplyMiddleware());

            // ---------- Entry points ----------
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
                $this->sendGreeting($bot);
            });

            $botman->listen();

            $this->logExchange($conversationId, $incomingText, CaptureReplyMiddleware::$captured);

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

    protected function logExchange(string $conversationId, string $userMessage, array $capturedReplies)
    {
        try {
            $replies = collect($capturedReplies)->filter()->implode("\n---\n");

            if ($userMessage === '' && $replies === '') {
                return;
            }

            ChatbotLog::create([
                'conversation_id' => $conversationId !== '' ? $conversationId : null,
                'user_message'    => $userMessage !== '' ? $userMessage : '(no text)',
                'bot_message'     => $replies !== '' ? $replies : '(no reply)',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to save chatbot log: ' . $e->getMessage());
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
}