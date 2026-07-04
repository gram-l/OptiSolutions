<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DoctorSchedule;
use App\Models\PatientDoctor;

class ChatbotController extends Controller
{
    // ============================================================
    // API: Get all doctors with their schedules
    // ============================================================
    public function getDoctors()
    {
        $doctors = PatientDoctor::where('available', 1)->with('schedules')->get();

        $result = $doctors->map(function ($doc) {
            return [
                'id'         => $doc->doctor_id,
                'name'       => $doc->doctor_name,
                'gender'     => $doc->gender,
                'spec'       => $doc->specialty,
                'bio'        => $doc->description,
                'yearsExp'   => $doc->years_experience,
                'education'  => $doc->education,
                'license'    => $doc->license,
                'clinic'     => $doc->clinic_room,
                'fellowship' => $doc->fellowship,
                'schedule'   => $doc->schedules->map(function ($s) {
                    $start       = date('g', strtotime($s->start_time));
                    $startSuffix = date('a', strtotime($s->start_time));
                    $end         = date('g', strtotime($s->end_time));
                    $endSuffix   = date('a', strtotime($s->end_time));

                    $timeStr = ($s->start_time === $s->end_time)
                        ? $start . $startSuffix
                        : $start . $startSuffix . ' - ' . $end . $endSuffix;

                    return ['day' => $s->day, 'time' => $timeStr];
                })->values(),
            ];
        });

        return response()->json($result);
    }

    // API: Get clinic info
    
    public function getClinicInfo()
    {
        $info = DB::table('clinic_info')->first();
        return response()->json($info);
    }

    // API: Get all services with conditions and doctors
    
    public function getServices()
    {
        $services = DB::table('services')
            ->where('available', 1)
            ->get();

        $result = $services->map(function ($svc) {
            $conditions = DB::table('service_conditions')
                ->where('service_id', $svc->service_id)
                ->pluck('condition_name');

            $doctors = DB::table('service_doctors')
                ->where('service_id', $svc->service_id)
                ->pluck('doctor_name');

            return [
                'key'         => $svc->service_key,
                'title'       => $svc->title,
                'icon'        => $svc->icon,
                'description' => $svc->description,
                'room'        => $svc->room,
                'schedule'    => $svc->schedule,
                'conditions'  => $conditions,
                'doctors'     => $doctors,
            ];
        });

        return response()->json($result);
    }

    // Save appointment
    
    public function saveAppointment(Request $request)
    {
        try {
            // 1. Save patient
            $patientId = DB::table('patients')->insertGetId([
                'patient_fname'     => $request->fname,
                'patient_lname'     => $request->lname,
                'patient_birthdate' => $request->dob,
                'patient_email'     => $request->email,
                'patient_contact'   => $request->phone,
            ]);

            // 2. Save chatbot log
            $logId = DB::table('chatbot_logs')->insertGetId([
                'user_id'      => 1,
                'user_message' => $request->user_message ?? 'Schedule appointment',
                'bot_message'  => $request->bot_message  ?? 'Appointment confirmed',
            ]);

            // 3. Find doctor_id by name
            $doctor   = DB::table('doctors')->where('doctor_name', $request->doctor)->first();
            $doctorId = $doctor ? $doctor->doctor_id : 1;

            // 4. Save schedule_visit
            DB::table('schedule_visit')->insert([
                'doctor_id'    => $doctorId,
                'patient_id'   => $patientId,
                'service_type' => $request->service,
                'visit_date'   => $request->visit_date,
                'notes'        => $request->schedule,
                'scheduled_at' => now(),
            ]);

            return response()->json([
                'success'    => true,
                'patient_id' => $patientId,
                'log_id'     => $logId,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // Save feedback
    
    public function saveFeedback(Request $request)
    {
        try {
            DB::table('feedback')->insert([
                'log_id'        => $request->log_id,
                'patient_id'    => $request->patient_id,
                'feedback_text' => $request->feedback_text ?? '',
                'star_rating'   => $request->star_rating,
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================================
    // Save complaint
    // ============================================================
    public function saveComplaint(Request $request)
    {
        try {
            // 1. Save chatbot log
            $logId = DB::table('chatbot_logs')->insertGetId([
                'user_id'      => 1,
                'user_message' => $request->complaint_text,
                'bot_message'  => 'Complaint recorded',
            ]);

            // 2. Save complaint
            $complaintId = DB::table('complaints')->insertGetId([
                'patient_id'     => $request->patient_id,
                'log_id'         => $logId,
                'complaint_text' => $request->complaint_text,
                'status'         => 'pending',
            ]);

            return response()->json([
                'success'      => true,
                'complaint_id' => $complaintId,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}