<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\ScheduleVisit;
use App\Support\ScheduleVisitCapacity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PatientBookingService
{
    public function book(array $patientData, array $visitData): array
    {
        $patientData = array_map(fn ($value) => trim((string) $value), $patientData);
        $patientData['patient_birthdate'] = Carbon::parse($patientData['patient_birthdate'])->toDateString();
        $identity = $this->identity($patientData);
        $connection = DB::connection();
        $lock = null;

        // Serialize identical public bookings on MySQL, including requests for
        // different doctors. No new table or unsafe unique patient constraint.
        if ($connection->getDriverName() === 'mysql') {
            $lock = 'patient-booking:'.substr(hash('sha256', json_encode([$connection->getDatabaseName(), $identity], JSON_THROW_ON_ERROR)), 0, 48);
            $result = $connection->selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$lock], false);
            abort_unless((int) ($result->acquired ?? 0) === 1, 503, 'Booking is busy. Please try again shortly.');
        }

        // Serialize bookings for the same doctor/date/time so two patients cannot
        // take the last seat at once (MySQL 8 allows holding both named locks).
        $slotLock = null;
        if ($connection->getDriverName() === 'mysql' && !empty($visitData['visit_time'])) {
            $slotLock = 'schedule-slot:'.substr(hash('sha256', json_encode([
                $connection->getDatabaseName(), $visitData['doctor_id'] ?? null, $visitData['visit_date'] ?? null, $visitData['visit_time'],
            ], JSON_THROW_ON_ERROR)), 0, 48);
            $got = $connection->selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$slotLock], false);
            if ((int) ($got->acquired ?? 0) !== 1) {
                $this->releaseLock($connection, $lock);
                abort(503, 'Booking is busy. Please try again shortly.');
            }
        }

        try {
            return $connection->transaction(function () use ($patientData, $visitData, $identity) {
                // Compare the complete identity in PHP so database collations
                // cannot treat distinct accented names as the same person.
                $matches = Patient::whereDate('patient_birthdate', $patientData['patient_birthdate'])
                    ->whereRaw('TRIM(patient_contact) = ?', [$patientData['patient_contact']])
                    ->get()->filter(fn ($patient) => $this->identity($patient->getAttributes()) === $identity);

                if ($matches->count() > 1) {
                    throw ValidationException::withMessages([
                        'patient' => 'Multiple patient records match these details. Please contact the clinic to confirm the correct record.',
                    ]);
                }

                $existing = $matches->first();
                $this->assertWithinLimits($existing, $visitData);

                $patient = $existing ?? Patient::create($patientData);
                if (!$patient->exists) {
                    throw new \RuntimeException('The patient could not be saved.');
                }

                // Bookings are confirmed right away (staff no longer confirm them).
                // The patient confirms attendance later through the reminder email.
                if (ScheduleVisitCapacity::hasStatusColumn()) {
                    $visitData['status'] = 'Confirmed';
                }
                $visit = ScheduleVisit::create(array_merge($visitData, ['patient_id' => $patient->patient_id]));

                // Model events can cancel an insert without throwing.
                if (!$visit->exists) {
                    throw new \RuntimeException('The patient and visit could not both be saved.');
                }

                return ['patient' => $patient, 'visit' => $visit];
            }, 3);
        } finally {
            $this->releaseLock($connection, $slotLock);
            $this->releaseLock($connection, $lock);
        }
    }

    /**
     * Rejects a booking when the window is full or the patient is over the daily
     * limit. Runs inside the booking locks and the transaction.
     */
    private function assertWithinLimits(?Patient $patient, array $visitData): void
    {
        if (empty($visitData['visit_time']) || empty($visitData['visit_end_time']) || empty($visitData['visit_date'])) {
            return;
        }

        $slot = (object) [
            'schedule_id' => 0,
            'doctor_id'   => $visitData['doctor_id'],
            'start_time'  => $visitData['visit_time'],
            'end_time'    => $visitData['visit_end_time'],
        ];

        if ($patient) {
            $mine = ScheduleVisitCapacity::patientVisitsOn((int) $patient->patient_id, $visitData['visit_date']);

            if ($mine->contains(fn ($v) => (int) $v->doctor_id === (int) $visitData['doctor_id']
                && ScheduleVisitCapacity::matches($v, $slot))) {
                throw ValidationException::withMessages([
                    'limit' => 'You already have a schedule visit with this doctor at this date and time.',
                ]);
            }

            $max = ScheduleVisitCapacity::maxVisitsPerPatientPerDay();
            if ($mine->count() >= $max) {
                throw ValidationException::withMessages([
                    'limit' => "You already have {$max} schedule visits on this date, which is the most we can accept per day. Please choose another date.",
                ]);
            }
        }

        if (ScheduleVisitCapacity::remainingForSlot($slot, $visitData['visit_date']) < 1) {
            throw ValidationException::withMessages([
                'slot' => 'Sorry, that schedule just became full. Please choose another date or time.',
            ]);
        }
    }

    private function releaseLock($connection, ?string $lock): void
    {
        if ($lock === null) {
            return;
        }
        try {
            $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock], false);
        } catch (\Throwable $exception) {
            try {
                Log::warning('Could not release a booking lock.');
            } catch (\Throwable $logException) {
                // A cleanup error must not turn a committed visit into a failure.
            }
        }
    }

    private function identity(array $patient): array
    {
        return [
            mb_strtolower(trim((string) ($patient['patient_fname'] ?? ''))),
            mb_strtolower(trim((string) ($patient['patient_lname'] ?? ''))),
            (string) ($patient['patient_birthdate'] ?? ''),
            mb_strtolower(trim((string) ($patient['patient_email'] ?? ''))),
            trim((string) ($patient['patient_contact'] ?? '')),
        ];
    }
}