<?php

namespace Tests\Feature;

use App\Mail\ChatbotInquiryAlert;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ChatbotInquiryEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.url' => 'https://clinic.example']);
        (require database_path('migrations/2026_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_01_01_000001_create_app_notifications_table.php'))->up();
        foreach ([['Admin', 'active', 'admin@example.com'], ['Staff', 'active', 'staff@example.com'],
            ['Staff', 'inactive', 'inactive@example.com'], ['Patient', 'active', 'patient@example.com']] as [$role, $status, $email]) {
            DB::table('users')->insert(['name' => 'Test Account', 'email' => $email, 'password' => 'unused', 'user_role' => $role, 'status' => $status]);
        }
        Mail::fake();
    }

    public function test_inquiry_sends_individual_admin_and_staff_emails_and_keeps_the_original_notification(): void
    {
        NotificationService::newInquiry('A guest', 12);
        $this->assertDatabaseCount('app_notifications', 1);
        $this->assertDatabaseHas('app_notifications', [
            'type' => 'chat_inquiry', 'title' => 'New Chatbot Inquiry',
            'message' => "A guest submitted an inquiry the chatbot couldn't answer.",
            'reference_type' => 'inquiry', 'reference_id' => 12, 'is_read' => 0,
        ]);
        Mail::assertSent(ChatbotInquiryAlert::class, 2);
        foreach (['admin@example.com', 'staff@example.com'] as $email) {
            Mail::assertSent(ChatbotInquiryAlert::class, fn ($mail) =>
                $mail->hasTo($email) && count($mail->to) === 1 && !$mail->cc && !$mail->bcc
                && $mail->inquiryId === 12 && $mail->loginUrl === 'https://polyclinic-lipa.tech/auth/login'
            );
        }
    }

    public function test_other_notification_types_do_not_send_email(): void
    {
        NotificationService::newFeedback('A guest', 1, 5);
        NotificationService::newFeedback('A guest', 2, 1);
        NotificationService::newComplaint('A guest', 3);
        NotificationService::newVisit('A guest', 'A doctor', '2026-10-10', 4);
        NotificationService::system('System notification', 'Test message');
        $this->assertDatabaseCount('app_notifications', 5);
        Mail::assertNothingSent();
    }

    public function test_chatbot_follow_up_inquiry_also_emails_but_reading_notifications_does_not(): void
    {
        NotificationService::lowRatingFollowUp('A guest', 2, 13);
        DB::table('app_notifications')->update(['is_read' => 1]);
        Mail::assertSent(ChatbotInquiryAlert::class, 2);
        Mail::assertSent(ChatbotInquiryAlert::class, fn ($mail) => $mail->inquiryId === 13);
    }

    public function test_rolled_back_inquiry_does_not_send_and_committed_inquiry_does(): void
    {
        DB::beginTransaction();
        NotificationService::newInquiry('A guest', 10);
        Mail::assertNothingSent();
        DB::rollBack();
        Mail::assertNothingSent();
        DB::beginTransaction();
        NotificationService::newInquiry('A guest', 11);
        Mail::assertNothingSent();
        DB::commit();
        Mail::assertSent(ChatbotInquiryAlert::class, 2);
    }

    public function test_smtp_failure_does_not_break_the_saved_notification_or_stop_other_recipients(): void
    {
        Mail::shouldReceive('to')->twice()->andThrow(new \RuntimeException('SMTP unavailable'));
        NotificationService::newInquiry('A guest', 14);
        $this->assertDatabaseHas('app_notifications', ['reference_id' => 14, 'is_read' => 0]);
    }
}
