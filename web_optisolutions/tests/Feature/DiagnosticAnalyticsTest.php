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
    public function test_combined_specialties_are_separate_filters_that_match_the_same_doctor(): void
    {
        DB::table('services')->where('service_id', 1)->update(['title' => 'General / Adult Medicine']);
        DB::table('services')->where('service_id', 2)->update(['title' => 'Ophthalmology / General Medicine']);
        $this->review('2026-10-20 12:00:00', 'negative');
        foreach (['General', 'Adult Medicine'] as $specialty) {
            $data = $this->data(['diagnostic_doctor' => 1, 'diagnostic_specialty' => $specialty]);
            $this->assertSame(1, $data['periods']['current']['negative']);
            $this->assertSame(['Adult Medicine', 'General', 'General Medicine', 'Ophthalmology'], $data['specialties']->all());
        }
        $this->assertSame(0, $this->data(['diagnostic_specialty' => 'General Medicine'])['periods']['current']['negative']);
    }

    public function test_both_dashboard_addresses_supply_diagnostic_data_and_keep_admin_middleware(): void
    {
        $this->withoutVite();
        $this->actingAs((new \App\Models\admin_models\User([
            'name' => 'Test Admin', 'email' => 'admin@example.test', 'user_role' => 'Admin',
        ]))->forceFill(['status' => 'active']));
        $this->partialMock(\App\Http\Controllers\admin_acc\AdminDashboardController::class,
            fn ($mock) => $mock->shouldReceive('getDashboardData')->twice()->andReturn($this->dashboardFixture()));

        foreach (['/admin_acc/dashboard', '/admin/dashboard'] as $url) {
            $route = app('router')->getRoutes()->match(Request::create($url));
            $this->assertContains('admin', $route->gatherMiddleware());
            $this->get($url.'?diagnostic_week=2026-10-05')
                ->assertOk()->assertViewHas('diagnostic', fn ($data) => $data['week'] === '2026-10-05')
                ->assertSee('Diagnostic Analytics')->assertSee('Oct 5, 2026')
                ->assertSee('Generate report')->assertSee(route('admin.dashboard.diagnostics.report'));
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-26', 'Asia/Manila'));
        Schema::create('doctors', function (Blueprint $t) { $t->increments('doctor_id'); $t->string('doctor_name'); });
        Schema::create('services', function (Blueprint $t) { $t->increments('service_id'); $t->string('title'); });
        DB::table('services')->insert([['service_id' => 1, 'title' => 'Consultation'], ['service_id' => 2, 'title' => 'Pediatrics']]);
        Schema::create('schedule_visit', function (Blueprint $t) { $t->increments('visit_id'); $t->integer('doctor_id')->nullable(); $t->string('service_type')->nullable(); });
        Schema::create('feedback', function (Blueprint $t) { $t->increments('feedback_id'); $t->integer('visit_id')->nullable(); $t->text('feedback_text')->nullable(); $t->integer('star_rating')->default(3); $t->dateTime('submitted_at'); });
        Schema::create('complaints', function (Blueprint $t) { $t->increments('complaint_id'); $t->text('complaint_text'); $t->string('category')->nullable(); $t->dateTime('created_at'); });
        Schema::create('sentiment_results', function (Blueprint $t) { $t->integer('feedback_id')->unique(); $t->string('sentiment_label'); });
        (require database_path('migrations/2026_11_14_000001_add_issue_analysis_to_feedback_and_complaints.php'))->up();
        DB::table('doctors')->insert([
            ['doctor_id' => 1, 'doctor_name' => 'Doctor A'],
            ['doctor_id' => 2, 'doctor_name' => 'Doctor B'],
        ]);
        (require database_path('migrations/2026_11_05_000001_create_doctor_service_table.php'))->up();
        DB::table('doctor_service')->insert([['doctor_id' => 1, 'service_id' => 1], ['doctor_id' => 2, 'service_id' => 2]]);
        DB::table('schedule_visit')->insert(['visit_id' => 1, 'doctor_id' => 1, 'service_type' => 'Consultation']);
        Http::fake(fn ($request) => Http::response(['details' => array_map(fn ($item) => [
            'id' => $item['id'], 'source' => $item['source'],
            'matched_categories' => str_contains($item['text'], 'wait') ? ['waiting_time'] : [],
            'findings' => str_contains($item['text'], 'wait')
                ? [['category' => 'waiting_time', 'reported_reason' => 'Long wait reported']] : [],
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

    private function dashboardFixture(): array
    {
        return ['chartInterpretations' => array_fill_keys(
            ['ratings', 'sentiment', 'inquiry-status', 'services', 'visits', 'inquiry-volume'], ''
        )];
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

    public function test_confirmed_no_match_replaces_legacy_complaint_category_and_survives_outage(): void
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
        $this->assertTrue($complaint['issues_analyzed']);
        $this->assertSame([], $complaint['issue_categories']);
        $this->assertSame('other', $complaint['category']);
        $this->assertSame('other', DB::table('complaints')->value('category'));
        Http::assertNothingSent();
    }

    public function test_saved_findings_and_ratings_remain_visible_with_pending_records_during_outage(): void
    {
        $this->review('2026-10-20', 'Negative');
        DB::table('complaints')->insert(['complaint_text' => 'Long wait', 'created_at' => '2026-10-20 12:00:00']);
        $this->artisan('issues:analyze')->assertSuccessful();
        foreach (['feedback', 'complaints'] as $table) {
            $stored = json_decode(DB::table($table)->value('issue_analysis'), true);
            $this->assertSame(['waiting_time'], $stored['matched_categories']);
            $this->assertSame('Long wait reported', $stored['findings'][0]['reported_reason']);
        }

        $this->review('2026-10-21', 'Negative', 'awaiting analysis');
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*' => Http::response([], 503)]);
        $feedback = app(\App\Http\Controllers\admin_acc\FeedbackController::class)->index()->getData();
        $this->assertSame('waiting_time', $feedback['complaintData']->first()['category']);
        $this->assertSame('Long wait reported', $feedback['feedbackData']->last()['issue_findings'][0]['reported_reason']);
        $this->assertFalse($feedback['feedbackData']->first()['issues_analyzed']);
        $data = $this->data();
        $this->assertSame(1, $data['pending_issues']);
        $this->assertSame(['waiting_time'], $data['categories']);
        $this->assertSame(1, $data['periods']['current']['categories']['waiting_time']);
        $this->assertEquals(3, $data['periods']['current']['comments'][0]['rating']);
        $report = app(DiagnosticAnalyticsService::class)->dashboard(Request::create('/'), forReport: true);
        $this->assertSame([3 => 1], $report['periods'][1]['overall']['categories']['waiting_time']['negative_ratings']);
        $this->assertSame(['waiting_time' => 1], $report['periods'][1]['complaint_categories']);
        Http::assertSent(fn ($request) => count($request['items']) === 1 && $request['items'][0]['text'] === 'awaiting analysis');
        $this->assertNull(DB::table('feedback')->where('feedback_id', 2)->value('issue_analysis'));
    }

    public function test_rejected_week_selection_returns_visible_modal_errors(): void
    {
        $this->withoutVite();
        $this->actingAs((new \App\Models\admin_models\User([
            'name' => 'Test Admin', 'email' => 'admin@example.test', 'user_role' => 'Admin',
        ]))->forceFill(['status' => 'active']));
        $this->partialMock(\App\Http\Controllers\admin_acc\AdminDashboardController::class,
            fn ($mock) => $mock->shouldReceive('getDashboardData')->andReturn($this->dashboardFixture()));
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
        $this->actingAs((new \App\Models\admin_models\User([
            'name' => 'Test Admin', 'email' => 'admin@example.test', 'user_role' => 'Admin',
        ]))->forceFill(['status' => 'active']));
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

    public function test_report_combines_low_ratings_negative_reviews_and_clinic_complaints(): void
    {
        $this->getJson(route('admin.dashboard.diagnostics.report'))->assertUnauthorized();
        $this->actingAs((new \App\Models\admin_models\User([
            'name' => 'Test Admin', 'email' => 'admin@example.test', 'user_role' => 'Admin',
        ]))->forceFill(['status' => 'active']));
        $this->review('2026-10-19', 'Negative');
        $this->review('2026-10-20', 'Neutral');
        $this->review('2026-10-25 23:59:59', null, null);
        $this->review('2026-10-21', 'Negative', 'wait', null);
        $this->review('2026-10-26', 'Negative');
        DB::table('complaints')->insert(['complaint_id' => 1, 'complaint_text' => 'Long wait',
            'created_at' => '2026-10-20 12:00:00']);

        $url = route('admin.dashboard.diagnostics.report', [
            'diagnostic_week' => '2026-10-19', 'diagnostic_doctor' => 1, 'diagnostic_specialty' => 'Consultation',
        ]);
        $response = $this->get($url)->assertOk()
            ->assertSee('Diagnostic Report')->assertSee('Print / Save as PDF')
            ->assertSee('Oct 19, 2026')->assertSee('3/5 (1)')->assertSee('Waiting Time (1)')
            ->assertDontSee('Long wait');
        $current = $response->viewData('report')['periods'][1];
        $this->assertSame(3, $current['overall']['reviews']);
        $this->assertSame(3, $current['overall']['low']);
        $this->assertSame(1, $current['overall']['low_without_comment']);
        $this->assertSame(1, $current['overall']['negative']);
        $this->assertSame(['negative' => 1, 'low' => 2, 'negative_ratings' => [3 => 1]],
            $current['overall']['categories']['waiting_time']);
        $this->assertSame($current['overall']['categories'], $current['doctors'][1]['categories']);
        $this->assertSame($current['overall']['categories'], $current['services']['Consultation']['categories']);
        $this->assertSame(['waiting_time' => 1], $current['complaint_categories']);
        Http::assertSentCount(1);
        $this->assertCount(3, Http::recorded()[0][0]['items']);
    }

    public function test_staff_dashboard_renders_shared_modal_with_staff_comparison_routes(): void
    {
        $this->withoutVite();
        $staff = (new \App\Models\admin_models\User([
            'name' => 'Test Staff', 'email' => 'staff@example.test', 'user_role' => 'Staff',
        ]))->forceFill(['status' => 'active']);
        $staff->user_id = 123;
        $this->actingAs($staff);
        $html = view('staff.dashboard', ['diagnostic' => $this->data()])->render();
        $this->assertStringContainsString('id="diagnostic-open"', $html);
        $this->assertStringContainsString('id="diagnostic-modal"', $html);
        $this->assertStringContainsString('action="'.route('staff.dashboard').'#diagnostic-title"', $html);
        $this->assertStringContainsString('data-compare-url="'.route('staff.dashboard.diagnostics').'"', $html);
        $this->assertStringNotContainsString('id="diagnostic-report"', $html);
        $this->assertStringNotContainsString('href="'.route('admin_acc.feedback.index').'"', $html);
        $route = app('router')->getRoutes()->match(Request::create('/staff/dashboard/diagnostics'));
        $this->assertContains('staff', $route->gatherMiddleware());
    }

    public function test_staff_comparison_endpoint_returns_filtered_results_and_rejects_other_roles(): void
    {
        $this->getJson('/staff/dashboard/diagnostics')->assertUnauthorized();
        $this->actingAs((new \App\Models\admin_models\User([
            'name' => 'Test Admin', 'email' => 'admin@example.test', 'user_role' => 'Admin',
        ]))->forceFill(['status' => 'active']));
        $this->getJson('/staff/dashboard/diagnostics')->assertForbidden();
        $this->actingAs((new \App\Models\admin_models\User([
            'name' => 'Test Staff', 'email' => 'staff@example.test', 'user_role' => 'Staff',
        ]))->forceFill(['status' => 'active']));
        $this->review('2026-10-20', 'Negative', 'wait');
        $response = $this->getJson('/staff/dashboard/diagnostics?diagnostic_doctor=1&diagnostic_specialty=Consultation');
        $response->assertOk()->assertJsonStructure(['html']);
        $this->assertStringContainsString('Waiting Time', $response->json('html'));
        $this->assertStringContainsString('Doctor A', $response->json('html'));
        $this->assertStringNotContainsString('href="'.route('admin_acc.feedback.index').'"', $response->json('html'));
        $this->getJson('/staff/dashboard/diagnostics?diagnostic_week=2026-10-20')
            ->assertUnprocessable()->assertJsonValidationErrors('diagnostic_week');
        $this->getJson('/staff/dashboard/diagnostics?diagnostic_doctor=1&diagnostic_specialty=Pediatrics')
            ->assertUnprocessable()->assertJsonValidationErrors('diagnostic_specialty');
        $this->getJson('/admin/dashboard/diagnostics')->assertRedirect();
    }
}
