<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;

class PolyclinicController extends Controller
{
    public function home()
    {
        $heroImage = SiteSetting::get('hero_image', 'images/doctors_pic.png');

        $clinicInfo = DB::table('clinic_info')->first();
        $specialistsCount = DB::table('doctors')->where('available', 1)->count();

        $avgRating = DB::table('feedback')->avg('star_rating');
        $avgRating = $avgRating ? round($avgRating, 1) : null;

        $yearsOfExcellence = ($clinicInfo && $clinicInfo->founded_year)
            ? now()->year - $clinicInfo->founded_year
            : null;

        $happyPatientsCount = DB::table('sentiment_results')
            ->where('sentiment_label', 'Positive')
            ->count();

        return view('patient.home', compact(
            'heroImage', 'clinicInfo', 'specialistsCount', 'avgRating',
            'yearsOfExcellence', 'happyPatientsCount'
        ));
    }

    public function about()
    {
        // Fetch clinic info (address, about us paragraph, atbp.)
        $clinicInfo = DB::table('clinic_info')->first();

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
        $clinicInfo = DB::table('clinic_info')->first();

        return view('patient.contact', compact('clinicInfo'));
    }

    public function chatbot()
    {
        return view('patient.chatbot');
    }
}