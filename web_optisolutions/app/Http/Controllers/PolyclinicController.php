<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\admin_models\ClinicInfo;
use Illuminate\Support\Facades\DB;

class PolyclinicController extends Controller
{
    public function home()
{
    $heroImage = SiteSetting::get('hero_image', 'images/doctors_pic.png');
    $clinicInfo = ClinicInfo::first();

    $specialistsCount = DB::table('doctors')->where('available', 1)->count();

    $avgRating = DB::table('feedback')->avg('star_rating');
    $avgRating = $avgRating ? round($avgRating, 1) : null;

    // Years of Excellence — calculate from clinic's founding year
    // Assumes clinic_info has a `founded_year` column (e.g. 2011)
    $yearsOfExcellence = $clinicInfo && $clinicInfo->founded_year
        ? now()->year - $clinicInfo->founded_year
        : 15;

    // Happy Patients — count of patients table
    $patientsCount = DB::table('patients')->count();
    $feedbackCount = DB::table('feedback')->count();
    return view('patient.home', compact(
        'heroImage', 'clinicInfo', 'specialistsCount', 'avgRating',
        'yearsOfExcellence', 'patientsCount', 'feedbackCount'
    ));
}

    public function about()
    {
        // Fetch clinic info (address, about us paragraph, atbp.)
        $clinicInfo = ClinicInfo::first();

        // Count ng available doctors → "Specialists" stat
        $specialistsCount = DB::table('doctors')->where('available', 1)->count();

        // Average star rating mula sa feedback → "Patient Rating" stat
        $avgRating = DB::table('feedback')->avg('star_rating');
        $avgRating = $avgRating ? round($avgRating, 1) : null;

        return view('patient.about', compact('clinicInfo', 'specialistsCount', 'avgRating'));
    }

    public function services()
    {
        return view('patient.services');
    }

    public function doctors()
    {
        return view('patient.doctors');
    }

    public function contact()
    {
        // Fetch clinic info (address, contact no, hours, email) para sa Contact page
        $clinicInfo = ClinicInfo::first();

        return view('patient.contact', compact('clinicInfo'));
    }

    public function chatbot()
    {
        return view('patient.chatbot');
    }
}