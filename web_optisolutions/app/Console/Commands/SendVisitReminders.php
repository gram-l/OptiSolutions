<?php

namespace App\Console\Commands;

use App\Mail\VisitReminderMail;
use App\Support\ScheduleVisitCapacity;
use App\Support\VisitAttendance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails each patient once, when their visit starts in 30 minutes or less
 * (config clinic.reminder_minutes), with a button to confirm they are coming.
 * Runs every minute from the scheduler (routes/console.php).
 */
class SendVisitReminders extends Command
{
    protected $signature = 'visits:send-reminders {--dry-run : List who would be emailed without sending}';
    protected $description = 'Email patients a reminder shortly before their visit, with a button to confirm attendance.';

    public function handle(): int
    {
        if (!VisitAttendance::hasColumns() || !ScheduleVisitCapacity::hasStatusColumn()) {
            $this->warn('Run the migrations first (php artisan migrate).');
            return self::SUCCESS;
        }

        $now = Carbon::now(VisitAttendance::TZ);
        $until = $now->copy()->addMinutes(VisitAttendance::reminderMinutes());

        $visits = DB::table('schedule_visit as v')
            ->join('patients as p', 'p.patient_id', '=', 'v.patient_id')
            ->leftJoin('doctors as d', 'd.doctor_id', '=', 'v.doctor_id')
            ->whereIn('v.visit_date', [$now->toDateString(), $until->toDateString()])
            ->whereNotNull('v.visit_time')
            ->whereNull('v.reminder_sent_at')
            ->where('v.status', '!=', 'Cancelled')
            ->where(fn ($q) => $q->whereNull('v.visited')->orWhere('v.visited', 0))
            ->get([
                'v.visit_id', 'v.visit_date', 'v.visit_time', 'v.visit_end_time', 'v.service_type',
                'p.patient_fname', 'p.patient_lname', 'p.patient_email', 'd.doctor_name',
            ]);

        $sent = 0;
        foreach ($visits as $visit) {
            $start = VisitAttendance::startsAt($visit);
            $end = VisitAttendance::endsAt($visit) ?? $start;
            if (!$start || $start->lt($now) || $start->gt($until)) {
                continue; // not in the reminder window (already started, or too early)
            }

            $email = trim((string) $visit->patient_email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Visit reminder skipped: patient has no valid email.', ['visit_id' => $visit->visit_id]);
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("Would email visit #{$visit->visit_id} ({$email}), starts {$start->format('Y-m-d g:i A')}");
                continue;
            }

            // Claim the visit first so two overlapping runs never email the same patient twice.
            $claimed = DB::table('schedule_visit')
                ->where('visit_id', $visit->visit_id)
                ->whereNull('reminder_sent_at')
                ->update(['reminder_sent_at' => $now]);
            if (!$claimed) {
                continue;
            }

            try {
                Mail::to($email)->send(new VisitReminderMail(
                    trim($visit->patient_fname . ' ' . $visit->patient_lname),
                    (string) $visit->service_type,
                    (string) ($visit->doctor_name ?? ''),
                    $start->format('l, F j, Y') . ' at ' . $start->format('g:i A') . ($end && $end->gt($start) ? ' - ' . $end->format('g:i A') : ''),
                    (int) $visit->visit_id,
                    VisitAttendance::url((int) $visit->visit_id, ($end ?? $start)->copy()->addHour())
                ));
                $sent++;
            } catch (\Throwable $e) {
                // Release the claim so the next minute retries until the visit starts.
                DB::table('schedule_visit')->where('visit_id', $visit->visit_id)->update(['reminder_sent_at' => null]);
                Log::error('Visit reminder email failed.', ['visit_id' => $visit->visit_id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Visit reminders sent: {$sent}");
        return self::SUCCESS;
    }
}