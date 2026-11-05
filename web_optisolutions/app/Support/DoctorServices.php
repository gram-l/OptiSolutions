<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Manages the doctor <-> service relationship (pivot table `doctor_service`).
 * Used by Admin Doctors (saving) and by the Flutter API.
 */
class DoctorServices
{
    /** service_id[] for each doctor: [doctor_id => [service_id, ...]] */
    public static function idsForDoctors(array $doctorIds): array
    {
        if (empty($doctorIds)) return [];

        return DB::table('doctor_service')
            ->whereIn('doctor_id', $doctorIds)
            ->get()
            ->groupBy('doctor_id')
            ->map(fn ($rows) => $rows->pluck('service_id')->map(fn ($v) => (int) $v)->values()->all())
            ->all();
    }

    /** Replace a doctor's services with the given list. */
    public static function sync(int $doctorId, array $serviceIds): void
    {
        $serviceIds = array_values(array_unique(array_map('intval', $serviceIds)));

        DB::transaction(function () use ($doctorId, $serviceIds) {
            DB::table('doctor_service')->where('doctor_id', $doctorId)->delete();
            foreach ($serviceIds as $serviceId) {
                DB::table('doctor_service')->insert(['doctor_id' => $doctorId, 'service_id' => $serviceId]);
            }
        });
    }

    /** Attach service-derived display fields to query-builder results in one query. */
    public static function withLabels($rows, string $field = 'specialty')
    {
        $titles = DB::table('doctor_service')
            ->join('services', 'services.service_id', '=', 'doctor_service.service_id')
            ->whereIn('doctor_service.doctor_id', $rows->pluck('doctor_id')->filter()->unique()->all())
            ->orderBy('services.title')
            ->get(['doctor_service.doctor_id', 'services.title'])
            ->groupBy('doctor_id');

        return $rows->each(function ($row) use ($titles, $field) {
            $row->{$field} = $titles->get($row->doctor_id, collect())->pluck('title')->implode(' / ');
        });
    }

    /** Display text derived from services, without a database column or length limit. */
    public static function label(array $serviceIds): string
    {
        if (empty($serviceIds)) return '';

        $titles = DB::table('services')->whereIn('service_id', $serviceIds)->orderBy('title')->pluck('title')->all();

        return implode(' / ', $titles);
    }

    /**
     * For the legacy Flutter app that still sends a `specialty` text:
     * find the matching services. No match = returns nothing.
     */
    public static function idsFromSpecialtyText(?string $text): array
    {
        $text = trim((string) $text);
        if ($text === '') return [];

        $services = DB::table('services')->get(['service_id', 'service_key', 'title']);
        $find = function (string $needle) use ($services) {
            $needle = mb_strtolower(trim($needle));
            foreach ($services as $s) {
                if ($needle === mb_strtolower($s->title) || $needle === mb_strtolower($s->service_key)) {
                    return (int) $s->service_id;
                }
            }
            $extra = ['general medicine' => 'adultmedicine', 'adult medicine' => 'adultmedicine',
                      'pediatrician' => 'pediatrics', 'dermatologist' => 'dermatology'];
            if (isset($extra[$needle])) {
                foreach ($services as $s) {
                    if ($s->service_key === $extra[$needle]) return (int) $s->service_id;
                }
            }
            return null;
        };

        // Try the full text first (e.g. "General / Adult Medicine" is a single service)
        if (($id = $find($text)) !== null) return [$id];

        $ids = [];
        foreach (preg_split('/\s*[\/,]\s*/', $text) as $part) {
            if ($part !== '' && ($id = $find($part)) !== null) $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }
}
