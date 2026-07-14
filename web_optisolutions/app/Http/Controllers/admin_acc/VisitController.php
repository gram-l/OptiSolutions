<?php
// app/Http/Controllers/admin_acc/VisitController.php
namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    // GET /admin/appointments?week_start=2026-07-13&week_end=2026-07-19
    public function apiIndex(Request $request)
    {
        $weekStart = $request->query('week_start');
        $weekEnd   = $request->query('week_end');

        $query = DB::table('visits')
            ->leftJoin('doctors', 'visits.doctor_id', '=', 'doctors.doctor_id')
            ->leftJoin('users as patients', 'visits.patient_id', '=', 'patients.user_id') // adjust if patients live in a separate table, not users
            ->select(
                'visits.visit_id',
                'visits.service_type',
                'visits.visit_date',
                'visits.notes',
                'visits.scheduled_at',
                'doctors.doctor_name',
                'patients.name as patient_name'
            );

        if ($weekStart && $weekEnd) {
            $query->whereBetween('visits.visit_date', [$weekStart, $weekEnd]);
        }

        $visits = $query->orderBy('visits.visit_date')->get();

        return response()->json([
            'success' => true,
            'visits'  => $visits,
        ]);
    }
}