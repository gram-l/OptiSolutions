<?php

namespace App\Http\Controllers;

use App\Mail\VisitReviewPass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class VisitCompletionController extends Controller
{
    public function store(Request $request, int $id)
    {
        abort_unless(in_array(strtolower(trim($request->user()?->user_role ?? '')), ['admin', 'staff'], true), 403);

        $result = DB::transaction(function () use ($id) {
            $visit = DB::table('schedule_visit')->where('visit_id', $id)->lockForUpdate()->first();
            abort_unless($visit, 404);
            if (!$visit->visit_date || Carbon::parse($visit->visit_date)->startOfDay()->isFuture()) {
                throw ValidationException::withMessages(['visited' => 'Only visits scheduled for today or an earlier date can be marked visited.']);
            }
            if ($visit->visited && $visit->review_pass_sent_at) {
                return ['already_sent' => true];
            }

            $patient = DB::table('patients')->where('patient_id', $visit->patient_id)->first();
            $email = trim($patient->patient_email ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages(['patient_email' => 'Add a valid patient email address before marking this visit as visited.']);
            }
            $doctor = DB::table('doctors')->where('doctor_id', $visit->doctor_id)->first();
            $code = $visit->review_pass_code
                ? Crypt::decryptString($visit->review_pass_code)
                : strtoupper(bin2hex(random_bytes(16)));
            DB::table('schedule_visit')->where('visit_id', $id)->update([
                'visited' => true,
                'visited_at' => $visit->visited_at ?? now(),
                'review_pass_code' => $visit->review_pass_code ?? Crypt::encryptString($code),
                'review_pass_hash' => $visit->review_pass_hash ?? hash('sha256', $code),
            ]);

            return [
                'already_sent' => false, 'email' => $email, 'code' => $code,
                'patient_name' => trim(($patient->patient_fname ?? '') . ' ' . ($patient->patient_lname ?? '')),
                'doctor_name' => $doctor->doctor_name ?? 'Your doctor',
                'service' => $visit->service_type ?? 'Clinic visit', 'date' => $visit->visit_date,
            ];
        });

        if ($result['already_sent']) {
            return response()->json(['success' => true, 'visited' => true, 'email_sent' => true, 'message' => 'This visit is already marked visited and its review pass was emailed.']);
        }

        try {
            Mail::to($result['email'], $result['patient_name'])->send(new VisitReviewPass(
                $result['patient_name'], $result['doctor_name'], $result['service'], $result['date'], $result['code'], $id,
            ));
            DB::table('schedule_visit')->where('visit_id', $id)->update(['review_pass_sent_at' => now()]);
        } catch (\Throwable $exception) {
            Log::error('Visit review pass email failed.', ['visit_id' => $id, 'error' => $exception->getMessage()]);
            return response()->json([
                'success' => true, 'visited' => true, 'email_sent' => false,
                'message' => 'Visit marked visited, but the email could not be sent. Check mail settings and use Retry email.',
            ]);
        }

        return response()->json(['success' => true, 'visited' => true, 'email_sent' => true, 'message' => 'Visit marked visited. The review pass was emailed to the patient.']);
    }
}
