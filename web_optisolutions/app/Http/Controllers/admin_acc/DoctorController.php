<?php

namespace App\Http\Controllers\admin_acc;
use App\Http\Controllers\Controller;
use App\Models\admin_models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    // Used by the Blade admin page
// Used by the Blade admin page
public function index()
{
    $doctors = Doctor::all()->map(function ($doctor) {
        return [
            'id' => $doctor->doctor_id,
            'name' => $doctor->doctor_name,
            'specialty' => $doctor->specialty,
            'schedule' => $doctor->schedule,
            'description' => $doctor->description,
            'phone' => $doctor->contact_number,
            'active' => $doctor->status === 'Active',
        ];
    });

    return view('admin_acc.doctors', compact('doctors'));
}
    // GET /api/doctors — used by Flutter
    public function apiIndex()
    {
        $doctors = Doctor::all();
        return response()->json([
            'success' => true,
            'doctors' => $doctors,
        ]);
    }

    // POST /api/doctors
    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_name'     => 'required|string|max:255',
            'specialty'       => 'required|string|max:255',
            'schedule'        => 'required|string|max:255',
            'description'     => 'nullable|string',
            'contact_number'  => 'nullable|string|max:20',
            'status'          => 'nullable|string|in:Active,Inactive',
        ]);

        $validated['status'] = $validated['status'] ?? 'Active';

        $doctor = Doctor::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Doctor added successfully.',
            'doctor'  => $doctor,
        ], 201);
    }

    // PUT /api/doctors/{id}
    public function update(Request $request, $id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor not found.'], 404);
        }

        $validated = $request->validate([
            'doctor_name'    => 'required|string|max:255',
            'specialty'      => 'required|string|max:255',
            'schedule'       => 'required|string|max:255',
            'description'    => 'nullable|string',
            'contact_number' => 'nullable|string|max:20',
        ]);

        $doctor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Doctor updated successfully.',
            'doctor'  => $doctor,
        ]);
    }

    // PATCH /api/doctors/{id}/toggle
    public function toggleStatus($id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor not found.'], 404);
        }

        $doctor->status = $doctor->status === 'Active' ? 'Inactive' : 'Active';
        $doctor->save();

        return response()->json([
            'success' => true,
            'message' => "Doctor {$doctor->status} status updated.",
            'doctor'  => $doctor,
        ]);
    }

    // DELETE /api/doctors/{id}
    public function destroy($id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Doctor not found.'], 404);
        }

        $doctor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Doctor removed successfully.',
        ]);
    }
}