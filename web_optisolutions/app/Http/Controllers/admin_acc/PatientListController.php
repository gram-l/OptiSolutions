<?php
// app/Http/Controllers/admin_acc/PatientListController.php
namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_models\PatientList;
use Illuminate\Http\Request;

class PatientListController extends Controller
{
    public function apiIndex()
    {
        return response()->json([
            'success'  => true,
            'patients' => PatientList::orderBy('patient_lname')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_fname'     => 'required|string|max:100',
            'patient_lname'     => 'required|string|max:100',
            'patient_birthdate' => 'nullable|date',
            'patient_email'     => 'nullable|email|max:255',
            'patient_contact'   => 'nullable|string|max:20',
        ]);

        $patient = PatientList::create($validated);

        return response()->json(['success' => true, 'patient' => $patient], 201);
    }

    public function update(Request $request, $id)
    {
        $patient = PatientList::find($id);
        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found.'], 404);
        }

        $validated = $request->validate([
            'patient_fname'     => 'required|string|max:100',
            'patient_lname'     => 'required|string|max:100',
            'patient_birthdate' => 'nullable|date',
            'patient_email'     => 'nullable|email|max:255',
            'patient_contact'   => 'nullable|string|max:20',
        ]);

        $patient->update($validated);

        return response()->json(['success' => true, 'patient' => $patient]);
    }
}