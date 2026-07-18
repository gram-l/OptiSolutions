<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_models\Complaint;

class ComplaintController extends Controller
{
    // GET /api/admin/complaints — used by Flutter
    //
    // Read-only for now: these rows are produced by the ML pipeline
    // (via log_id) and this endpoint is for admin visibility, not
    // editing. `status` is returned as-is ('pending' | 'in_progress' |
    // 'resolved') — Flutter formats it for display. If you later want
    // admins to change status from this screen, add an update() method
    // here (same pattern as DoctorController::toggleStatus) and a
    // corresponding PATCH route.
    public function apiIndex()
    {
        $complaints = Complaint::orderByDesc('created_at')
            ->get()
            ->map(function ($c) {
                return [
                    'complaint_id'   => $c->complaint_id,
                    'patient_id'     => $c->patient_id,
                    'log_id'         => $c->log_id,
                    'complaint_text' => $c->complaint_text,
                    'status'         => $c->status,
                    'date'           => optional($c->created_at)->format('M d, Y g:i A'),
                ];
            });

        return response()->json([
            'success'    => true,
            'complaints' => $complaints,
        ]);
    }
}