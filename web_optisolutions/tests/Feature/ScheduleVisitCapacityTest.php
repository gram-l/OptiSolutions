<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\ScheduleVisit;
use App\Services\PatientBookingService;
use App\Support\ScheduleVisitCapacity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ScheduleVisitCapacityTest extends TestCase
{
    private string $date;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'clinic.max_patients_per_hour' => 2, 'clinic.max_visits_per_patient_per_day' => 2,
        ]);
        foreach (['patients', 'doctors', 'doctor_schedules', 'schedule_visit'] as $table) {
            (require database_path("migrations/2026_01_01_000001_create_{$table}_table.php"))->up();
        }
        (require database_path('migrations/2026_10_14_000001_add_visit_times_to_schedule_visit.php'))->up();
        (require database_path('migrations/2026_11_18_000001_add_status_to_schedule_visit.php'))->up();
        ScheduleVisitCapacity::forgetStatusColumnCache();

        DB::table('doctors')->insert([
            ['doctor_id' => 1, 'doctor_name' => 'Dr. One', 'specialty' => 'General'],
            ['doctor_id' => 2, 'doctor_name' => 'Dr. Two', 'specialty' => 'General'],
            ['doctor_id' => 3, 'doctor_name' => 'Dr. Three', 'specialty' => 'General'],
        ]);
        // 1-hour window (2 seats) and a 9-hour window (18 seats).
        DB::table('doctor_schedules')->insert([
            ['schedule_id' => 1, 'doctor_id' => 1, 'day' => 'Monday', 'start_time' => '09:00:00', 'end_time' => '10:00:00'],
            ['schedule_id' => 2, 'doctor_id' => 2, 'day' => 'Monday', 'start_time' => '08:00:00', 'end_time' => '17:00:00'],
            ['schedule_id' => 3, 'doctor_id' => 3, 'day' => 'Monday', 'start_time' => '09:00:00', 'end_time' => '10:00:00'],
        ]);
        $this->date = now()->addWeek()->toDateString();
        $this->beforeApplicationDestroyed(function () {
            Patient::flushEventListeners();
            ScheduleVisit::flushEventListeners();
        });
    }

    private function patient(int $n): array
    {
        return ['patient_fname' => "Pat{$n}", 'patient_lname' => 'Cruz', 'patient_birthdate' => '1990-01-0' . $n,
            'patient_email' => "p{$n}@clinic.test", 'patient_contact' => '0917000000' . $n];
    }

    private function visit(int $doctor, string $start, string $end, ?string $date = null): array
    {
        return ['doctor_id' => $doctor, 'service_type' => 'General', 'visit_date' => $date ?? $this->date,
            'visit_time' => $start, 'visit_end_time' => $end, 'notes' => 'test'];
    }

    private function book(int $n, int $doctor = 1, string $start = '09:00:00', string $end = '10:00:00', ?string $date = null): array
    {
        return app(PatientBookingService::class)->book($this->patient($n), $this->visit($doctor, $start, $end, $date));
    }

    public function test_capacity_is_hours_times_patients_per_hour(): void
    {
        $this->assertSame(2, ScheduleVisitCapacity::capacity('09:00:00', '10:00:00'));
        $this->assertSame(4, ScheduleVisitCapacity::capacity('10:00:00', '12:00:00'));
        $this->assertSame(18, ScheduleVisitCapacity::capacity('08:00:00', '17:00:00'));
        config(['clinic.max_patients_per_hour' => 3]);
        $this->assertSame(3, ScheduleVisitCapacity::capacity('09:00:00', '10:00:00'));
    }

    public function test_new_requests_are_saved_as_pending(): void
    {
        $this->assertSame('Pending', $this->book(1)['visit']->fresh()->status);
    }

    public function test_a_full_window_rejects_the_next_patient(): void
    {
        $this->book(1);
        $this->book(2);

        try {
            $this->book(3);
            $this->fail('The third patient should not fit in a 2-seat window.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('slot', $e->errors());
        }
        $this->assertDatabaseCount('schedule_visit', 2);
        $this->assertDatabaseCount('patients', 2);
    }

    public function test_other_dates_and_other_doctors_are_not_affected(): void
    {
        $this->book(1);
        $this->book(2);
        $this->book(3, 3); // same time, other doctor
        $this->book(4, 1, '09:00:00', '10:00:00', now()->addWeeks(2)->toDateString()); // same slot, other date
        $this->assertDatabaseCount('schedule_visit', 4);
    }

    public function test_cancelling_a_visit_releases_its_seat(): void
    {
        $first = $this->book(1);
        $this->book(2);
        DB::table('schedule_visit')->where('visit_id', $first['visit']->visit_id)->update(['status' => 'Cancelled']);

        $this->book(3);
        $this->assertDatabaseCount('schedule_visit', 3);
    }

    public function test_confirmed_visits_also_hold_their_seat(): void
    {
        $first = $this->book(1);
        $this->book(2);
        DB::table('schedule_visit')->where('visit_id', $first['visit']->visit_id)->update(['status' => 'Confirmed']);

        $this->expectException(ValidationException::class);
        $this->book(3);
    }

    public function test_older_rows_without_visit_time_are_matched_by_their_notes(): void
    {
        $patientId = DB::table('patients')->insertGetId($this->patient(9));
        foreach ([1, 2] as $i) {
            DB::table('schedule_visit')->insert([
                'doctor_id' => 1, 'patient_id' => $patientId, 'service_type' => 'General', 'visit_date' => $this->date,
                'notes' => 'Monday, January 5, 2026 at 9AM - 10AM', 'visit_time' => null, 'status' => 'Confirmed',
            ]);
        }
        $slot = DB::table('doctor_schedules')->where('schedule_id', 1)->first();
        $this->assertSame(0, ScheduleVisitCapacity::remainingForSlot($slot, $this->date));
    }

    public function test_a_patient_cannot_hold_more_than_two_visits_on_one_date(): void
    {
        $this->book(1, 1);
        $this->book(1, 2, '08:00:00', '17:00:00');

        try {
            $this->book(1, 3);
            $this->fail('A third visit on the same date should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('limit', $e->errors());
        }
        $this->assertDatabaseCount('schedule_visit', 2);
    }

    public function test_the_same_patient_cannot_book_the_same_doctor_and_time_twice(): void
    {
        $this->book(1);

        try {
            $this->book(1);
            $this->fail('A duplicate booking for the same slot should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('limit', $e->errors());
        }
        $this->assertDatabaseCount('schedule_visit', 1);
    }

    public function test_remaining_seats_and_labels(): void
    {
        $this->book(1);
        $slots = DB::table('doctor_schedules')->get();
        $dates = $slots->mapWithKeys(fn ($s) => [$s->schedule_id => $this->date])->all();
        $left = ScheduleVisitCapacity::remainingForSlots($slots, $dates);

        $this->assertSame([1 => 1, 2 => 18, 3 => 2], $left);
        $this->assertSame('1 slot left', ScheduleVisitCapacity::label(1));
        $this->assertSame('18 slots left', ScheduleVisitCapacity::label(18));
        $this->assertSame('Full', ScheduleVisitCapacity::label(0));
    }
}