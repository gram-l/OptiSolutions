<?php

namespace App\Http\Controllers;

use App\Models\ClinicInfo;

class ClinicInfoController extends Controller
{
    public function index()
    {
        $info = ClinicInfo::first();
        return response()->json($info);
    }
}