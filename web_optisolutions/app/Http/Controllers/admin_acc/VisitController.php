<?php
// app/Http/Controllers/admin_acc/VisitController.php
namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    // GET /admin/appointments?week_start=2026-07-13&week_end=2026-07-19
   // VisitController.php
public function apiIndex(Request $request)
{
    $date = $request->query('date'); // e.g. 2026-07-14

    $query = DB::table('schedule_visit')
        ->leftJoin('doctors', 'schedule_visit.doctor_id', '=', 'doctors.doctor_id')
        ->leftJoin('patients', 'schedule_visit.patient_id', '=', 'patients.patient_id')
        ->select(
            'schedule_visit.visit_id',
            'schedule_visit.service_type',
            'schedule_visit.visit_date',
            'schedule_visit.notes',
            'schedule_visit.scheduled_at',
            'doctors.doctor_name',
            DB::raw("CONCAT(patients.patient_fname, ' ', patients.patient_lname) as patient_name")
        );

    if ($date) {
        $query->whereDate('schedule_visit.visit_date', $date);
    }

    $visits = $query->orderBy('schedule_visit.scheduled_at')->get();

    return response()->json(['success' => true, 'visits' => $visits]);
}

    // ─────────────────────────────────────────────────────────
    //  Web-only endpoints below — the admin Blade page's day view.
    //  apiIndex() above stays untouched; it's what the mobile app
    //  hits. These are separate routes wired only in web.php.
    // ─────────────────────────────────────────────────────────

    // GET /admin_acc/appointments/day?date=2026-07-14
    public function dayJson(Request $request)
    {
        $date = $request->query('date') ?: now()->toDateString();

        $visits = DB::table('schedule_visit')
            ->leftJoin('doctors', 'schedule_visit.doctor_id', '=', 'doctors.doctor_id')
            ->leftJoin('patients', 'schedule_visit.patient_id', '=', 'patients.patient_id')
            ->whereDate('schedule_visit.visit_date', $date)
            ->select(
                'schedule_visit.visit_id',
                'schedule_visit.service_type',
                'schedule_visit.visit_date',
                'schedule_visit.notes',
                'schedule_visit.scheduled_at',
                'doctors.doctor_name',
                DB::raw("CONCAT(patients.patient_fname, ' ', patients.patient_lname) as patient_name")
            )
            ->orderBy('schedule_visit.scheduled_at')
            ->get();

        return response()->json([
            'success' => true,
            'date' => $date,
            'visits' => $visits,
        ]);
    }

    // PUT /admin_acc/appointments/{id} — reschedule (change visit_date only;
    // there's no appointment-time column to move, just the date) and/or
    // update notes. Either field can be sent on its own — e.g. the notes
    // edit modal only sends { notes: ... }, the reschedule modal only
    // sends { visit_date: ... } — so both are validated with 'sometimes'
    // rather than always requiring visit_date.
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'visit_date' => 'sometimes|required|date',
            'notes' => 'sometimes|nullable|string|max:1000',
        ]);

        if (empty($validated)) {
            return response()->json(['success' => false, 'message' => 'Nothing to update.'], 422);
        }

        $updated = DB::table('schedule_visit')
            ->where('visit_id', $id)
            ->update($validated);

        if (!$updated) {
            $exists = DB::table('schedule_visit')->where('visit_id', $id)->exists();
            if (!$exists) {
                return response()->json(['success' => false, 'message' => 'Appointment not found.'], 404);
            }
            // $updated is 0 when the row exists but nothing actually changed
            // (e.g. rescheduling to the same date) — not an error.
        }

        return response()->json(['success' => true, 'message' => 'Appointment updated.']);
    }

    // DELETE /admin_acc/appointments/{id} — no status column to flip to
    // "cancelled", so cancelling removes the row.
    public function destroy($id)
    {
        $deleted = DB::table('schedule_visit')->where('visit_id', $id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Appointment not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Appointment removed.']);
    }
}