<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicInfo;
use Illuminate\Http\JsonResponse;

class ClinicController extends Controller
{
    // Iisa lang naman ang clinic (single-tenant), kaya first() na lang.
    public function show(): JsonResponse
    {
        $clinic = ClinicInfo::first();

        if (!$clinic) {
            return response()->json(['message' => 'Clinic info not found'], 404);
        }

        return response()->json([
            'clinic_name'      => $clinic->clinic_name,
            'address'          => $clinic->address,
            'contact_no'       => $clinic->contact_no,
            'operating_hours'  => $clinic->operating_hours,
        ]);
    }
}