<?php

namespace App\Http\Controllers;

use App\Models\ClinicInfo;
use App\Support\PublicClinicDetails;

class ClinicInfoController extends Controller
{
    public function index()
    {
        $info = ClinicInfo::first();
        return response()->json(array_merge($info?->toArray() ?? [], PublicClinicDetails::from($info)))
            ->header('Cache-Control', 'no-store');
    }
}
