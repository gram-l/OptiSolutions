<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\ScheduleVisit;
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

                $patient = $matches->first() ?? Patient::create($patientData);
                if (!$patient->exists) {
                    throw new \RuntimeException('The patient could not be saved.');
                }
                $visit = ScheduleVisit::create(array_merge($visitData, ['patient_id' => $patient->patient_id]));

                // Model events can cancel an insert without throwing.
                if (!$visit->exists) {
                    throw new \RuntimeException('The patient and visit could not both be saved.');
                }

                return ['patient' => $patient, 'visit' => $visit];
            }, 3);
        } finally {
            if ($lock !== null) {
                try {
                    $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock], false);
                } catch (\Throwable $exception) {
                    try {
                        Log::warning('Could not release the patient booking lock.');
                    } catch (\Throwable $logException) {
                        // A cleanup error must not turn a committed visit into a failure.
                    }
                }
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
