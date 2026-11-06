<?php

namespace Tests\Feature;

use App\Http\Controllers\BotManController;
use App\Models\Staff\InquiryReply;
use App\Models\admin_models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateChatAttachmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        (require database_path('migrations/2026_01_01_000000_create_users_table.php'))->up();
        foreach (['chatbot_logs', 'inquiries', 'inquiry_replies', 'app_notifications'] as $table) {
            (require database_path('migrations/2026_01_01_000001_create_'.$table.'_table.php'))->up();
        }
        Storage::fake('public');
        Storage::fake('chat_attachments');
        Mail::fake();
    }

    private function upload(): string
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a24sAAAAASUVORK5CYII=');
        return $this->post('/chatbot/attachment', ['attachment' => UploadedFile::fake()->createWithContent('photo.png', $png), 'userId' => 'patient-chat-123'])
            ->assertOk()->assertJsonPath('success', true)->json('attachmentUrl');
    }

    private function account(string $role, string $status = 'active'): User
    {
        $user = User::create(['name' => $role, 'email' => strtolower($role).'@example.test', 'password' => 'test-password', 'user_role' => $role]);
        $user->forceFill(['status' => $status])->save();
        return $user;
    }

    public function test_upload_is_private_and_the_uploader_session_can_read_it(): void
    {
        $url = $this->upload();
        $reply = InquiryReply::firstOrFail();
        $this->assertStringContainsString('/chatbot/attachments/'.$reply->reply_id, $url);
        $this->assertSame($url, $reply->attachment_url);
        $this->assertStringNotContainsString('/storage/', $url);
        $this->assertSame([], Storage::disk('public')->allFiles());
        Storage::disk('chat_attachments')->assertExists(basename($reply->attachment_path));
        $response = $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
        $this->assertSame(Storage::disk('chat_attachments')->get(basename($reply->attachment_path)), $response->streamedContent());
    }

    public function test_url_and_conversation_id_do_not_grant_an_unrelated_guest_access(): void
    {
        $url = $this->upload();
        $this->withSession(['chat_attachment_ids' => []])->get($url.'?userId=patient-chat-123')->assertForbidden();
    }

    public function test_admin_and_staff_can_read_without_the_uploaders_session(): void
    {
        $url = $this->upload();
        $this->withSession(['chat_attachment_ids' => []]);
        foreach (['Admin', 'Staff'] as $role) $this->actingAs($this->account($role))->get($url)->assertOk();
    }

    public function test_inactive_staff_and_other_roles_cannot_read_someone_elses_file(): void
    {
        $url = $this->upload();
        $this->withSession(['chat_attachment_ids' => []]);
        $this->actingAs($this->account('Staff', 'inactive'))->get($url)->assertRedirect('/auth/login');
        $this->assertGuest();
        $this->actingAs($this->account('Patient'))->get($url)->assertForbidden();
    }

    public function test_existing_bearer_token_clients_can_use_the_protected_link_without_an_api_route_change(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id(); $table->morphs('tokenable'); $table->string('name');
            $table->string('token', 64)->unique(); $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
        $url = $this->upload();
        $this->withSession(['chat_attachment_ids' => []]);
        $token = $this->account('Staff')->createToken('mobile')->plainTextToken;
        $this->withToken($token)->get($url)->assertOk();
    }

    public function test_pdf_download_has_the_original_filename_and_is_not_inline(): void
    {
        $url = $this->post('/chatbot/attachment', ['attachment' => UploadedFile::fake()->createWithContent('report.pdf', "%PDF-1.4\nTest report"), 'userId' => 'patient-chat-123'])
            ->assertOk()->json('attachmentUrl');
        $response = $this->get($url)->assertOk();
        $this->assertStringStartsWith('attachment;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('report.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_authorized_access_to_missing_files_or_traversal_paths_returns_404(): void
    {
        $url = $this->upload();
        $reply = InquiryReply::firstOrFail();
        Storage::disk('chat_attachments')->delete(basename($reply->attachment_path));
        $this->get($url)->assertNotFound();
        $reply->update(['attachment_path' => 'chat-attachments/../secret.pdf']);
        $this->get($url)->assertNotFound();
    }

    public function test_invalid_uploads_do_not_create_files(): void
    {
        $this->postJson('/chatbot/attachment', ['attachment' => UploadedFile::fake()->createWithContent('page.html', '<script>alert(1)</script>')])->assertUnprocessable();
        $this->assertSame([], Storage::disk('chat_attachments')->allFiles());
        $this->assertSame(0, InquiryReply::count());
    }

    public function test_database_failure_removes_the_uploaded_file_and_rolls_back_the_log(): void
    {
        $mock = $this->partialMock(BotManController::class);
        $mock->shouldAllowMockingProtectedMethods()->shouldReceive('recordInquiryReply')->once()->andReturnNull();
        $this->post('/chatbot/attachment', ['attachment' => UploadedFile::fake()->createWithContent('report.pdf', "%PDF-1.4\nTest report")])->assertStatus(500);
        $this->assertSame([], Storage::disk('chat_attachments')->allFiles());
        $this->assertSame(0, DB::table('chatbot_logs')->count());
    }
}
