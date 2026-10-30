<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Support\PublicCatalog;
use App\Support\ScheduleVisitCatalog;

class PatientDoctorController extends Controller
{
    // GET /api/doctors — used by the patient-facing website
    public function index()
    {
        $doctors = PublicCatalog::doctors()
            ->orderBy('doctor_name')
            ->get();

        $schedules = DB::table('doctor_schedules')->get()->groupBy('doctor_id');

        $result = $doctors->map(function ($doctor) use ($schedules) {
            $docSchedules = $schedules->get($doctor->doctor_id, collect())
                ->map(fn ($s) => [
                    'day'        => $s->day,
                    'start_time' => $s->start_time,
                    'end_time'   => $s->end_time,
                ])->values();

            return [
                'doctor_id'        => $doctor->doctor_id,
                'doctor_name'      => $doctor->doctor_name,
                'specialty'        => $doctor->specialty,
                'gender'           => $doctor->gender,
                'years_experience' => $doctor->years_experience,
                'education'        => $doctor->education,
                'license'          => $doctor->license,
                'fellowship'       => $doctor->fellowship,
                'description'      => $doctor->description,
                'clinic_room'      => $doctor->clinic_room,
                'profile_image'    => $doctor->profile_image,
                'available'        => $doctor->available,
                'schedules'        => $docSchedules,
            ];
        });

        return response()->json($result);
    }

    // GET /api/schedule-visit-catalog — live na listahan para sa Schedule Visit form ng chatbot
    public function scheduleVisitCatalog()
    {
        return response()->json(ScheduleVisitCatalog::build())
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}