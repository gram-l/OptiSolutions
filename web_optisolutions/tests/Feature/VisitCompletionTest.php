<?php

namespace Tests\Feature;

use App\Mail\VisitReviewPass;
use App\Models\admin_models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VisitCompletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        foreach (['users', 'doctors', 'patients', 'schedule_visit'] as $table) {
            $prefix = $table === 'users' ? '2026_01_01_000000' : '2026_01_01_000001';
            (require database_path("migrations/{$prefix}_create_{$table}_table.php"))->up();
        }
        (require database_path('migrations/2026_10_12_000001_add_visited_and_review_pass_to_schedule_visit.php'))->up();
        DB::table('patients')->insert(['patient_id' => 1, 'patient_fname' => 'Test', 'patient_lname' => 'Patient', 'patient_email' => 'patient@example.com']);
        DB::table('doctors')->insert(['doctor_id' => 1, 'doctor_name' => 'Dr. Test', 'specialty' => 'Ophthalmology']);
        DB::table('schedule_visit')->insert(['visit_id' => 1, 'patient_id' => 1, 'doctor_id' => 1, 'service_type' => 'Eye examination', 'visit_date' => now()->toDateString()]);
        Mail::fake();
    }

    private function loginAs(string $role): void
    {
        $this->actingAs(User::create(['name' => 'Test User', 'email' => strtolower($role) . '@example.com', 'password' => 'test-password', 'user_role' => $role]));
    }

    public function test_admin_marks_visit_and_emails_one_pass_linked_to_doctor_and_service(): void
    {
        $this->loginAs('Admin');
        $this->assertEquals(0, DB::table('schedule_visit')->value('visited'));
        $this->postJson('/admin_acc/appointments/1/visited')->assertOk()->assertJson(['visited' => true, 'email_sent' => true]);
        $visit = DB::table('schedule_visit')->first();
        $code = Crypt::decryptString($visit->review_pass_code);
        $this->assertEquals(hash('sha256', $code), $visit->review_pass_hash);
        $this->assertNotNull($visit->visited_at);
        $this->assertNotNull($visit->review_pass_sent_at);
        $this->assertNull($visit->review_pass_used_at);
        Mail::assertSent(VisitReviewPass::class, fn ($mail) => $mail->hasTo('patient@example.com')
            && $mail->transactionCode === $code && $mail->doctorName === 'Dr. Test' && $mail->service === 'Eye examination');
        $this->postJson('/admin_acc/appointments/1/visited')->assertOk();
        Mail::assertSent(VisitReviewPass::class, 1);
        $this->putJson('/admin_acc/appointments/1', ['visit_date' => now()->addDay()->toDateString()])->assertStatus(422);
        $this->deleteJson('/admin_acc/appointments/1')->assertStatus(422);
    }

    public function test_staff_can_mark_visit_and_patient_cannot(): void
    {
        $this->loginAs('Patient');
        $this->postJson('/staff/appointments/1/visited')->assertForbidden();
        $this->loginAs('Staff');
        $this->postJson('/staff/appointments/1/visited')->assertOk()->assertJson(['email_sent' => true]);
        Mail::assertSent(VisitReviewPass::class, 1);
    }

    public function test_future_visit_or_missing_patient_email_does_not_issue_a_pass(): void
    {
        $this->loginAs('Admin');
        DB::table('schedule_visit')->update(['visit_date' => now()->addDay()->toDateString()]);
        $this->postJson('/admin_acc/appointments/1/visited')->assertUnprocessable()->assertJsonValidationErrors('visited');
        DB::table('schedule_visit')->update(['visit_date' => now()->toDateString()]);
        DB::table('patients')->update(['patient_email' => null]);
        $this->postJson('/admin_acc/appointments/1/visited')->assertUnprocessable()->assertJsonValidationErrors('patient_email');
        $this->assertEquals(0, DB::table('schedule_visit')->value('visited'));
        $this->assertNull(DB::table('schedule_visit')->value('review_pass_hash'));
        Mail::assertNothingSent();
    }

    public function test_failed_delivery_keeps_visit_and_retry_reuses_the_original_code(): void
    {
        $this->loginAs('Staff');
        $mailFake = Mail::getFacadeRoot();
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->postJson('/staff/appointments/1/visited')->assertOk()->assertJson(['visited' => true, 'email_sent' => false]);
        $visit = DB::table('schedule_visit')->first();
        $code = Crypt::decryptString($visit->review_pass_code);
        $this->assertNull($visit->review_pass_sent_at);
        Mail::swap($mailFake);
        $this->postJson('/staff/appointments/1/visited')->assertOk()->assertJson(['email_sent' => true]);
        $this->assertEquals($visit->review_pass_hash, DB::table('schedule_visit')->value('review_pass_hash'));
        Mail::assertSent(VisitReviewPass::class, fn ($mail) => $mail->transactionCode === $code);
    }
}
