<?php
namespace App\Http\Controllers;

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Web\WebDriver;
use BotMan\BotMan\Cache\LaravelCache;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use App\Conversations\AppointmentConversation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
            $botman->hears('schedule visit', function ($bot) {
                Log::info('MATCHED: schedule visit');
                $bot->startConversation(new AppointmentConversation());
            });

            $botman->hears('general information', function ($bot) {
                Log::info('MATCHED: general information');
                $this->sendClinicInfo($bot);
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

    /**
     * Fetches clinic info dynamically from the `clinic_info` table
     * and sends it as a clean, readable formatted message.
     */
    protected function sendClinicInfo($bot)
    {
        $info = DB::table('clinic_info')->first();

        if (!$info) {
            $bot->reply("⚠️ Sorry, we couldn't load our clinic information right now. Please try again later.");
            return;
        }

        $data = (array) $info;

        // 👉 IMPORTANT: Palitan ang mga keys sa kaliwa (hal. 'clinic_name')
        // ng ACTUAL column names ng clinic_info table mo.
        $order = [
            'clinic_name'     => '🏥',
            'address'         => '📍',
            'contact_number'  => '📞',
            'email'           => '📧',
            'operating_hours' => '🕒',
            'about_us'        => '📝',
            'facebook_link'   => '🔗',
        ];

        $lines = ["🏥 PolyClinic Lipa - Clinic Information", ""];

        foreach ($order as $key => $emoji) {
            if (!empty($data[$key])) {
                $label = ucwords(str_replace('_', ' ', $key));
                $lines[] = "{$emoji} {$label}: {$data[$key]}";
                $lines[] = "";
            }
        }

        // Fallback: kung may column na hindi kasama sa $order, idagdag pa rin
        foreach ($data as $key => $value) {
            if (!isset($order[$key]) && $key !== 'id' && $value !== null && $value !== '') {
                $label = ucwords(str_replace('_', ' ', $key));
                $lines[] = "• {$label}: {$value}";
                $lines[] = "";
            }
        }

        $bot->reply(rtrim(implode("\n", $lines)));
    }
}