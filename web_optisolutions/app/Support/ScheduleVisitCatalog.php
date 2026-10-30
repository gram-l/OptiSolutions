<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

// Provides the available schedule visit options for the chatbot.
class ScheduleVisitCatalog
{
    public static function build(): array
    {
        $services  = PublicCatalog::services()->get();
        $doctors   = PublicCatalog::doctors()->get();
        $schedules = $doctors->isEmpty()
            ? collect()
            : DB::table('doctor_schedules')
                ->whereIn('doctor_id', $doctors->pluck('doctor_id')->all())
                ->orderBy('doctor_id')->orderBy('schedule_id')
                ->get()->groupBy('doctor_id');

        $serviceOptions = $doctorOptions = $slotOptions = [];

        foreach ($services as $svc) {
            $hasDoctor = false;

            foreach ($doctors as $d) {
                if (mb_strtolower(trim((string) $d->specialty)) !== mb_strtolower(trim((string) $svc->title))) {
                    continue;
                }

                $docSlots = $schedules->get($d->doctor_id, collect());
                if ($docSlots->isEmpty()) {
                    continue;
                }

                $hasDoctor = true;
                $doctorOptions[] = ['value' => (string) $d->doctor_id, 'label' => $d->doctor_name, 'parent' => $svc->service_key];

                foreach ($docSlots as $slot) {
                    $slotOptions[] = ['value' => (string) $slot->schedule_id, 'label' => self::slotLabel($slot), 'parent' => (string) $d->doctor_id];
                }
            }

            if ($hasDoctor) {
                $serviceOptions[] = ['value' => $svc->service_key, 'label' => $svc->title];
            }
        }

        return ['services' => $serviceOptions, 'doctors' => $doctorOptions, 'slots' => $slotOptions];
    }

    // "Monday, October 30, 2026 · 9AM - 12PM"
    protected static function slotLabel($slot): string
    {
        return self::nextDate($slot->day) . ' · ' . date('gA', strtotime($slot->start_time)) . ' - ' . date('gA', strtotime($slot->end_time));
    }

    protected static function nextDate(string $dayStr): string
    {
        $dayMap = ['Sunday'=>0,'Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6];
        $target = trim(explode('-', explode(',', $dayStr)[0])[0]);
        $targetIndex = $dayMap[$target] ?? null;
        $now = new \DateTime();
        if ($targetIndex === null) {
            $now->modify('+1 day');
        } else {
            $daysUntil = ($targetIndex - (int) $now->format('w') + 7) % 7;
            if ($daysUntil === 0) $daysUntil = 7;
            $now->modify("+{$daysUntil} days");
        }
        return $now->format('l, F j, Y');
    }
}