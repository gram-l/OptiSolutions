<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function login()
    {
        return view('staff.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'staff_email' => 'required|email',
            'staff_password' => 'required',
        ]);

        $user = User::where('email', $request->staff_email)
                    ->where('role', 'staff')
                    ->first();

        if (!$user || !Hash::check($request->staff_password, $user->password)) {
            return back()->withErrors([
                'staff_email' => 'Invalid email or password.',
            ])->onlyInput('staff_email');
        }

        if ($user->status !== 'active') {
            return back()->withErrors([
                'staff_email' => 'Your account is inactive. Please contact the administrator.',
            ])->onlyInput('staff_email');
        }

        Auth::guard('staff')->login($user);
        $request->session()->regenerate();

        return redirect()->intended('/staff/dashboard');
    }

    public function dashboard()
    {
        $totalAppointments = Appointment::count();
        $pendingInquiries = Inquiry::where('status', 'Pending')->count();
        $activeDoctors = Doctor::where('status', 'Available')->count();
        $totalPatients = Patient::count();

        $positivePercent = 75;
        $neutralPercent = 18;
        $negativePercent = 5;

        $serviceDistribution = collect();

        return view('staff.dashboard', compact(
            'totalAppointments',
            'pendingInquiries',
            'activeDoctors',
            'totalPatients',
            'positivePercent',
            'neutralPercent',
            'negativePercent',
            'serviceDistribution'
        ));
    }

    public function logout(Request $request)
    {
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/staff/login');
    }
}