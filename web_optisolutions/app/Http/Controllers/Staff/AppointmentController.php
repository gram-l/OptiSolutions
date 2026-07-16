<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = DB::table('schedule_visit')
            ->leftJoin('doctors', 'schedule_visit.doctor_id', '=', 'doctors.doctor_id')
            ->select(
                'schedule_visit.visit_id',
                'schedule_visit.doctor_id',
                'doctors.doctor_name as doctor_name',
                'schedule_visit.visit_date',
                'schedule_visit.service_type',
                'doctors.specialty as service'
            )
            ->get();

        return view('staff.appointments.index', compact('appointments'));
    }
}