<?php
// app/Http/Controllers/Api/AppointmentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff\Appointment;

class AppointmentController extends Controller
{
    /** GET /api/appointments */
    public function index()
    {
        return Appointment::all()->map->toApiArray();
    }

    /** GET /api/appointments/{appointment} */
    public function show(Appointment $appointment)
    {
        return $appointment->toApiArray();
    }
}