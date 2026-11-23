<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How many patients a doctor's schedule window can accept, and how many are
 * already booked.
 *
 * Capacity = hours in the window x clinic.max_patients_per_hour (at least 1).
 * Pending and Confirmed visits hold a slot; Cancelled visits release it.
 *
 * A visit belongs to a window when doctor, date and visit_time match. Older
 * chatbot rows saved no visit_time, so they are matched by the " at 8AM - 5PM"
 * text the chatbot wrote into notes.
 */
class ScheduleVisitCapacity
{
    private static ?bool $hasStatus = null;

    public static function perHour(): int
    {
        return max(1, (int) config('clinic.max_patients_per_hour', 2));
    }

    public static function maxVisitsPerPatientPerDay(): int
    {
        return max(1, (int) config('clinic.max_visits_per_patient_per_day', 2));
    }

    /** False until the status migration has been run on this database. */
    public static function hasStatusColumn(): bool
    {
        return self::$hasStatus ??= Schema::hasColumn('schedule_visit', 'status');
    }

    public static function forgetStatusColumnCache(): void
    {
        self::$hasStatus = null;
    }

    public static function capacity(?string $start, ?string $end): int
    {
        $seconds = strtotime((string) $end) - strtotime((string) $start);
        $hours = $seconds > 0 ? $seconds / 3600 : 1;

        return max(1, (int) ceil($hours * self::perHour()));
    }

    /** Applies "not cancelled" when the status column exists. */
    public static function active($query)
    {
        return self::hasStatusColumn() ? $query->where('status', '!=', 'Cancelled') : $query;
    }

    /**
     * Seats left for each schedule row on its date.
     *
     * @param Collection $slots rows with schedule_id, doctor_id, start_time, end_time
     * @param array      $dates schedule_id => 'Y-m-d' date of that slot
     * @return array<int,int> schedule_id => seats left (never below 0)
     */
    public static function remainingForSlots(Collection $slots, array $dates): array
    {
        if ($slots->isEmpty()) {
            return [];
        }

        $visits = self::active(DB::table('schedule_visit'))
            ->whereIn('doctor_id', $slots->pluck('doctor_id')->unique()->all())
            ->whereIn('visit_date', array_values(array_unique($dates)))
            ->get(['doctor_id', 'visit_date', 'visit_time', 'notes'])
            ->groupBy(fn ($v) => $v->doctor_id . '|' . $v->visit_date);

        $left = [];
        foreach ($slots as $slot) {
            $key = $slot->doctor_id . '|' . ($dates[$slot->schedule_id] ?? '');
            $booked = $visits->get($key, collect())->filter(fn ($v) => self::matches($v, $slot))->count();
            $left[(int) $slot->schedule_id] = max(0, self::capacity($slot->start_time, $slot->end_time) - $booked);
        }

        return $left;
    }

    public static function remainingForSlot(object $slot, string $date): int
    {
        return self::remainingForSlots(collect([$slot]), [$slot->schedule_id => $date])[(int) $slot->schedule_id] ?? 0;
    }

    /** Active schedule visits this patient already has on a date. */
    public static function patientVisitsOn(int $patientId, string $date)
    {
        return self::active(DB::table('schedule_visit'))
            ->where('patient_id', $patientId)
            ->where('visit_date', $date)
            ->get(['visit_id', 'doctor_id', 'visit_time', 'notes']);
    }

    public static function matches(object $visit, object $slot): bool
    {
        if (!empty($visit->visit_time)) {
            return substr((string) $visit->visit_time, 0, 8) === substr((string) $slot->start_time, 0, 8);
        }

        $text = ' at ' . date('gA', strtotime($slot->start_time)) . ' - ' . date('gA', strtotime($slot->end_time));

        return str_contains((string) ($visit->notes ?? ''), $text);
    }

    /** "2 slots left" / "1 slot left" / "Full" */
    public static function label(int $left): string
    {
        return $left <= 0 ? 'Full' : $left . ($left === 1 ? ' slot left' : ' slots left');
    }
}