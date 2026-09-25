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

        $yearsOfExcellence = ($clinicInfo && $clinicInfo->founded_year)
            ? now()->year - $clinicInfo->founded_year
            : null;

        $happyPatientsCount = DB::table('sentiment_results')
            ->where('sentiment_label', 'Positive')
            ->count();

        $recentFeedback = DB::table('feedback')
            ->join('sentiment_results', 'sentiment_results.feedback_id', '=', 'feedback.feedback_id')
            ->leftJoin('patients', 'patients.patient_id', '=', 'feedback.patient_id')
            ->where('sentiment_results.sentiment_label', 'Positive')
            ->whereNotNull('feedback.feedback_text')
            ->where('feedback.feedback_text', '!=', '')
            ->orderByDesc('feedback.submitted_at')
            ->limit(12)
            ->get([
                'feedback.feedback_text',
                'feedback.star_rating',
                'feedback.submitted_at',
                'patients.patient_fname',
                'patients.patient_lname',
            ]);

        return view('patient.home', compact(
            'heroImage', 'clinicInfo', 'specialistsCount', 'avgRating',
            'yearsOfExcellence', 'happyPatientsCount', 'recentFeedback'
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