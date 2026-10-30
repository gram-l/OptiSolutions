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
            ->orderBy('title')
            ->get();

        $conditions = DB::table('service_conditions')->get()->groupBy('service_id');

        $result = $services->map(function ($svc) use ($conditions) {
            $doctorNames = PublicCatalog::doctors()
                ->where('specialty', $svc->title)
                ->pluck('doctor_name');

            return [
                'service_id'  => $svc->service_id,
                'service_key' => $svc->service_key,
                'title'       => $svc->title,
                'icon'        => $svc->icon,
                'description' => $svc->description,
                'room'        => $svc->room,
                'schedule'    => $svc->schedule,
                'available'   => $svc->available,
                'conditions'  => $conditions->get($svc->service_id, collect())
                                    ->pluck('condition_name')->values(),
                'doctors'     => $doctorNames->values(),
            ];
        });

        return response()->json($result);
    }
}