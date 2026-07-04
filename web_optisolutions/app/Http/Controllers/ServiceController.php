<?php

namespace App\Http\Controllers;

use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with(['conditions', 'doctorsList'])
            ->where('available', 1)
            ->get();

        $result = $services->map(function ($svc) {
            return [
                'service_id'  => $svc->service_id,
                'service_key' => $svc->service_key,
                'title'       => $svc->title,
                'icon'        => $svc->icon,
                'description' => $svc->description,
                'room'        => $svc->room,
                'schedule'    => $svc->schedule,
                'available'   => $svc->available,
                'conditions'  => $svc->conditions->pluck('condition_name'),
                'doctors'     => $svc->doctorsList->pluck('doctor_name'),
            ];
        });

        return response()->json($result);
    }
}