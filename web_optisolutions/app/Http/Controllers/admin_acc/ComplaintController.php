<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_models\Complaint;

class ComplaintController extends Controller
{
    // GET /api/admin/complaints — used by Flutter
    //
    // Read-only: these rows are produced by the ML pipeline (via log_id)
    // and this endpoint is for admin visibility only.
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
                    'category'       => $c->category ?? 'Uncategorized',
                    'date'           => optional($c->created_at)->format('M d, Y g:i A'),
                ];
            });

        return response()->json([
            'success'    => true,
            'complaints' => $complaints,
        ]);
    }
}