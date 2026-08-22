<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'     => 'nullable|integer|exists:patients,patient_id',
            'log_id'         => 'nullable|integer|exists:chatbot_logs,log_id',
            'complaint_text' => 'required|string|min:10',
        ]);

        $complaint = Complaint::create([
            'patient_id'     => $validated['patient_id'] ?? null,
            'log_id'         => $validated['log_id'] ?? null,
            'complaint_text' => $validated['complaint_text'],
            'status'         => 'pending',
            
        ]);

        return response()->json(['complaint_id' => $complaint->complaint_id], 201);
    }
}