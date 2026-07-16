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
}