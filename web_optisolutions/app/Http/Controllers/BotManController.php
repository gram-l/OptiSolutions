<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Web\WebDriver;
use BotMan\BotMan\Cache\LaravelCache;
use App\Conversations\AppointmentConversation;
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

            $botman->hears('schedule visit', function ($bot) {
                Log::info('MATCHED: schedule visit');
                $bot->startConversation(new AppointmentConversation());
            });

            $botman->hears('menu', function ($bot) {
                Log::info('MATCHED: menu');
                $bot->reply('Hi! I\'m your PolyClinic Assistant. How can I help you today?');
            });

            $botman->fallback(function ($bot) {
                Log::info('FALLBACK - Received text: "' . $bot->getMessage()->getText() . '"');
                $bot->reply("I'm not sure how to respond to that yet. Try 'schedule visit' to book an appointment.");
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
}