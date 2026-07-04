<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;

class PolyclinicController extends Controller
{
    public function home()
    {
        $heroImage = SiteSetting::get('hero_image', 'images/doctors_pic.png');

        return view('patient.home', compact('heroImage'));
    }

    public function about()
    {
        return view('patient.about');
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
        return view('patient.contact');
    }

    public function chatbot()
    {
        return view('patient.chatbot');
    }
}