<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    public function index()
    {
        $doctors = Doctor::with('schedules')->get();
        return view('staff.doctors.index', compact('doctors'));
    }

    public function edit($id)
    {
        $doctor = Doctor::with('schedules')->findOrFail($id);
        return view('staff.doctors.edit', compact('doctor'));
    }

    public function update(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);

        $request->validate([
            'schedules' => 'array',
            'schedules.*.*.start_time' => 'required|date_format:H:i',
            'schedules.*.*.end_time' => 'required|date_format:H:i|after:schedules.*.*.start_time',
        ]);

        // Only the schedule is editable here. Delete existing sessions
        // and recreate them from the submitted checked days/sessions,
        // wrapped in a transaction so existing data is never lost if
        // something fails mid-save.
        DB::transaction(function () use ($doctor, $request) {
            $doctor->schedules()->delete();

            $schedules = $request->input('schedules', []);

            foreach ($schedules as $day => $sessions) {
                foreach ($sessions as $session) {
                    if (!empty($session['start_time']) && !empty($session['end_time'])) {
                        $doctor->schedules()->create([
                            'doctor_id'  => $doctor->doctor_id,
                            'day'        => $day,
                            'start_time' => $session['start_time'],
                            'end_time'   => $session['end_time'],
                        ]);
                    }
                }
            }
        });

        return redirect()->route('staff.doctors')->with('success', 'Doctor schedule updated successfully!');
    }

    public function toggleStatus($id)
    {
        $doctor = Doctor::findOrFail($id);
        $doctor->available = $doctor->available ? 0 : 1;
        $doctor->save();

        return response()->json(['success' => true, 'available' => $doctor->available]);
    }

    /**
     * Export every doctor together with their patients.
     * Format is one of: csv, xlsx, pdf.
     */
    public function export($format)
    {
        $doctors = Doctor::with('patients.latestVisit')->orderBy('doctor_name')->get();

        return match ($format) {
            'csv'  => $this->exportCsv($doctors),
            'xlsx' => $this->exportXlsx($doctors),
            'pdf'  => $this->exportPdf($doctors),
            default => abort(404),
        };
    }

    private function exportCsv($doctors)
    {
        $filename = 'doctors_patients_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($doctors) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Excel/Sheets reads accented names correctly
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($doctors as $doctor) {
                fputcsv($handle, [$doctor->doctor_name, $doctor->specialty]);
                fputcsv($handle, ['Patient Name', 'Email', 'Contact Number', 'Birthdate', 'Last Visit']);

                foreach ($doctor->patients as $patient) {
                    fputcsv($handle, [
                        $patient->full_name,
                        $patient->patient_email,
                        $patient->patient_contact,
                        $patient->patient_birthdate,
                        $patient->latestVisit->visit_date ?? 'No visits yet',
                    ]);
                }

                fputcsv($handle, []); // blank row between doctors
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportXlsx($doctors)
    {
        // No package needed: Excel opens an HTML table just fine as long
        // as the file is served with a .xls extension and the right
        // content type, so we build a small HTML table here instead of
        // a real binary .xlsx file.
        $filename = 'doctors_patients_' . now()->format('Ymd_His') . '.xls';

        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<table border="1">';

        foreach ($doctors as $doctor) {
            $html .= '<tr><td colspan="5"><b>' . e($doctor->doctor_name) . ' — ' . e($doctor->specialty) . '</b></td></tr>';
            $html .= '<tr>'
                . '<th>Patient Name</th>'
                . '<th>Email</th>'
                . '<th>Contact Number</th>'
                . '<th>Birthdate</th>'
                . '<th>Last Visit</th>'
                . '</tr>';

            foreach ($doctor->patients as $patient) {
                $html .= '<tr>'
                    . '<td>' . e($patient->full_name) . '</td>'
                    . '<td>' . e($patient->patient_email) . '</td>'
                    . '<td>' . e($patient->patient_contact) . '</td>'
                    . '<td>' . e($patient->patient_birthdate) . '</td>'
                    . '<td>' . e($patient->latestVisit->visit_date ?? 'No visits yet') . '</td>'
                    . '</tr>';
            }

            $html .= '<tr><td colspan="5">&nbsp;</td></tr>'; // blank row between doctors
        }

        $html .= '</table></body></html>';

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    private function exportPdf($doctors)
    {
        // No package needed: render a print-friendly page and let the
        // staff member use the browser's own "Print > Save as PDF"
        // option (Ctrl+P), instead of generating a PDF on the server.
        return view('staff.doctors.export_pdf', compact('doctors'));
    }
}