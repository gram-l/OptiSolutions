<?php

namespace Tests\Feature;

use App\Conversations\AppointmentConversation;
use App\Mail\VisitConfirmationMail;
use App\Models\Patient;
use App\Models\ScheduleVisit;
use App\Services\PatientBookingService;
use BotMan\BotMan\BotMan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingConversationProbe extends AppointmentConversation
{
    public array $messages = [];
    public bool $postPromptShown = false;

    public function __construct(array $patient, bool $sendCopy = true)
    {
        parent::__construct();
        $this->fname = $patient['patient_fname'];
        $this->lname = $patient['patient_lname'];
        $this->dob = $patient['patient_birthdate'];
        $this->email = $patient['patient_email'];
        $this->phone = $patient['patient_contact'];
        $this->doctorId = 1;
        $this->doctor = 'Dr. Test';
        $this->service = 'General';
        $this->scheduleDay = 'Monday';
        $this->scheduleSuggestion = 'Monday morning';
        $this->sendCopy = $sendCopy;
    }

    public function submitWith(BotMan $bot): void
    {
        $this->setBot($bot);
        $this->submitAppointment();
    }

    protected function sayLogged($message) { $this->messages[] = $message; }
    protected function askPostAppointment() { $this->postPromptShown = true; }
    protected function getNextAvailableISODate($dayStr) { return now()->addWeek()->toDateString(); }
}

class PatientBookingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        foreach (['patients', 'doctors', 'schedule_visit', 'app_notifications'] as $table) {
            (require database_path("migrations/2026_01_01_000001_create_{$table}_table.php"))->up();
        }
        DB::table('doctors')->insert([
            ['doctor_id' => 1, 'doctor_name' => 'Dr. Test', 'specialty' => 'General'],
            ['doctor_id' => 2, 'doctor_name' => 'Dr. Second', 'specialty' => 'General'],
        ]);
        Mail::fake();
        $this->beforeApplicationDestroyed(function () {
            Patient::flushEventListeners();
            ScheduleVisit::flushEventListeners();
        });
    }

    private function patient(): array
    {
        return ['patient_fname' => 'Ana Maria', 'patient_lname' => 'Reyes', 'patient_birthdate' => '2000-06-12',
            'patient_email' => 'ana@example.test', 'patient_contact' => '09171234567'];
    }

    private function payload(): array
    {
        return ['patient' => $this->patient(), 'doctor_id' => 1, 'service_type' => 'General',
            'visit_date' => now()->addWeek()->toDateString(), 'notes' => 'Routine checkup'];
    }

    public function test_repeat_bookings_create_two_visits_for_one_patient_even_with_different_doctors(): void
    {
        $payload = $this->payload();
        $first = $this->postJson('/api/schedule-visit', $payload)->assertCreated()->json();
        $payload['doctor_id'] = 2;
        $second = $this->postJson('/api/schedule-visit', $payload)->assertCreated()->json();
        $this->assertSame($first['patient_id'], $second['patient_id']);
        $this->assertNotSame($first['visit_id'], $second['visit_id']);
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseCount('schedule_visit', 2);
    }

    public function test_case_and_outer_whitespace_do_not_create_a_duplicate_or_overwrite_the_existing_record(): void
    {
        $original = $this->patient();
        $original['patient_fname'] = ' ANA MARIA ';
        $original['patient_email'] = ' ANA@EXAMPLE.TEST ';
        $original['patient_contact'] = ' 09171234567 ';
        $patient = Patient::create($original);
        $this->postJson('/api/schedule-visit', $this->payload())->assertCreated()->assertJsonPath('patient_id', $patient->patient_id);
        $this->assertDatabaseCount('patients', 1);
        $this->assertSame($original['patient_fname'], $patient->fresh()->patient_fname);
        $this->assertSame($original['patient_email'], $patient->fresh()->patient_email);
    }

    public static function differentIdentityFields(): array
    {
        return [['patient_fname', 'Other Ana'], ['patient_lname', 'Santos'], ['patient_birthdate', '2000-06-13'],
            ['patient_email', 'other@example.test'], ['patient_contact', '09179876543']];
    }

    #[DataProvider('differentIdentityFields')]
    public function test_partial_matches_are_kept_as_separate_patients(string $field, string $value): void
    {
        $existing = Patient::create($this->patient());
        $payload = $this->payload();
        $payload['patient'][$field] = $value;
        $new = $this->postJson('/api/schedule-visit', $payload)->assertCreated()->json('patient_id');
        $this->assertNotSame($existing->patient_id, $new);
        $this->assertDatabaseCount('patients', 2);
        $this->assertDatabaseCount('schedule_visit', 1);
    }

    public function test_accents_are_not_removed_when_matching_names(): void
    {
        Patient::create(array_merge($this->patient(), ['patient_fname' => 'José']));
        $payload = $this->payload();
        $payload['patient']['patient_fname'] = 'Jose';
        $this->postJson('/api/schedule-visit', $payload)->assertCreated();
        $this->assertDatabaseCount('patients', 2);
    }

    public function test_multiple_exact_matches_require_review_instead_of_choosing_or_creating_a_patient(): void
    {
        Patient::create($this->patient());
        Patient::create($this->patient());
        $this->postJson('/api/schedule-visit', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('patient');
        $this->assertDatabaseCount('patients', 2);
        $this->assertDatabaseCount('schedule_visit', 0);
    }

    public function test_visit_failure_rolls_back_a_new_patient(): void
    {
        ScheduleVisit::creating(fn () => throw new \RuntimeException('Simulated visit insert failure'));
        $this->postJson('/api/schedule-visit', $this->payload())->assertStatus(500);
        $this->assertDatabaseCount('patients', 0);
        $this->assertDatabaseCount('schedule_visit', 0);
    }

    public function test_failed_visit_does_not_modify_or_delete_an_existing_patient(): void
    {
        $patient = Patient::create($this->patient());
        ScheduleVisit::creating(fn () => throw new \RuntimeException('Simulated visit insert failure'));
        $this->postJson('/api/schedule-visit', $this->payload())->assertStatus(500);
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseCount('schedule_visit', 0);
        $this->assertSame($this->patient(), $patient->fresh()->only(array_keys($this->patient())));
    }

    public function test_cancelled_patient_or_visit_insert_cannot_leave_partial_records(): void
    {
        Patient::creating(fn () => false);
        $this->postJson('/api/schedule-visit', $this->payload())->assertStatus(500);
        Patient::flushEventListeners();
        ScheduleVisit::creating(fn () => false);
        $this->postJson('/api/schedule-visit', $this->payload())->assertStatus(500);
        $this->assertDatabaseCount('patients', 0);
        $this->assertDatabaseCount('schedule_visit', 0);
    }

    private function bot(array &$stored, bool $expectSave = true): BotMan
    {
        $storage = \Mockery::mock();
        if ($expectSave) {
            $storage->shouldReceive('save')->once()->andReturnUsing(function ($data) use (&$stored) { $stored = $data; });
        } else {
            $storage->shouldNotReceive('save');
        }
        $bot = \Mockery::mock(BotMan::class);
        $bot->shouldReceive('userStorage')->andReturn($storage);
        return $bot;
    }

    public function test_chatbot_and_api_share_the_same_patient_and_keep_confirmation_and_email_behavior(): void
    {
        $first = $this->postJson('/api/schedule-visit', $this->payload())->assertCreated()->json();
        $conversation = new BookingConversationProbe($this->patient());
        $stored = [];
        $conversation->submitWith($this->bot($stored));
        $this->assertSame($first['patient_id'], $stored['patient_id']);
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseCount('schedule_visit', 2);
        $this->assertDatabaseCount('app_notifications', 1);
        $this->assertTrue($conversation->postPromptShown);
        $this->assertStringStartsWith('APPT_CARD::', $conversation->messages[0]);
        Mail::assertSent(VisitConfirmationMail::class, fn ($mail) => $mail->hasTo('ana@example.test'));
    }

    public function test_chatbot_visit_failure_rolls_back_patient_and_does_not_confirm_or_email(): void
    {
        ScheduleVisit::creating(fn () => throw new \RuntimeException('Simulated visit insert failure'));
        $conversation = new BookingConversationProbe($this->patient());
        $stored = [];
        $conversation->submitWith($this->bot($stored, false));
        $this->assertDatabaseCount('patients', 0);
        $this->assertDatabaseCount('schedule_visit', 0);
        $this->assertDatabaseCount('app_notifications', 0);
        $this->assertFalse($conversation->postPromptShown);
        $this->assertStringContainsString("couldn't save", $conversation->messages[0]);
        Mail::assertNothingSent();
    }

    public function test_chatbot_reports_ambiguous_patients_without_changing_any_records(): void
    {
        Patient::create($this->patient());
        Patient::create($this->patient());
        $conversation = new BookingConversationProbe($this->patient());
        $stored = [];
        $conversation->submitWith($this->bot($stored, false));
        $this->assertStringContainsString('Multiple patient records', $conversation->messages[0]);
        $this->assertDatabaseCount('patients', 2);
        $this->assertDatabaseCount('schedule_visit', 0);
        Mail::assertNothingSent();
    }

    public function test_chatbot_email_failure_does_not_undo_the_committed_patient_or_visit(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Simulated mail failure'));
        $conversation = new BookingConversationProbe($this->patient());
        $stored = [];
        $conversation->submitWith($this->bot($stored));
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseCount('schedule_visit', 1);
        $this->assertTrue($conversation->postPromptShown);
        $this->assertStringContainsString('Your visit is still saved', implode(' ', $conversation->messages));
    }

    private function mysqlLockConnection()
    {
        $connection = \Mockery::mock(DB::connection());
        $connection->shouldReceive('getDriverName')->andReturn('mysql');
        $manager = \Mockery::mock(DB::getFacadeRoot());
        $manager->shouldReceive('connection')->andReturn($connection);
        DB::swap($manager);
        return $connection;
    }

    public function test_mysql_lock_is_shared_by_the_same_identity_across_doctors_and_released_after_each_save(): void
    {
        $connection = $this->mysqlLockConnection();
        $acquired = $released = [];
        $connection->shouldReceive('selectOne')->with('SELECT GET_LOCK(?, 5) AS acquired', \Mockery::any(), false)->twice()
            ->andReturnUsing(function ($sql, $bindings) use (&$acquired) { $acquired[] = $bindings[0]; return (object) ['acquired' => 1]; });
        $connection->shouldReceive('selectOne')->with('SELECT RELEASE_LOCK(?) AS released', \Mockery::any(), false)->twice()
            ->andReturnUsing(function ($sql, $bindings) use (&$released) { $released[] = $bindings[0]; return (object) ['released' => 1]; });
        $service = app(PatientBookingService::class);
        $data = $this->payload();
        $service->book($data['patient'], $data);
        $data['patient']['patient_fname'] = 'ANA MARIA';
        $data['doctor_id'] = 2;
        $service->book($data['patient'], $data);
        $this->assertSame($acquired[0], $acquired[1]);
        $this->assertSame($acquired, $released);
        $this->assertSame(64, strlen($acquired[0]));
        $this->assertStringNotContainsString('ana@example.test', $acquired[0]);
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseCount('schedule_visit', 2);
    }

    public function test_mysql_lock_is_released_when_the_visit_insert_fails(): void
    {
        $connection = $this->mysqlLockConnection();
        $connection->shouldReceive('selectOne')->with('SELECT GET_LOCK(?, 5) AS acquired', \Mockery::any(), false)->once()->andReturn((object) ['acquired' => 1]);
        $connection->shouldReceive('selectOne')->with('SELECT RELEASE_LOCK(?) AS released', \Mockery::any(), false)->once()->andReturn((object) ['released' => 1]);
        ScheduleVisit::creating(fn () => throw new \RuntimeException('Simulated visit insert failure'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Simulated visit insert failure');
        try {
            $data = $this->payload();
            app(PatientBookingService::class)->book($data['patient'], $data);
        } finally {
            $this->assertDatabaseCount('patients', 0);
            $this->assertDatabaseCount('schedule_visit', 0);
        }
    }

    public function test_mysql_lock_timeout_does_not_create_records_or_release_another_requests_lock(): void
    {
        $connection = $this->mysqlLockConnection();
        $connection->shouldReceive('selectOne')->with('SELECT GET_LOCK(?, 5) AS acquired', \Mockery::any(), false)->once()->andReturn((object) ['acquired' => 0]);
        $connection->shouldReceive('selectOne')->with('SELECT RELEASE_LOCK(?) AS released', \Mockery::any(), false)->never();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Booking is busy');
        try {
            $data = $this->payload();
            app(PatientBookingService::class)->book($data['patient'], $data);
        } finally {
            $this->assertDatabaseCount('patients', 0);
            $this->assertDatabaseCount('schedule_visit', 0);
        }
    }

    public function test_lock_cleanup_failure_does_not_report_a_committed_booking_as_failed(): void
    {
        $connection = $this->mysqlLockConnection();
        $connection->shouldReceive('selectOne')->with('SELECT GET_LOCK(?, 5) AS acquired', \Mockery::any(), false)->once()->andReturn((object) ['acquired' => 1]);
        $connection->shouldReceive('selectOne')->with('SELECT RELEASE_LOCK(?) AS released', \Mockery::any(), false)->once()->andThrow(new \RuntimeException('Connection lost after commit'));
        $data = $this->payload();
        $booking = app(PatientBookingService::class)->book($data['patient'], $data);
        $this->assertTrue($booking['visit']->exists);
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseCount('schedule_visit', 1);
    }
}
