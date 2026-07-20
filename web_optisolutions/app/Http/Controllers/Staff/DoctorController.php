<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Doctor;
use Illuminate\Http\Request;

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

    
        $doctor->available = $request->available;
        $doctor->save();

        $doctor->schedules()->updateOrCreate(
            ['doctor_id' => $doctor->doctor_id, 'day' => $request->day],
            ['start_time' => $request->start_time, 'end_time' => $request->end_time]
        );

        return redirect()->route('staff.doctors')->with('success', 'Doctor updated successfully!');
    }

    public function toggleStatus($id)
    {
        $doctor = Doctor::findOrFail($id);
        $doctor->available = $doctor->available ? 0 : 1;
        $doctor->save();

        return response()->json(['success' => true, 'available' => $doctor->available]);
    }
}