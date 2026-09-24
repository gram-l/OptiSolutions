<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function index()
    {
        // NOTE: assumes schedule_visit has a `notes` and `patient_id` column.
        // If your actual column names differ, adjust the select() below.
        $appointments = DB::table('schedule_visit')
            ->leftJoin('doctors', 'schedule_visit.doctor_id', '=', 'doctors.doctor_id')
            ->leftJoin('patients', 'schedule_visit.patient_id', '=', 'patients.patient_id')
            ->select(
                'schedule_visit.visit_id',
                'schedule_visit.doctor_id',
                'doctors.doctor_name as doctor_name',
                'schedule_visit.visit_date',
                'schedule_visit.service_type',
                'doctors.specialty as service',
                'schedule_visit.notes',
                DB::raw("TRIM(CONCAT(patients.patient_fname, ' ', patients.patient_lname)) as patient_name")
            )
            ->orderBy('schedule_visit.visit_date')
            ->get();

        return view('staff.appointments.index', compact('appointments'));
    }

    /**
     * Update just the notes field for a single visit — used by the
     * Scheduled Visits table's Edit button (staff can only edit notes,
     * not reschedule/remove).
     */
    public function updateNotes(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::table('schedule_visit')
            ->where('visit_id', $id)
            ->update(['notes' => $request->input('notes')]);

        return response()->json(['success' => true]);
    }

    /**
     * Export every scheduled visit grouped by date.
     * Format is one of: csv, xlsx, pdf.
     */
    public function export($format)
    {
        $appointments = DB::table('schedule_visit')
            ->leftJoin('doctors', 'schedule_visit.doctor_id', '=', 'doctors.doctor_id')
            ->leftJoin('patients', 'schedule_visit.patient_id', '=', 'patients.patient_id')
            ->select(
                'schedule_visit.visit_id',
                'schedule_visit.visit_date',
                'doctors.doctor_name as doctor_name',
                'doctors.specialty as service',
                DB::raw("TRIM(CONCAT(patients.patient_fname, ' ', patients.patient_lname)) as patient_name")
            )
            ->orderBy('schedule_visit.visit_date')
            ->orderBy('schedule_visit.visit_id')
            ->get();

        // Group all visits under their date (Y-m-d), so every patient who
        // booked on the same day ends up listed together.
        $grouped = $appointments->groupBy(function ($apt) {
            return Carbon::parse($apt->visit_date)->format('Y-m-d');
        });

        return match ($format) {
            'csv'  => $this->exportCsv($grouped),
            'xlsx' => $this->exportXlsx($grouped),
            'pdf'  => $this->exportPdf($grouped),
            default => abort(404),
        };
    }

    private function exportCsv($grouped)
    {
        $filename = 'scheduled_visits_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($grouped) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Excel/Sheets reads accented names correctly
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($grouped as $date => $visits) {
                fputcsv($handle, [$date]);
                fputcsv($handle, ['Visit ID', 'Patient', 'Doctor', 'Service']);

                foreach ($visits as $visit) {
                    fputcsv($handle, [
                        $visit->visit_id,
                        $visit->patient_name ?: 'N/A',
                        $visit->doctor_name ?? 'N/A',
                        $visit->service ?? 'N/A',
                    ]);
                }

                fputcsv($handle, []); // blank row between dates
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportXlsx($grouped)
    {
        // No package needed: Excel opens an HTML table just fine as long
        // as the file is served with a .xls extension and the right
        // content type.
        $filename = 'scheduled_visits_' . now()->format('Ymd_His') . '.xls';

        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<table border="1">';

        foreach ($grouped as $date => $visits) {
            $html .= '<tr><td colspan="4"><b>' . e($date) . '</b></td></tr>';
            $html .= '<tr>'
                . '<th>Visit ID</th>'
                . '<th>Patient</th>'
                . '<th>Doctor</th>'
                . '<th>Service</th>'
                . '</tr>';

            foreach ($visits as $visit) {
                $html .= '<tr>'
                    . '<td>' . e($visit->visit_id) . '</td>'
                    . '<td>' . e($visit->patient_name ?: 'N/A') . '</td>'
                    . '<td>' . e($visit->doctor_name ?? 'N/A') . '</td>'
                    . '<td>' . e($visit->service ?? 'N/A') . '</td>'
                    . '</tr>';
            }

            $html .= '<tr><td colspan="4">&nbsp;</td></tr>'; // blank row between dates
        }

        $html .= '</table></body></html>';

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    private function exportPdf($grouped)
    {
        // No package needed: render a print-friendly page and let the
        // staff member use the browser's own "Print > Save as PDF"
        // option (Ctrl+P).
        return view('staff.appointments.export_pdf', compact('grouped'));
    }
}