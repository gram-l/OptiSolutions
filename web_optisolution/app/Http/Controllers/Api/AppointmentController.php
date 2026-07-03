<?php
// app/Http/Controllers/Api/AppointmentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;

class AppointmentController extends Controller
{
    /** GET /api/appointments */
    public function index()
    {
        return Appointment::with(['patient', 'doctor'])->get()->map->toApiArray();
    }

    /** GET /api/appointments/{appointment} */
    public function show(Appointment $appointment)
    {
        return $appointment->load(['patient', 'doctor'])->toApiArray();
    }
}