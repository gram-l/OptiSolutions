<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Web\WebDriver;
use App\Conversations\AppointmentConversation;
use Illuminate\Support\Facades\Log;

class BotManController extends Controller
{
    public function handle()
{
    try {
        DriverManager::loadDriver(WebDriver::class);
        $botman = BotManFactory::create(config('botman'));

        $botman->hears('schedule visit', function ($bot) {
            $bot->startConversation(new AppointmentConversation());
        });

        $botman->hears('menu', function ($bot) {
            $bot->reply('Hi! I\'m your PolyClinic Assistant. How can I help you today?');
        });

        $botman->fallback(function ($bot) {
            $bot->reply("I'm not sure how to respond to that yet. Try 'schedule visit' to book an appointment.");
        });

        return $botman->listen();
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