<?php
namespace Tests\Feature;

use App\Models\ChatbotCommand;
use App\Services\ClinicInfoService;
use Database\Seeders\ClinicChatbotCommandsSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClinicChatbotCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        (require database_path('migrations/2026_01_01_000001_create_chatbot_commands_table.php'))->up();
        (require database_path('migrations/2026_09_22_000001_restructure_chatbot_commands.php'))->up();
    }

    public function test_english_and_tagalog_triggers_match_the_same_active_command(): void
    {
        $this->seed(ClinicChatbotCommandsSeeder::class);
        $english = ClinicInfoService::matchCustomCommand('HOW TO RESCHEDULE MY VISIT');
        $tagalog = ClinicInfoService::matchCustomCommand('paano mag reschedule');
        $this->assertNotNull($english);
        $this->assertEquals($english->command_id, $tagalog->command_id);
        $this->assertDatabaseCount('chatbot_commands', 15);
        $this->assertDatabaseCount('chatbot_command_triggers', 90);
        $this->assertSame(0, DB::table('chatbot_command_triggers')->whereIn('trigger_value', ChatbotCommand::RESERVED_TRIGGERS)->count());
    }

    public function test_reseeding_keeps_edited_replies_and_does_not_steal_triggers(): void
    {
        $custom = ChatbotCommand::create(['label' => 'Existing walk-in command', 'reply_text' => 'Admin reply', 'is_active' => true]);
        $custom->triggers()->create(['trigger_value' => 'can i walk in']);
        $this->seed(ClinicChatbotCommandsSeeder::class);
        $command = ChatbotCommand::where('label', 'Visit booking help')->first();
        $command->update(['reply_text' => 'Edited by admin', 'is_active' => false]);
        $this->seed(ClinicChatbotCommandsSeeder::class);
        $this->assertDatabaseCount('chatbot_commands', 16);
        $this->assertDatabaseCount('chatbot_command_triggers', 90);
        $this->assertSame('Edited by admin', $command->fresh()->reply_text);
        $this->assertFalse($command->fresh()->is_active);
        $this->assertSame($custom->command_id, ClinicInfoService::matchCustomCommand('can i walk in')->command_id);
    }
}
