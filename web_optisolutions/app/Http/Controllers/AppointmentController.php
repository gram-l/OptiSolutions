<?php

namespace App\Http\Controllers;

use App\Services\PatientBookingService;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient.patient_fname'     => 'required|string|max:100',
            'patient.patient_lname'     => 'required|string|max:100',
            'patient.patient_birthdate' => 'required|date',
            'patient.patient_email'     => 'required|email',
            'patient.patient_contact'   => 'required|string|max:20',
            'doctor_id'    => 'required|integer|exists:doctors,doctor_id',
            'service_type' => 'required|string|max:100',
            'visit_date'   => 'required|date',
            'notes'        => 'nullable|string',
        ]);

        $booking = app(PatientBookingService::class)->book($validated['patient'], [
            'doctor_id'    => $validated['doctor_id'],
            'service_type' => $validated['service_type'],
            'visit_date'   => $validated['visit_date'],
            'notes'        => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'patient_id' => $booking['patient']->patient_id,
            'visit_id'   => $booking['visit']->visit_id,
        ], 201);
    }
}
