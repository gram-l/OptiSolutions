<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

/**
 * Helpers for the "please confirm you are coming" reminder email.
 * Replies are stored on schedule_visit.attendance_response.
 */
class VisitAttendance
{
    public const COMING = 'confirmed';
    public const DECLINED = 'declined';
    public const TZ = 'Asia/Manila';

    private static ?bool $hasColumns = null;

    /** False until the attendance/reminder migration has been run. */
    public static function hasColumns(): bool
    {
        return self::$hasColumns ??= Schema::hasColumn('schedule_visit', 'attendance_response')
            && Schema::hasColumn('schedule_visit', 'reminder_sent_at')
            && Schema::hasColumn('schedule_visit', 'attendance_responded_at');
    }

    /** Fields that clear the reminder and the reply (used when a visit is rescheduled). */
    public static function resetFields(): array
    {
        return self::hasColumns()
            ? ['reminder_sent_at' => null, 'attendance_response' => null, 'attendance_responded_at' => null]
            : [];
    }

    public static function reminderMinutes(): int
    {
        return max(5, (int) config('clinic.reminder_minutes', 30));
    }

    public static function startsAt(object $visit): ?Carbon
    {
        return self::at($visit, $visit->visit_time ?? null);
    }

    public static function endsAt(object $visit): ?Carbon
    {
        return self::at($visit, $visit->visit_end_time ?? null);
    }

    private static function at(object $visit, ?string $time): ?Carbon
    {
        if (empty($visit->visit_date) || empty($time)) {
            return null;
        }

        return Carbon::parse(Carbon::parse($visit->visit_date)->toDateString() . ' ' . $time, self::TZ);
    }

    /** Signed link for the patient's confirmation page. It stops working at the end of the visit window. */
    public static function url(int $visitId, Carbon $expires): string
    {
        return URL::temporarySignedRoute('visit.attendance.show', $expires, ['id' => $visitId]);
    }
}