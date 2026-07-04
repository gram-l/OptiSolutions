<?php

namespace App\Http\Controllers;

use App\Models\PatientDoctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    // Used by the Blade admin page
    public function index()
    {
        $doctors = PatientDoctor::all()->map(function ($doctor) {
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

    // GET /api/doctors — used by the patient-facing website
    public function apiIndex()
    {
        $doctors = PatientDoctor::where('available', 1)
            ->get()
            ->map(function ($doctor) {
                return [
                    'doctor_id'        => $doctor->doctor_id,
                    'doctor_name'      => $doctor->doctor_name,
                    'specialty'        => $doctor->specialty,
                    'gender'           => $doctor->gender,
                    'years_experience' => $doctor->years_experience,
                    'education'        => $doctor->education,
                    'license'          => $doctor->license,
                    'fellowship'       => $doctor->fellowship,
                    'description'      => $doctor->description,
                    'clinic_room'      => $doctor->clinic_room,
                    'profile_image'    => $doctor->profile_image,
                    'available'        => $doctor->available,
                    'schedules'        => [], // placeholder until schedules relationship is wired up
                ];
            });

        return response()->json($doctors);
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

        $doctor = PatientDoctor::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Doctor added successfully.',
            'doctor'  => $doctor,
        ], 201);
    }

    // PUT /api/doctors/{id}
    public function update(Request $request, $id)
    {
        $doctor = PatientDoctor::find($id);
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
        $doctor = PatientDoctor::find($id);
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
        $doctor = PatientDoctor::find($id);
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