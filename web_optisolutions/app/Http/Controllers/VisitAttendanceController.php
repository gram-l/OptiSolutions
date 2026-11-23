<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use App\Support\VisitAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Public page opened from the reminder email (signed link). The GET only shows
 * the choices; nothing is recorded until the patient presses a button (POST),
 * so email scanners that pre-open links cannot confirm a visit by accident.
 */
class VisitAttendanceController extends Controller
{
    public function show(Request $request, int $id)
    {
        return view('visit.attendance', $this->state($this->find($id)));
    }

    public function store(Request $request, int $id)
    {
        abort_unless(VisitAttendance::hasColumns(), 404);
        $data = $request->validate(['response' => 'required|in:' . VisitAttendance::COMING . ',' . VisitAttendance::DECLINED]);
        $notify = null;

        DB::transaction(function () use ($id, $data, &$notify) {
            $visit = DB::table('schedule_visit')->where('visit_id', $id)->lockForUpdate()->first();
            abort_unless($visit, 404);

            if ($visit->visited || ($visit->status ?? null) === 'Cancelled' || $visit->attendance_response === $data['response']) {
                return;
            }

            DB::table('schedule_visit')->where('visit_id', $id)->update([
                'attendance_response' => $data['response'],
                'attendance_responded_at' => now(),
            ]);
            $notify = $visit;
        });

        if ($notify) {
            try {
                $row = DB::table('schedule_visit as v')
                    ->join('patients as p', 'p.patient_id', '=', 'v.patient_id')
                    ->leftJoin('doctors as d', 'd.doctor_id', '=', 'v.doctor_id')
                    ->where('v.visit_id', $id)
                    ->first(['v.visit_date', 'p.patient_fname', 'p.patient_lname', 'd.doctor_name']);
                NotificationService::visitAttendance(
                    trim($row->patient_fname . ' ' . $row->patient_lname),
                    (string) ($row->doctor_name ?? 'the doctor'),
                    (string) $row->visit_date,
                    $id,
                    $data['response'] === VisitAttendance::COMING
                );
            } catch (\Throwable $e) {
                // The reply is saved; a failed staff notification must not fail the patient's page.
                Log::error('Could not notify staff of a visit attendance reply.', ['visit_id' => $id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->to($request->fullUrl());
    }

    private function find(int $id): object
    {
        abort_unless(VisitAttendance::hasColumns(), 404);

        $visit = DB::table('schedule_visit as v')
            ->join('patients as p', 'p.patient_id', '=', 'v.patient_id')
            ->leftJoin('doctors as d', 'd.doctor_id', '=', 'v.doctor_id')
            ->where('v.visit_id', $id)
            ->first(['v.*', 'p.patient_fname', 'd.doctor_name']);
        abort_unless($visit, 404);

        return $visit;
    }

    private function state(object $visit): array
    {
        $start = VisitAttendance::startsAt($visit);
        $end = VisitAttendance::endsAt($visit) ?? $start;

        $closed = null;
        if (($visit->status ?? null) === 'Cancelled') {
            $closed = 'This visit was cancelled. Please use our chatbot to schedule a new visit.';
        } elseif ($visit->visited) {
            $closed = 'This visit has already been completed. Thank you for visiting PolyClinic Lipa.';
        }

        return [
            'firstName' => $visit->patient_fname,
            'service' => $visit->service_type,
            'doctor' => $visit->doctor_name,
            'schedule' => $start
                ? $start->format('l, F j, Y') . ' at ' . $start->format('g:i A') . ($end && $end->gt($start) ? ' - ' . $end->format('g:i A') : '')
                : '',
            'response' => $visit->attendance_response,
            'closed' => $closed,
        ];
    }
}