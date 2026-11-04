<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Shared visibility rules for doctors and services.
 *
 * Used by the Medical Team and Services pages through /api/doctors and
 * /api/services, and by the chatbot for listing, searching, booking,
 * and specialty matching.
 */
class PublicCatalog
{
    /** Doctors eligible for display on the website and booking through the chatbot. */
    public static function doctors(): Builder
    {
        return DB::table('doctors')->where('doctors.available', 1);
    }

    /** Services eligible for display on the website and use in chatbot bookings. */
    public static function services(): Builder
    {
        return DB::table('services')->where('services.available', 1);
    }

    /**
     * Return visible services grouped by doctor ID.
     * The website uses these groups for its filter tabs, and the chatbot
     * uses them to match doctors to specialties.
     */
    public static function servicesForDoctors(array $doctorIds)
    {
        if (empty($doctorIds)) {
            return collect();
        }

        return DB::table('doctor_service')
            ->join('services', 'services.service_id', '=', 'doctor_service.service_id')
            ->where('services.available', 1)
            ->whereIn('doctor_service.doctor_id', $doctorIds)
            ->orderBy('services.title')
            ->get(['doctor_service.doctor_id', 'services.service_id', 'services.service_key', 'services.title'])
            ->groupBy('doctor_id');
    }

    /** Return visible doctors linked to the given service through doctor_service. */
    public static function doctorsForService(int $serviceId): Builder
    {
        return self::doctors()->whereIn('doctors.doctor_id', function ($q) use ($serviceId) {
            $q->select('doctor_id')->from('doctor_service')->where('service_id', $serviceId);
        });
    }
}