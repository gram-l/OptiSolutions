<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = DB::table('appointments')
            ->leftJoin('doctors', 'appointments.doctor', '=', 'doctors.name')
            ->select(
                'appointments.appointment_id',
                'appointments.doctor',
                'appointments.date',
                'appointments.status',
                'doctors.specialty as service'
            )
            ->get();

        return view('staff.appointments.index', compact('appointments'));
    }
}