<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class VisitRescheduleController extends Controller
{
    public function schedules(Request $request, int $id)
    {
        $visit = DB::table('schedule_visit')->where('visit_id', $id)->first();
        abort_unless($visit, 404);
        $doctor = DB::table('doctors')->where('doctor_id', $visit->doctor_id)->first();
        $available = $doctor && $doctor->available && strtolower(trim($doctor->status ?? '')) === 'active';
        return response()->json([
            'visit_date' => $visit->visit_date,
            'today' => now('Asia/Manila')->toDateString(),
            'doctor' => $doctor->doctor_name ?? null,
            'doctor_available' => (bool) $available,
            'message' => $available ? null : 'This doctor is inactive or unavailable. Update the doctor’s availability in Manage Doctors before rescheduling.',
            'schedules' => !$available ? [] : DB::table('doctor_schedules')->where('doctor_id', $visit->doctor_id)
                ->whereNotNull('start_time')->whereNotNull('end_time')->orderBy('start_time')
                ->get(['schedule_id', 'day', 'start_time', 'end_time']),
        ]);
    }

    public function store(Request $request, int $id)
    {
        $data = $request->validate(['visit_date' => 'required|date_format:Y-m-d', 'schedule_id' => 'required|integer']);
        $mailData = DB::transaction(function () use ($data, $id) {
            $visit = DB::table('schedule_visit')->where('visit_id', $id)->lockForUpdate()->first();
            abort_unless($visit, 404);
            if ($visit->visited) throw ValidationException::withMessages(['visit_date' => 'Completed visits cannot be rescheduled.']);
            $doctor = DB::table('doctors')->where('doctor_id', $visit->doctor_id)->lockForUpdate()->first();
            $slot = DB::table('doctor_schedules')->where('doctor_id', $visit->doctor_id)->where('schedule_id', $data['schedule_id'])->lockForUpdate()->first();
            if (!$doctor || !$doctor->available || strtolower(trim($doctor->status ?? '')) !== 'active') {
                throw ValidationException::withMessages(['schedule_id' => 'This doctor is inactive or unavailable. Update the doctor’s availability in Manage Doctors before rescheduling.']);
            }
            if (!$slot || !$slot->start_time || !$slot->end_time) {
                throw ValidationException::withMessages(['schedule_id' => 'Select an available schedule for this visit’s doctor.']);
            }
            $start = Carbon::parse($data['visit_date'] . ' ' . $slot->start_time, 'Asia/Manila');
            $end = Carbon::parse($data['visit_date'] . ' ' . $slot->end_time, 'Asia/Manila');
            if ($start->format('l') !== $slot->day || $start->lt(now('Asia/Manila')) || !$end->gt($start)) {
                throw ValidationException::withMessages(['visit_date' => 'Choose a future date matching the doctor’s schedule and a valid time range.']);
            }
            $patient = DB::table('patients')->where('patient_id', $visit->patient_id)->first();
            $email = trim($patient->patient_email ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw ValidationException::withMessages(['patient_email' => 'Add a valid patient email before rescheduling.']);
            $details = $start->format('l, F j, Y') . ' at ' . $start->format('g:i A') . ' - ' . $end->format('g:i A');
            // Replace the old auto-generated schedule note, preserving other notes.
            $notes = preg_replace('/^(?:Rescheduled visit: )?(?:Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday),[^\r\n]* at [^\r\n]*(?:\r?\n|$)/m', '', $visit->notes ?? '');
            DB::table('schedule_visit')->where('visit_id', $id)->update([
                'visit_date' => $data['visit_date'], 'visit_time' => $slot->start_time, 'visit_end_time' => $slot->end_time,
                'notes' => $details . (trim($notes) ? "\n" . trim($notes) : ''),
            ]);
            return ['email' => $email, 'name' => trim(($patient->patient_fname ?? '') . ' ' . ($patient->patient_lname ?? '')),
                'doctor' => $doctor->doctor_name, 'service' => $visit->service_type, 'details' => $details];
        });
        try {
            Mail::to($mailData['email'])->send(new \App\Mail\VisitRescheduled($mailData['name'], $mailData['doctor'], $mailData['service'] ?? 'Clinic visit', $mailData['details'], $id));
        } catch (\Throwable $exception) {
            Log::error('Rescheduled visit email failed.', ['visit_id' => $id, 'error' => $exception->getMessage()]);
            return response()->json(['success' => true, 'email_sent' => false, 'message' => 'Visit rescheduled, but the email failed. Use Retry email to send the updated details.']);
        }
        return response()->json(['success' => true, 'email_sent' => true, 'message' => 'Visit rescheduled. Updated details were emailed to the patient.']);
    }
}
