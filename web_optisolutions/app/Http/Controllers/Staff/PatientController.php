<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Patient;
use App\Models\Staff\ScheduleVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::with('latestVisit.doctor');


        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->whereHas('visits', function ($q) use ($request) {
                if ($request->filled('date_from')) {
                    $q->whereDate('visit_date', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $q->whereDate('visit_date', '<=', $request->date_to);
                }
            });
        }

        $patients = $query->get();

        return view('staff.patients.index', compact('patients'));
    }

    public function show($id)
    {
        $patient = Patient::with(['latestVisit.doctor', 'visits.doctor'])->findOrFail($id);
        return view('staff.patients.show', compact('patient'));
    }

    public function edit($id)
    {
        $patient = Patient::with('latestVisit')->findOrFail($id);
        return view('staff.patients.edit', compact('patient'));
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'patient_fname'    => 'required|string',
            'patient_lname'    => 'required|string',
            'patient_birthdate'=> 'required|date',
            'patient_email'    => 'required|email',
            'patient_contact'  => 'required|string',
            'visit_id'         => 'nullable|exists:schedule_visit,visit_id',
            'notes'            => 'nullable|string',
        ]);

        $patient = Patient::findOrFail($id);
        $patient->update($request->only([
            'patient_fname', 'patient_lname', 'patient_birthdate', 'patient_email', 'patient_contact',
        ]));

        // ---- Notes: nakatira sa schedule_visit table, hindi sa patients table ----
        if ($request->filled('visit_id')) {
            ScheduleVisit::where('visit_id', $request->visit_id)
                ->update(['notes' => $request->notes]);
        }

        return redirect()->route('staff.patients')->with('success', 'Patient updated successfully!');
    }

    /**
     * Export patients list as CSV.
     */
    public function export(): StreamedResponse
    {
        $filename = 'patients_export_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = [
            'Patient ID',
            'First Name',
            'Last Name',
            'Birthdate',
            'Email',
            'Contact Number',
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Chunk lang para hindi malaki ang memory usage kung marami ang records
            Patient::orderBy('patient_id')->chunk(200, function ($patients) use ($file) {
                foreach ($patients as $patient) {
                    fputcsv($file, [
                        $patient->patient_id,
                        $patient->patient_fname,
                        $patient->patient_lname,
                        $patient->patient_birthdate
                            ? Carbon::parse($patient->patient_birthdate)->format('Y-m-d')
                            : '',
                        $patient->patient_email,
                        $patient->patient_contact,
                    ]);
                }
            });

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}