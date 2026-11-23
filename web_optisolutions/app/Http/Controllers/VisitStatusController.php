<?php

namespace App\Http\Controllers;

use App\Support\ScheduleVisitCapacity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Staff action on a schedule visit: Cancel (releases the slot). The row is
 * kept, so history and analytics are not lost.
 *
 * Staff can no longer confirm visits: bookings are Confirmed automatically
 * and the patient confirms attendance from the reminder email.
 */
class VisitStatusController extends Controller
{
    public function update(Request $request, int $id)
    {
        $data = $request->validate(['status' => 'required|in:Cancelled']);

        if (!ScheduleVisitCapacity::hasStatusColumn()) {
            return response()->json([
                'success' => false,
                'message' => 'Run the schedule visit status migration first (php artisan migrate).',
            ], 409);
        }

        return DB::transaction(function () use ($data, $id) {
            $visit = DB::table('schedule_visit')->where('visit_id', $id)->lockForUpdate()->first();
            abort_unless($visit, 404);

            if ($visit->visited) {
                return response()->json(['success' => false, 'message' => 'Completed visits cannot change status.'], 422);
            }
            if ($visit->status === $data['status']) {
                return response()->json(['success' => true, 'status' => $visit->status, 'message' => 'No change.']);
            }
            // Cancelled visits cannot be revived because their slot may already be taken.
            if ($visit->status === 'Cancelled') {
                return response()->json(['success' => false, 'message' => 'This visit was cancelled. Ask the patient to schedule a new visit.'], 422);
            }

            DB::table('schedule_visit')->where('visit_id', $id)->update(['status' => $data['status']]);

            return response()->json([
                'success' => true,
                'status'  => $data['status'],
                'message' => 'Schedule visit cancelled. The slot is open again.',
            ]);
        });
    }
}