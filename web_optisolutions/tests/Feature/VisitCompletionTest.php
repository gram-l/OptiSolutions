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
        (require database_path('migrations/2026_01_01_000001_create_doctor_schedules_table.php'))->up();
        (require database_path('migrations/2026_10_14_000001_add_visit_times_to_schedule_visit.php'))->up();
        DB::table('patients')->insert(['patient_id' => 1, 'patient_fname' => 'Test', 'patient_lname' => 'Patient', 'patient_email' => 'patient@example.com']);
        DB::table('doctors')->insert(['doctor_id' => 1, 'doctor_name' => 'Dr. Test', 'specialty' => 'Ophthalmology']);
        DB::table('schedule_visit')->insert(['visit_id' => 1, 'patient_id' => 1, 'doctor_id' => 1, 'service_type' => 'Eye examination', 'visit_date' => now()->toDateString()]);
        Mail::fake();
    }

    private function loginAs(string $role): void
    {
        $this->actingAs(User::create(['name' => 'Test User', 'email' => strtolower($role) . '@example.com', 'password' => 'test-password', 'user_role' => $role]));
    }

    public function test_both_roles_can_only_reschedule_to_their_doctors_future_schedule_and_email_details(): void
    {
        DB::table('doctor_schedules')->insert(['schedule_id' => 1, 'doctor_id' => 1, 'day' => 'Monday', 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
        DB::table('doctors')->insert(['doctor_id' => 2, 'doctor_name' => 'Other doctor', 'specialty' => 'Surgery']);
        DB::table('doctor_schedules')->insert(['schedule_id' => 2, 'doctor_id' => 2, 'day' => 'Monday', 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
        $date = now('Asia/Manila')->next('Monday')->toDateString();
        foreach (['Admin' => 'admin_acc', 'Staff' => 'staff'] as $role => $prefix) {
            $this->loginAs($role);
            $this->getJson("/$prefix/appointments/1/schedules")->assertOk()->assertJsonCount(1, 'schedules');
            $this->postJson("/$prefix/appointments/1/reschedule", ['visit_date' => $date, 'schedule_id' => 2])->assertUnprocessable();
            $this->postJson("/$prefix/appointments/1/reschedule", ['visit_date' => \Carbon\Carbon::parse($date)->addDay()->toDateString(), 'schedule_id' => 1])->assertUnprocessable();
            $this->postJson("/$prefix/appointments/1/reschedule", ['visit_date' => $date, 'schedule_id' => 1])->assertOk()->assertJson(['email_sent' => true]);
            $this->assertDatabaseHas('schedule_visit', ['visit_id' => 1, 'visit_date' => $date, 'visit_time' => '09:00:00', 'visit_end_time' => '12:00:00']);
        }
        Mail::assertSent(\App\Mail\VisitRescheduled::class, 2);
        Mail::assertSent(\App\Mail\VisitRescheduled::class, fn ($mail) => $mail->hasTo('patient@example.com') && $mail->doctorName === 'Dr. Test' && str_contains($mail->scheduleDetails, '9:00 AM - 12:00 PM'));
        DB::table('schedule_visit')->update(['visited' => true]);
        $this->postJson('/staff/appointments/1/reschedule', ['visit_date' => $date, 'schedule_id' => 1])->assertUnprocessable();
        Mail::assertSent(\App\Mail\VisitRescheduled::class, 2);
    }

    public function test_reschedule_rejects_past_dates_and_missing_email_and_keeps_saved_change_when_mail_fails(): void
    {
        $this->loginAs('Admin');
        DB::table('doctor_schedules')->insert(['schedule_id' => 1, 'doctor_id' => 1, 'day' => 'Monday', 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
        $date = now('Asia/Manila')->next('Monday')->toDateString();
        $this->postJson('/admin_acc/appointments/1/reschedule', ['visit_date' => now()->subDays(7)->toDateString(), 'schedule_id' => 1])->assertUnprocessable();
        DB::table('patients')->update(['patient_email' => null]);
        $this->postJson('/admin_acc/appointments/1/reschedule', ['visit_date' => $date, 'schedule_id' => 1])->assertUnprocessable();
        DB::table('patients')->update(['patient_email' => 'patient@example.com']);
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $this->postJson('/admin_acc/appointments/1/reschedule', ['visit_date' => $date, 'schedule_id' => 1])->assertOk()->assertJson(['success' => true, 'email_sent' => false]);
        $this->assertDatabaseHas('schedule_visit', ['visit_id' => 1, 'visit_date' => $date]);
    }

    public function test_inactive_or_unavailable_doctors_have_no_reschedule_options_for_both_roles(): void
    {
        DB::table('doctor_schedules')->insert(['schedule_id' => 1, 'doctor_id' => 1, 'day' => 'Monday', 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
        foreach (['Admin' => 'admin_acc', 'Staff' => 'staff'] as $role => $prefix) {
            $this->loginAs($role);
            foreach ([['status' => 'Inactive', 'available' => 1], ['status' => 'Active', 'available' => 0]] as $state) {
                DB::table('doctors')->where('doctor_id', 1)->update($state);
                $this->getJson("/$prefix/appointments/1/schedules")->assertOk()->assertJsonPath('doctor_available', false)->assertJsonCount(0, 'schedules');
                $this->postJson("/$prefix/appointments/1/reschedule", ['visit_date' => now()->next('Monday')->toDateString(), 'schedule_id' => 1])->assertUnprocessable()->assertJsonValidationErrors('schedule_id');
            }
        }
        Mail::assertNothingSent();
    }

    public function test_unmarked_visits_become_no_shows_after_the_scheduled_day_in_manila(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-13 23:59:59', 'Asia/Manila'));
        $visit = (object) ['visited' => false, 'visit_date' => '2026-10-13'];
        $this->assertSame('Pending', \App\Services\VisitReviewService::attendanceLabel($visit));
        $this->travelTo(\Carbon\Carbon::parse('2026-10-14 00:00:00', 'Asia/Manila'));
        $this->assertSame('Did not visit', \App\Services\VisitReviewService::attendanceLabel($visit));
        $visit->visited = true;
        $this->assertSame('Visited', \App\Services\VisitReviewService::attendanceLabel($visit));
        $visit->visited = false;
        $visit->visit_date = '2026-10-15';
        $this->assertSame('Pending', \App\Services\VisitReviewService::attendanceLabel($visit));
        $this->travelBack();
    }

    public function test_admin_marks_visit_and_emails_one_pass_linked_to_doctor_and_service(): void
    {
        $this->loginAs('Admin');
        $this->assertEquals(0, DB::table('schedule_visit')->value('visited'));
        $this->postJson('/admin_acc/appointments/1/visited')->assertOk()->assertJson(['visited' => true, 'email_sent' => true]);
        $visit = DB::table('schedule_visit')->first();
        $code = Crypt::decryptString($visit->review_pass_code);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{20}$/', $code);
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

    public function test_staff_visit_export_respects_search_doctor_service_and_date(): void
    {
        $this->loginAs('Staff');
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));
        DB::table('schedule_visit')->where('visit_id', 1)->update(['notes' => 'Follow-up check']);
        DB::table('doctors')->insert(['doctor_id' => 2, 'doctor_name' => 'Dr. Other', 'specialty' => 'Surgery']);
        DB::table('schedule_visit')->insert(['visit_id' => 2, 'patient_id' => 1, 'doctor_id' => 2, 'service_type' => 'Surgery', 'visit_date' => now()->toDateString()]);
        $response = $this->get(route('staff.visits.export', [
            'format' => 'csv', 'date' => now()->toDateString(), 'doctor' => 'Dr. Test',
            'service' => 'eye examination', 'search' => 'follow-up',
        ]))->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Dr. Test', $csv);
        $this->assertStringNotContainsString('Dr. Other', $csv);
    }

    public function test_email_includes_a_private_qr_image_and_review_link(): void
    {
        config(['app.url' => 'https://clinic.example.com']);
        $mail = new VisitReviewPass('Test Patient', 'Dr. Test', 'Eye examination', '2026-10-01', 'ABCDEFGHJKLM23456789', 1);
        $this->assertSame('https://clinic.example.com/home#review-code=ABCDEFGHJKLM23456789', $mail->reviewUrl);
        $png = $mail->qrCodePng();
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));
        $dimensions = unpack('Nwidth/Nheight', substr($png, 16, 8));
        $this->assertSame($dimensions['width'], $dimensions['height']);
        $this->assertGreaterThan(200, $dimensions['width']);
        $html = $mail->render();
        // Laravel converts embedded CID images to data URLs in render() previews.
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString($mail->reviewUrl, $html);
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
