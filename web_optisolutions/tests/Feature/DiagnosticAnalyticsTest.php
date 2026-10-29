<?php

namespace Tests\Feature;

use App\Services\DiagnosticAnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DiagnosticAnalyticsTest extends TestCase
{
    public function test_both_dashboard_addresses_supply_diagnostic_data_and_keep_admin_middleware(): void
    {
        $this->withoutVite();
        $this->actingAs(new \App\Models\admin_models\User([
            'name' => 'Test Admin', 'email' => 'admin@example.test', 'user_role' => 'Admin',
        ]));
        $this->partialMock(\App\Http\Controllers\admin_acc\AdminDashboardController::class,
            fn ($mock) => $mock->shouldReceive('getDashboardData')->twice()->andReturn([]));

        foreach (['/admin_acc/dashboard', '/admin/dashboard'] as $url) {
            $route = app('router')->getRoutes()->match(Request::create($url));
            $this->assertContains('admin', $route->gatherMiddleware());
            $this->get($url.'?diagnostic_week=2026-10-05')
                ->assertOk()->assertViewHas('diagnostic', fn ($data) => $data['week'] === '2026-10-05')
                ->assertSee('Diagnostic Analytics')->assertSee('Oct 5, 2026');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-26', 'Asia/Manila'));
        Schema::create('doctors', function (Blueprint $t) { $t->increments('doctor_id'); $t->string('doctor_name'); $t->string('specialty'); });
        Schema::create('schedule_visit', function (Blueprint $t) { $t->increments('visit_id'); $t->integer('doctor_id')->nullable(); $t->string('service_type')->nullable(); });
        Schema::create('feedback', function (Blueprint $t) { $t->increments('feedback_id'); $t->integer('visit_id')->nullable(); $t->text('feedback_text')->nullable(); $t->integer('star_rating')->default(3); $t->dateTime('submitted_at'); });
        Schema::create('complaints', function (Blueprint $t) { $t->increments('complaint_id'); $t->text('complaint_text'); $t->string('category')->nullable(); $t->dateTime('created_at'); });
        Schema::create('sentiment_results', function (Blueprint $t) { $t->integer('feedback_id')->unique(); $t->string('sentiment_label'); });
        DB::table('doctors')->insert([
            ['doctor_id' => 1, 'doctor_name' => 'Doctor A', 'specialty' => 'Consultation'],
            ['doctor_id' => 2, 'doctor_name' => 'Doctor B', 'specialty' => 'Pediatrics'],
        ]);
        DB::table('schedule_visit')->insert(['visit_id' => 1, 'doctor_id' => 1, 'service_type' => 'Consultation']);
        Http::fake(fn ($request) => Http::response(['details' => array_map(fn ($item) => [
            'id' => $item['id'], 'source' => $item['source'],
            'matched_categories' => str_contains($item['text'], 'wait') ? ['waiting_time'] : [],
            'findings' => [],
        ], $request['items'])]));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function review(string $date, ?string $label, ?string $text = 'wait', ?int $visit = 1): void
    {
        $id = DB::table('feedback')->insertGetId(['visit_id' => $visit, 'feedback_text' => $text,
            'submitted_at' => CarbonImmutable::parse($date)->format('Y-m-d H:i:s')], 'feedback_id');
        if ($label !== null) DB::table('sentiment_results')->insert(['feedback_id' => $id, 'sentiment_label' => $label]);
    }

    private function data(array $filters = []): array
    {
        return app(DiagnosticAnalyticsService::class)->dashboard(Request::create('/', 'GET', $filters));
    }

    public function test_rates_category_shares_and_excluded_records_use_correct_denominators(): void
    {
        foreach (['2026-10-15' => [8, 3, 32], '2026-10-22' => [14, 10, 26]] as $date => [$negative, $waiting, $positive]) {
            for ($i = 0; $i < $negative; $i++) $this->review($date, 'Negative', $i < $waiting ? 'wait' : 'another concern');
            for ($i = 0; $i < $positive; $i++) $this->review($date, 'Positive', 'great');
        }
        $this->review('2026-10-25', null, 'pending');
        $this->review('2026-10-25', 'Negative', '   ');
        $this->review('2026-10-26', 'Negative'); // Outside completed comparison periods.
        $data = $this->data();
        $this->assertSame('2026-10-19', $data['week']);
        $this->assertSame(20.0, $data['periods']['previous']['negative_rate']);
        $this->assertSame(35.0, $data['periods']['current']['negative_rate']);
        $this->assertSame(14, $data['periods']['current']['negative']);
        $this->assertSame(10, $data['periods']['current']['categories']['waiting_time']);
        $this->assertSame(1, $data['periods']['current']['pending']);
        $this->assertSame(1, $data['periods']['current']['rating_only']);
        $html = view('admin_acc.partials.diagnostic_analytics', ['diagnostic' => $data])->render();
        $plain = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertStringContainsString('Negative comments went up from 8 to 14 this week.', $plain);
        $this->assertStringContainsString('Waiting Time is the most mentioned problem: 10 of 14 negative comments this week (last week: 3 of 8).', $plain);
        $this->assertStringContainsString('Negative comments: 8 → 14 (up by 6).', $plain);
        $this->assertStringContainsString('Share that were negative: 20.0% → 35.0%.', $plain);
        $this->assertStringContainsString('↑ Mentioned more than last week', $plain);
        $this->assertStringContainsString('View comments (10 this week, 3 last week)', $plain);
        Http::assertSentCount(1);
        $this->assertCount(22, Http::recorded()[0][0]['items']);
    }

    public function test_filters_exclude_unlinked_records_and_escape_comment_html(): void
    {
        $this->review('2026-10-20', 'Negative', '<script>alert(1)</script> wait');
        $this->review('2026-10-20', 'Negative', 'wait', null);
        $data = $this->data(['diagnostic_doctor' => '1', 'diagnostic_specialty' => 'Consultation']);
        $this->assertSame(1, $data['unlinked']);
        $this->assertSame(1, $data['periods']['current']['negative']);
        $html = view('admin_acc.partials.diagnostic_analytics', ['diagnostic' => $data])->render();
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringNotContainsString('may help explain', $html);
        $this->assertSame(0, $this->data(['diagnostic_doctor' => '2'])['periods']['current']['negative']);
    }

    public function test_service_failure_or_incomplete_response_never_becomes_zero_concerns(): void
    {
        $this->review('2026-10-20', 'Negative');
        foreach ([Http::response([], 503), Http::response(['details' => []])] as $response) {
            Http::swap(new \Illuminate\Http\Client\Factory());
            Http::fake(['*' => $response]);
            $data = $this->data();
            $this->assertFalse($data['available']);
            $this->assertSame(1, $data['periods']['current']['negative']);
            $this->assertNull($data['periods']['current']['unmatched']);
        }
    }

    public function test_empty_periods_have_no_fabricated_rates_and_make_no_service_request(): void
    {
        $data = $this->data();
        $this->assertNull($data['periods']['current']['negative_rate']);
        $this->assertTrue($data['available']);
        Http::assertNothingSent();
    }

    public function test_simple_comparison_handles_ties_zero_counts_and_count_trends(): void
    {
        $data = $this->data();
        $render = fn ($value) => preg_replace('/\s+/', ' ', strip_tags(
            view('admin_acc.partials.diagnostic_results', ['diagnostic' => $value])->render()));
        $this->assertStringContainsString('Negative comments stayed at 0 this week.', $render($data));
        $this->assertStringContainsString('not enough classified comments', $render($data));
        $data['periods']['previous']['negative'] = 5;
        $data['periods']['current']['negative'] = 6;
        $data['periods']['previous']['categories'] = ['waiting_time' => 1, 'staff_attitude' => 4];
        $data['periods']['current']['categories'] = ['waiting_time' => 3, 'staff_attitude' => 3];
        $data['categories'] = ['waiting_time', 'staff_attitude'];
        $plain = $render($data);
        $this->assertStringContainsString('are tied as the most mentioned problems', $plain);
        $this->assertStringContainsString('last week: Waiting Time 1 of 5; Staff Attitude 4 of 5', $plain);
        $this->assertStringContainsString('↓ Mentioned less than last week', $plain);
        $data['periods']['previous']['categories']['waiting_time'] = 3;
        $this->assertStringContainsString('→ Mentioned as often as last week', $render($data));
        $data['periods']['current']['negative'] = 0;
        $data['periods']['current']['categories'] = [];
        $this->assertStringContainsString('Negative comments went down from 5 to 0 this week.', $render($data));
        $this->assertStringContainsString('(down by 5).', $render($data));
        $data['available'] = false;
        $this->assertStringContainsString('Problem analysis is currently unavailable or incomplete.', $render($data));
    }

    public function test_incomplete_current_week_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->data(['diagnostic_week' => '2026-10-26']);
    }

    public function test_feedback_page_does_not_reuse_stale_complaint_categories(): void
    {
        DB::table('complaints')->insert(['complaint_text' => 'No matching topic',
            'category' => 'waiting_time', 'created_at' => '2026-10-20 12:00:00']);
        $data = app(\App\Http\Controllers\admin_acc\FeedbackController::class)->index()->getData();
        $complaint = $data['complaintData']->first();
        $this->assertTrue($complaint['issues_analyzed']);
        $this->assertSame([], $complaint['issue_categories']);
        $this->assertSame('other', $complaint['category']);

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*' => Http::response([], 503)]);
        $data = app(\App\Http\Controllers\admin_acc\FeedbackController::class)->index()->getData();
        $complaint = $data['complaintData']->first();
        $this->assertFalse($complaint['issues_analyzed']);
        $this->assertSame([], $complaint['issue_categories']);
        $this->assertSame('unclassified', $complaint['category']);
        $this->assertSame('waiting_time', DB::table('complaints')->value('category'));
    }

    public function test_rejected_week_selection_returns_visible_modal_errors(): void
    {
        $this->withoutVite();
        $this->actingAs(new \App\Models\admin_models\User([
            'name' => 'Test Admin', 'email' => 'admin@example.test', 'user_role' => 'Admin',
        ]));
        $this->partialMock(\App\Http\Controllers\admin_acc\AdminDashboardController::class,
            fn ($mock) => $mock->shouldReceive('getDashboardData')->andReturn([]));
        $this->from('/admin_acc/dashboard')->get('/admin/dashboard?diagnostic_week=2026-10-20')
            ->assertRedirect('/admin_acc/dashboard')->assertSessionHasErrors('diagnostic_week');
        $this->get('/admin_acc/dashboard')->assertOk()
            ->assertSee('Select a Monday')
            ->assertSee('const hasDiagnosticErrors = true;', false)
            ->assertSee('value="2026-10-20"', false);
    }

    public function test_non_monday_selection_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->data(['diagnostic_week' => '2026-10-20']);
    }

    public function test_week_boundaries_include_sunday_and_work_across_new_year(): void
    {
        $this->review('2025-12-21 23:59:59', 'Negative'); // Before both weeks.
        $this->review('2025-12-22 00:00:00', 'Positive');
        $this->review('2025-12-28 23:59:59', 'Negative');
        $this->review('2025-12-29 00:00:00', 'Negative');
        $this->review('2026-01-04 23:59:59', 'Negative');
        $this->review('2026-01-05 00:00:00', 'Negative'); // Following Monday excluded.
        $data = $this->data(['diagnostic_week' => '2025-12-29']);
        $this->assertSame(2, $data['periods']['previous']['classified']);
        $this->assertSame(1, $data['periods']['previous']['negative']);
        $this->assertSame(2, $data['periods']['current']['negative']);
        $this->assertSame('Dec 29, 2025 – Jan 4, 2026', $data['periods']['current']['label']);
        Http::assertSentCount(1);
        $this->assertCount(3, Http::recorded()[0][0]['items']);
    }

    public function test_specialty_filter_uses_registered_doctor_specialty(): void
    {
        DB::table('schedule_visit')->where('visit_id', 1)->update(['service_type' => 'Incorrect historical service']);
        $this->review('2026-10-20', 'Negative');
        $data = $this->data(['diagnostic_specialty' => 'Consultation']);
        $this->assertSame(1, $data['periods']['current']['negative']);
        $this->assertSame('Consultation', $data['periods']['current']['comments'][0]['specialty']);
        $this->assertSame(['Consultation', 'Pediatrics'], $data['specialties']->all());
        $this->assertSame(0, $this->data(['diagnostic_specialty' => 'Pediatrics'])['periods']['current']['negative']);
    }

    public function test_compare_endpoint_returns_only_results_and_validates_filter_pairs(): void
    {
        $this->actingAs(new \App\Models\admin_models\User([
            'name' => 'Test Admin', 'email' => 'admin@example.test', 'user_role' => 'Admin',
        ]));
        $this->review('2026-10-20', 'Negative');
        $response = $this->getJson('/admin/dashboard/diagnostics?diagnostic_week=2026-10-19&diagnostic_doctor=1&diagnostic_specialty=Consultation');
        $response->assertOk()->assertJsonStructure(['html']);
        $html = $response->json('html');
        $this->assertStringContainsString('Waiting Time', $html);
        $this->assertStringContainsString('Doctor A', $html);
        $this->assertStringContainsString('doctor and specialty', $html);
        $this->assertStringNotContainsString('<dialog', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->getJson('/admin/dashboard/diagnostics?diagnostic_doctor=1&diagnostic_specialty=Pediatrics')
            ->assertUnprocessable()->assertJsonValidationErrors('diagnostic_specialty');
        $this->getJson('/admin/dashboard/diagnostics?diagnostic_doctor=999')
            ->assertUnprocessable()->assertJsonValidationErrors('diagnostic_doctor');
    }

    public function test_compare_endpoint_requires_authentication(): void
    {
        $this->getJson('/admin/dashboard/diagnostics')->assertUnauthorized();
    }
}
