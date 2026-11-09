<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Support\PublicCatalog;

class ServiceController extends Controller
{
    // GET /api/services — used by the patient-facing website
    public function index()
    {
        $services = PublicCatalog::services()
            ->orderBy('service_id')
            ->get();

        $conditions = DB::table('service_conditions')->get()->groupBy('service_id');

        $result = $services->map(function ($svc) use ($conditions) {
            $doctorNames = PublicCatalog::doctorsForService((int) $svc->service_id)
                ->orderBy('doctor_name')
                ->pluck('doctor_name');

            $card = \App\Support\ServiceCardStyle::for($svc);

            return [
                'card_image'  => $card['image'],
                'card_color'  => $card['color'],
                'card_icon'   => $card['icon'],
                'service_id'  => $svc->service_id,
                'service_key' => $svc->service_key,
                'title'       => $svc->title,
                'icon'        => $svc->icon,
                'description' => $svc->description,
                'room'        => $svc->room,
                'schedule'    => trim((string) $svc->schedule) !== ''
                                    ? $svc->schedule
                                    : $this->scheduleFromDoctors((int) $svc->service_id),
                'available'   => $svc->available,
                'conditions'  => $conditions->get($svc->service_id, collect())
                                    ->pluck('condition_name')->values(),
                'doctors'     => $doctorNames->values(),
            ];
        });

        return response()->json($result);
    }

    // Services added in the admin have no stored schedule, so show the visiting
    // days/times of the doctors linked to the service instead.
    private function scheduleFromDoctors(int $serviceId): ?string
    {
        $doctorIds = PublicCatalog::doctorsForService($serviceId)->pluck('doctors.doctor_id');
        if ($doctorIds->isEmpty()) {
            return null;
        }

        $order = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
        $fmt = fn ($t) => \Carbon\Carbon::parse($t)->format('g:i A');

        $parts = DB::table('doctor_schedules')
            ->whereIn('doctor_id', $doctorIds)
            ->whereNotNull('start_time')->whereNotNull('end_time')
            ->get(['day', 'start_time', 'end_time'])
            ->sortBy(fn ($r) => ($order[$r->day] ?? 9).'-'.$r->start_time)
            ->map(fn ($r) => substr($r->day, 0, 3).' '.$fmt($r->start_time).' - '.$fmt($r->end_time))
            ->unique()
            ->values();

        return $parts->isEmpty() ? null : $parts->implode(' | ');
    }
}