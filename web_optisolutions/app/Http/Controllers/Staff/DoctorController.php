<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index()
    {
        $doctors = Doctor::all();
        return view('staff.doctors.index', compact('doctors'));
    }

    public function edit($id)
    {
        $doctor = Doctor::findOrFail($id);
        return view('staff.doctors.edit', compact('doctor'));
    }

    public function update(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);

        // Schedule at Status lang ang i-a-update
        $doctor->schedule = $request->schedule;
        $doctor->status = $request->status;
        $doctor->save();

        return redirect()->route('staff.doctors')->with('success', 'Doctor updated successfully!');
    }

    public function toggleStatus($id)
    {
        $doctor = Doctor::findOrFail($id);
        $doctor->status = $doctor->status === 'Available' ? 'Unavailable' : 'Available';
        $doctor->save();
        return response()->json(['success' => true, 'status' => $doctor->status]);
    }
}