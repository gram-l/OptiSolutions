<?php

namespace Tests\Feature;

use App\Services\VisitReviewService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VisitReviewPassTest extends TestCase
{
    private string $code = '0123456789ABCDEF0123456789ABCDEF';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        foreach (['schedule_visit', 'feedback', 'chatbot_logs', 'patients'] as $table) {
            (require database_path("migrations/2026_01_01_000001_create_{$table}_table.php"))->up();
        }
        (require database_path('migrations/2026_10_12_000001_add_visited_and_review_pass_to_schedule_visit.php'))->up();
        (require database_path('migrations/2026_10_13_000001_link_feedback_to_completed_visit.php'))->up();
        DB::table('patients')->insert(['patient_id' => 9, 'patient_fname' => 'Test', 'patient_email' => 'patient@example.com']);
        DB::table('schedule_visit')->insert([
            'visit_id' => 3, 'patient_id' => 9, 'doctor_id' => 4, 'service_type' => 'Eye examination',
            'visit_date' => now()->toDateString(), 'visited' => true,
            'review_pass_hash' => hash('sha256', $this->code), 'review_pass_sent_at' => now(),
        ]);
        Http::fake(['*' => Http::response([], 503)]);
    }

    public function test_only_unused_emailed_completed_visit_codes_are_accepted(): void
    {
        $service = app(VisitReviewService::class);
        $this->assertNull($service->verify(''));
        $this->assertNull($service->verify(str_repeat('A', 32)));
        $this->assertEquals(3, $service->verify(strtolower($this->code))->visit_id);
        DB::table('schedule_visit')->update(['visited' => false]);
        $this->assertNull($service->verify($this->code));
        DB::table('schedule_visit')->update(['visited' => true, 'review_pass_sent_at' => null]);
        $this->assertNull($service->verify($this->code));
    }

    public function test_feedback_page_uses_the_review_visit_instead_of_guessing_from_patient(): void
    {
        foreach (['doctors', 'complaints', 'sentiment_results'] as $table) {
            (require database_path("migrations/2026_01_01_000001_create_{$table}_table.php"))->up();
        }
        DB::table('doctors')->insert(['doctor_id' => 4, 'doctor_name' => 'Dr. Linked Visit', 'specialty' => 'Eye care']);
        app(VisitReviewService::class)->submit($this->code, 4, null);
        DB::table('feedback')->insert(['patient_id' => 9, 'star_rating' => 5, 'submitted_at' => now()]);
        $view = app(\App\Http\Controllers\admin_acc\FeedbackController::class)->index();
        $entries = $view->getData()['feedbackData'];
        $linked = $entries->firstWhere('doctor_id', 4);
        $this->assertSame('Dr. Linked Visit', $linked['doctor']);
        $this->assertSame('Eye examination', $linked['service']);
        $legacy = $entries->firstWhere('doctor_id', null);
        $this->assertNull($legacy['doctor']);
        $this->assertNull($legacy['service']);
        $this->assertCount(2, $entries);
    }

    public function test_new_twenty_character_codes_are_accepted_and_consumed_once(): void
    {
        $code = VisitReviewService::generateCode();
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{20}$/', $code);
        DB::table('schedule_visit')->update(['review_pass_hash' => hash('sha256', $code)]);
        $service = app(VisitReviewService::class);
        $this->assertEquals(3, $service->verify(strtolower($code))->visit_id);
        $this->assertEquals(3, $service->submit($code, 5, 'Excellent service')->visit_id);
        $this->assertNull($service->verify($code));
    }

    public function test_review_consumes_code_once_and_is_linked_to_the_visit(): void
    {
        $service = app(VisitReviewService::class);
        $feedback = $service->submit(strtolower($this->code), 5, ' Good service ', null, true);
        $this->assertEquals(3, $feedback->visit_id);
        $this->assertEquals(9, $feedback->patient_id);
        $this->assertEquals('Good service', $feedback->feedback_text);
        $this->assertNotNull(DB::table('schedule_visit')->value('review_pass_used_at'));
        $this->assertNull($service->verify($this->code));
        try {
            $service->submit($this->code, 4, 'Second review', null, true);
            $this->fail('A code was reused.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('transaction_code', $exception->errors());
        }
        $this->assertDatabaseCount('feedback', 1);
        $this->assertDatabaseCount('chatbot_logs', 1);
    }

    public function test_invalid_rating_does_not_consume_code_and_blank_comments_remain_null(): void
    {
        $service = app(VisitReviewService::class);
        try {
            $service->submit($this->code, 0, 'Comment');
            $this->fail('Invalid rating was saved.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('star_rating', $exception->errors());
        }
        $this->assertNull(DB::table('schedule_visit')->value('review_pass_used_at'));
        $feedback = $service->submit($this->code, 5, '   ');
        $this->assertNull($feedback->feedback_text);
    }

    public function test_public_endpoint_cannot_bypass_code_and_analysis_failure_keeps_saved_review(): void
    {
        $this->postJson('/api/feedback', ['star_rating' => 5, 'feedback_text' => 'Good'])->assertUnprocessable()->assertJsonValidationErrors('transaction_code');
        $this->postJson('/api/feedback', ['transaction_code' => str_repeat('A', 32), 'star_rating' => 5])->assertUnprocessable();
        $this->assertDatabaseCount('feedback', 0);
        $this->postJson('/api/feedback', ['transaction_code' => $this->code, 'star_rating' => 5, 'feedback_text' => 'Good'])->assertCreated();
        $this->assertDatabaseCount('feedback', 1);
        $this->postJson('/api/feedback', ['transaction_code' => $this->code, 'star_rating' => 5])->assertUnprocessable();
        $this->assertDatabaseCount('feedback', 1);
    }

    public function test_abandoned_code_verification_does_not_consume_pass(): void
    {
        $service = app(VisitReviewService::class);
        $this->assertNotNull($service->verify($this->code));
        $this->assertNotNull($service->verify($this->code));
        $this->assertDatabaseCount('feedback', 0);
        $this->assertNull(DB::table('schedule_visit')->value('review_pass_used_at'));
    }
}
