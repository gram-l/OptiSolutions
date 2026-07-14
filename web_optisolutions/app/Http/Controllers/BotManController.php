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
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class BotManController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('BOTMAN HANDLE CALLED - Content-Type: ' . $request->header('Content-Type'));
        Log::info('RAW BODY: ' . $request->getContent());
        Log::info('ALL FIELDS: ' . json_encode($request->all()));

        if ($request->isJson()) {
            $request->request->add($request->json()->all());
            Log::info('JSON MERGED INTO REQUEST');
        }

        try {
            DriverManager::loadDriver(WebDriver::class);
            $botman = BotManFactory::create(config('botman'), new LaravelCache(), $request);

            // ---------- Entry points ----------
            // NOTE: these only fire when there is NO active conversation waiting
            // on an ask(). Once AppointmentConversation starts, mid-flow
            // interception for these same commands is handled INSIDE
            // AppointmentConversation (see handleGlobalCommand()).
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

            // ---------- Fallback (first message / unrecognized input) ----------
            $botman->fallback(function ($bot) {
                Log::info('FALLBACK - Received text: "' . $bot->getMessage()->getText() . '"');
                $this->sendGreeting($bot);
            });

            $botman->listen();
            Log::info('LISTEN FINISHED');
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
     * Shared greeting shown on first load and when user types "menu".
     */
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