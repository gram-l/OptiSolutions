<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivatizeChatAttachmentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public'); Storage::fake('chat_attachments');
    }

    public function test_migration_verifies_and_moves_only_chat_files_and_is_safe_to_rerun(): void
    {
        Storage::disk('public')->put('chat-attachments/report.pdf', 'private report');
        Storage::disk('public')->put('profile_photos/photo.png', 'public avatar');
        $this->artisan('chat:privatize-attachments')->assertExitCode(0);
        Storage::disk('public')->assertMissing('chat-attachments/report.pdf');
        Storage::disk('public')->assertExists('profile_photos/photo.png');
        $this->assertSame('private report', Storage::disk('chat_attachments')->get('report.pdf'));
        $this->artisan('chat:privatize-attachments')->assertExitCode(0);
    }

    public function test_dry_run_does_not_move_any_files(): void
    {
        Storage::disk('public')->put('chat-attachments/report.pdf', 'private report');
        $this->artisan('chat:privatize-attachments', ['--dry-run' => true])->assertExitCode(0);
        Storage::disk('public')->assertExists('chat-attachments/report.pdf');
        Storage::disk('chat_attachments')->assertMissing('report.pdf');
    }

    public function test_a_conflicting_private_copy_is_not_overwritten_and_the_public_copy_is_not_deleted(): void
    {
        Storage::disk('public')->put('chat-attachments/report.pdf', 'original');
        Storage::disk('chat_attachments')->put('report.pdf', 'different');
        $this->artisan('chat:privatize-attachments')->assertExitCode(1);
        $this->assertSame('original', Storage::disk('public')->get('chat-attachments/report.pdf'));
        $this->assertSame('different', Storage::disk('chat_attachments')->get('report.pdf'));
    }

    public function test_matching_existing_copy_can_complete_an_interrupted_migration(): void
    {
        Storage::disk('public')->put('chat-attachments/report.pdf', 'same');
        Storage::disk('chat_attachments')->put('report.pdf', 'same');
        $this->artisan('chat:privatize-attachments')->assertExitCode(0);
        Storage::disk('public')->assertMissing('chat-attachments/report.pdf');
        Storage::disk('chat_attachments')->assertExists('report.pdf');
    }

    public function test_unexpected_nested_paths_are_left_untouched(): void
    {
        Storage::disk('public')->put('chat-attachments/nested/report.pdf', 'original');
        $this->artisan('chat:privatize-attachments')->assertExitCode(1);
        Storage::disk('public')->assertExists('chat-attachments/nested/report.pdf');
        $this->assertSame([], Storage::disk('chat_attachments')->allFiles());
    }
}
