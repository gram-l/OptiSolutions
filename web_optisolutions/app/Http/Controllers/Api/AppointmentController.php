<?php
// app/Http/Controllers/Api/AppointmentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff\ScheduleVisit;

class AppointmentController extends Controller
{
    /** GET /api/appointments */
    public function index()
    {
        $visits = ScheduleVisit::with(['doctor', 'patient'])
            ->orderBy('visit_date', 'desc')
            ->get();

        return response()->json($visits->map->toApiArray());
    }

    /** GET /api/appointments/{id} */
    public function show($id)
    {
        $visit = ScheduleVisit::with(['doctor', 'patient'])->findOrFail($id);

        return response()->json($visit->toApiArray());
    }
}