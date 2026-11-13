<?php

namespace Tests\Feature;

use App\Services\DiagnosticAnalyticsService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        Carbon::setTestNow(Carbon::parse('2026-10-29 12:00:00', 'Asia/Manila'));
        Schema::create('patients', fn (Blueprint $t) => $t->increments('patient_id'));
        Schema::create('doctors', function (Blueprint $t) { $t->increments('doctor_id'); $t->string('doctor_name')->nullable(); $t->boolean('available')->default(true); });
        Schema::create('doctor_schedules', function (Blueprint $t) {
            $t->increments('schedule_id'); $t->integer('doctor_id'); $t->string('day');
            $t->time('start_time')->nullable(); $t->time('end_time')->nullable();
        });
        Schema::create('schedule_visit', function (Blueprint $t) { $t->increments('visit_id'); $t->integer('doctor_id')->nullable(); $t->string('service_type'); $t->date('visit_date'); });
        Schema::create('feedback', function (Blueprint $t) { $t->increments('feedback_id'); $t->integer('visit_id')->nullable(); $t->integer('star_rating'); $t->dateTime('submitted_at'); });
        Schema::create('sentiment_results', function (Blueprint $t) { $t->integer('feedback_id')->unique(); $t->string('sentiment_label'); });
        Schema::create('chatbot_logs', function (Blueprint $t) { $t->increments('log_id'); $t->dateTime('chat_time'); });
        Schema::create('inquiries', function (Blueprint $t) {
            $t->increments('inquiry_id'); $t->integer('log_id')->nullable();
            $t->string('inquiry_reply')->nullable(); $t->string('resolved_status')->nullable(); $t->dateTime('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function chartData(string $role, string $url): array
    {
        $this->actingAs((new \App\Models\admin_models\User([
            'name' => $role, 'email' => strtolower($role).'@example.test', 'user_role' => $role,
        ]))->forceFill(['status' => 'active']));
        return $this->withSession(['account_last_activity' => now()->timestamp])->getJson($url)->assertOk()->json();
    }

    public function test_admin_and_staff_charts_match_saved_sentiment_and_include_trend_and_inquiry_status(): void
    {
        foreach (['Positive', 'Positive', 'Positive', 'Positive', 'Neutral', 'Neutral', 'Negative', null] as $i => $label) {
            DB::table('feedback')->insert(['feedback_id' => $i + 1, 'star_rating' => 5,
                'submitted_at' => $i < 4 ? '2026-10-25 23:59:59' : '2026-10-26 00:00:00']);
            if ($label) DB::table('sentiment_results')->insert(['feedback_id' => $i + 1, 'sentiment_label' => $label]);
        }
        DB::table('schedule_visit')->insert([
            ['service_type' => 'Surgery', 'visit_date' => '2026-10-25'],
            ['service_type' => 'Pediatrics', 'visit_date' => '2026-10-26'],
            ['service_type' => 'Pediatrics', 'visit_date' => '2026-10-27'],
        ]);
        DB::table('chatbot_logs')->insert(['log_id' => 1, 'chat_time' => '2026-10-26 00:00:00']);
        DB::table('inquiries')->insert([
            ['log_id' => 1, 'resolved_status' => 'Resolved', 'created_at' => '2026-10-25'],
            ['log_id' => null, 'resolved_status' => 'In Progress', 'created_at' => '2026-10-27'],
            ['log_id' => null, 'resolved_status' => null, 'created_at' => '2026-10-25'],
        ]);
        $admin = $this->chartData('Admin', '/admin/dashboard/data');
        $staff = $this->chartData('Staff', '/staff/dashboard/data');
        foreach (array_keys(app(\App\Services\DashboardChartService::class)->data()) as $key) {
            $this->assertSame($admin[$key], $staff[$key], $key.' must match across roles');
        }
        $this->assertEquals(57, $staff['positivePercent']);
        $this->assertEquals(29, $staff['neutralPercent']);
        $this->assertEquals(14, $staff['negativePercent']);
        $this->assertEquals([0, 0, 0, 0, 100, 0], $staff['sentimentTrendPositive']);
        $this->assertEquals([0, 0, 0, 0, 0, 67], $staff['sentimentTrendNeutral']);
        $this->assertEquals([0, 0, 0, 0, 0, 33], $staff['sentimentTrendNegative']);
        $this->assertSame(1, $staff['resolvedInquiries']);
        $this->assertSame(2, $staff['unresolvedInquiries']);
        $this->assertSame([1, 1, 0, 0, 0, 0, 0], $staff['inquiryVolumeByDay']);
        $this->assertSame([0, 0, 0, 0, 1, 2], $staff['weeklyVisits']);
        $this->assertSame('Pediatrics', $staff['serviceDistribution'][0]['service_type']);
        $this->assertSame([
            'ratings' => 'No ratings are linked to scheduled visits yet.',
            'sentiment' => 'Positive feedback had the largest share at 57%.',
            'inquiry-status' => 'The clinic has resolved 1 of 3 chatbot inquiries.',
            'services' => 'Pediatrics accounted for 2 of 3 scheduled visits.',
            'visits' => 'Scheduled visits rose from 0 to 1 across the last two completed weeks.',
            'inquiry-volume' => '2 days tied for the most inquiries with 1 each.',
        ], $admin['chartInterpretations']);
    }

    public function test_admin_sentiment_percentages_add_up_to_one_hundred(): void
    {
        foreach (['Positive', 'Positive', 'Positive', 'Neutral', 'Neutral', 'Neutral', 'Negative', 'Negative'] as $label) {
            $feedbackId = DB::table('feedback')->insertGetId([
                'star_rating' => 5,
                'submitted_at' => '2026-10-26 00:00:00',
            ], 'feedback_id');
            DB::table('sentiment_results')->insert(['feedback_id' => $feedbackId, 'sentiment_label' => $label]);
        }

        $data = $this->chartData('Admin', '/admin/dashboard/data');
        $this->assertSame(38, $data['positivePercent']);
        $this->assertSame(37, $data['neutralPercent']);
        $this->assertSame(25, $data['negativePercent']);
    }

    public function test_admin_dashboard_renders_the_welcome_date_and_sentiment_rows(): void
    {
        $this->withoutVite();
        $period = [
            'label' => 'Test week',
            'negative' => 0,
            'categories' => [],
            'classified' => 0,
            'negative_rate' => null,
            'pending' => 0,
        ];
        $this->mock(DiagnosticAnalyticsService::class)->shouldReceive('dashboard')->once()->andReturn([
            'week' => '2026-10-26',
            'latest_week' => '2026-10-26',
            'doctors' => [],
            'specialties' => [],
            'periods' => ['previous' => $period, 'current' => $period],
            'available' => false,
            'unlinked' => 0,
        ]);
        $this->chartData('Admin', '/admin/dashboard/data');

        $this->get('/admin_acc/dashboard')
            ->assertOk()
            ->assertSee('Thursday, 29 October 2026')
            ->assertSee('data-label="Positive"', false)
            ->assertSee('data-label="Neutral"', false)
            ->assertSee('data-label="Negative"', false)
            ->assertSee('No ratings are linked to scheduled visits yet.')
            ->assertSee('No reviews have been analyzed for sentiment yet.')
            ->assertSee('No chatbot inquiries have been recorded yet.')
            ->assertSee('No visits have been scheduled for any service yet.')
            ->assertSee('No visits were scheduled in the selected weeks.')
            ->assertSee('Daily inquiry volume stayed at zero in the selected week.');
    }

    public function test_empty_staff_dashboard_still_supplies_all_chart_arrays(): void
    {
        $staff = $this->chartData('Staff', '/staff/dashboard/data');
        $this->assertEquals(0, $staff['positivePercent']);
        $this->assertSame([0, 0, 0, 0, 0, 0], $staff['sentimentTrendNegative']);
        $this->assertSame(0, $staff['resolvedInquiries']);
        $this->assertSame(0, $staff['unresolvedInquiries']);
        $this->assertSame([0, 0, 0, 0, 0, 0, 0], $staff['inquiryVolumeByDay']);
    }

    public function test_staff_cards_count_todays_visits_and_unique_active_doctors_scheduled_today(): void
    {
        DB::table('doctors')->insert([
            ['doctor_id' => 1, 'available' => 1], ['doctor_id' => 2, 'available' => 1],
            ['doctor_id' => 3, 'available' => 0], ['doctor_id' => 4, 'available' => 1],
        ]);
        DB::table('doctor_schedules')->insert([
            ['doctor_id' => 1, 'day' => 'Thursday', 'start_time' => '08:00', 'end_time' => '12:00'],
            ['doctor_id' => 1, 'day' => 'Thursday', 'start_time' => '13:00', 'end_time' => '17:00'],
            ['doctor_id' => 2, 'day' => 'Friday', 'start_time' => '08:00', 'end_time' => '12:00'],
            ['doctor_id' => 3, 'day' => 'Thursday', 'start_time' => '08:00', 'end_time' => '12:00'],
        ]);
        DB::table('patients')->insert(['patient_id' => 1]);
        foreach (['2026-10-28', '2026-10-29', '2026-10-29', '2026-10-30'] as $date) {
            DB::table('schedule_visit')->insert(['service_type' => 'Pediatrics', 'visit_date' => $date]);
        }
        $staff = $this->chartData('Staff', '/staff/dashboard/data');
        $this->assertSame(2, $staff['todaysScheduledVisits']);
        $this->assertSame(1, $staff['availableDoctorsToday']);
        $this->assertSame(1, $staff['totalPatients']);
        // Philippine Friday has begun even though UTC is still Thursday.
        Carbon::setTestNow(Carbon::parse('2026-10-29 16:01:00', 'UTC'));
        $staff = $this->chartData('Staff', '/staff/dashboard/data');
        $this->assertSame(1, $staff['todaysScheduledVisits']);
        $this->assertSame(1, $staff['availableDoctorsToday']);
        Carbon::setTestNow(Carbon::parse('2026-10-31 12:00:00', 'Asia/Manila'));
        $staff = $this->chartData('Staff', '/staff/dashboard/data');
        $this->assertSame(0, $staff['todaysScheduledVisits']);
        $this->assertSame(0, $staff['availableDoctorsToday']);
    }

    public function test_chart_filters_work_for_both_roles_without_changing_sentiment_period(): void
    {
        DB::table('inquiries')->insert([
            ['created_at' => '2026-10-25 23:59:59'],
            ['created_at' => '2026-10-26 00:00:00'],
        ]);
        DB::table('schedule_visit')->insert(['service_type' => 'Surgery', 'visit_date' => '2026-09-01']);
        foreach (['Admin' => '/admin/dashboard/data', 'Staff' => '/staff/dashboard/data'] as $role => $url) {
            foreach ([4, 6, 12] as $weeks) {
                $data = $this->chartData($role, $url.'?inquiry_period=last_week&visit_weeks='.$weeks);
                $this->assertCount($weeks, $data['weeklyVisits']);
                $this->assertCount($weeks, $data['weekLabels']);
                $this->assertSame('This week (ongoing)', $data['weekLabels'][$weeks - 1]);
                $this->assertSame([0, 0, 0, 0, 0, 0, 1], $data['inquiryVolumeByDay']);
                $this->assertSame($weeks === 12 ? 1 : 0, array_sum($data['weeklyVisits']));
                $this->assertCount(6, $data['sentimentTrendLabels']);
                $this->assertStringContainsString('Oct 19, 2026', $data['inquiryPeriodLabel']);
            }
            $current = $this->chartData($role, $url.'?inquiry_period=this_week&visit_weeks=4');
            $this->assertSame([1, 0, 0, 0, 0, 0, 0], $current['inquiryVolumeByDay']);
            $this->getJson($url.'?inquiry_period=invalid&visit_weeks=100')
                ->assertUnprocessable()->assertJsonValidationErrors(['inquiry_period', 'visit_weeks']);
        }
    }

    public function test_rating_leaders_use_linked_stars_and_rating_counts_in_both_dashboards(): void
    {
        DB::table('doctors')->insert([
            ['doctor_id' => 1, 'doctor_name' => 'Doctor A'],
            ['doctor_id' => 2, 'doctor_name' => 'Doctor B'],
        ]);
        DB::table('schedule_visit')->insert([
            ['visit_id' => 1, 'doctor_id' => 1, 'service_type' => 'Pediatrics', 'visit_date' => '2026-10-20'],
            ['visit_id' => 2, 'doctor_id' => 2, 'service_type' => 'Surgery', 'visit_date' => '2026-10-20'],
        ]);
        foreach ([[1, 5], [1, 3], [2, 4], [null, 5], [2, 0], [2, 6]] as [$visit, $stars]) {
            DB::table('feedback')->insert(['visit_id' => $visit, 'star_rating' => $stars, 'submitted_at' => '2026-10-20']);
        }
        foreach (['Admin' => '/admin/dashboard/data', 'Staff' => '/staff/dashboard/data'] as $role => $url) {
            $data = $this->chartData($role, $url);
            $this->assertCount(2, $data['leadingDoctors']);
            $this->assertSame('Doctor A', $data['leadingDoctors'][0]['name']);
            $this->assertEquals(4, $data['leadingDoctors'][0]['average_rating']);
            $this->assertSame(2, $data['leadingDoctors'][0]['rating_count']);
            $this->assertSame('Pediatrics', $data['leadingServices'][0]['name']);
            $this->assertSame(1, $data['leadingServices'][1]['rating_count']);
        }
        $html = view('partials.dashboard_rating_leaders', [
            'leadingDoctors' => collect([(object) ['name' => '<script>unsafe</script>', 'average_rating' => 4, 'rating_count' => 2]]),
        ])->render();
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('4.0', $html);
        $this->assertStringContainsString('No linked ratings yet.', $html);
    }
}
