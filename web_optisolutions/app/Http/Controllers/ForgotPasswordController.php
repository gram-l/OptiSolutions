<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    // ── STEP 1: Show "enter your email" form ─────────────────────────────────
    public function showEmailForm()
    {
        return view('auth.forgot-password');
    }

    // ── STEP 1: Send OTP to email ─────────────────────────────────────────────
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'No account found with that email address.',
        ]);

        // Generate 6-digit OTP
        $otp = random_int(100000, 999999);

        // Delete any previous OTP for this email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Store OTP (expires in 10 minutes)
        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => $otp,
            'created_at' => Carbon::now(),
        ]);

        // Send OTP email
        Mail::send('emails.email_otp', ['otp' => $otp], function ($mail) use ($request) {
            $mail->to($request->email)
                 ->subject('PolyClinic – Your Password Reset Code');
        });

        // Pass email to OTP form via session
        session(['reset_email' => $request->email]);

        return redirect()->route('password.otp.form')
                         ->with('success', 'A 6-digit code has been sent to ' . $request->email);
    }

    // ── STEP 2: Show OTP input form ───────────────────────────────────────────
    public function showOtpForm()
    {
        if (!session('reset_email')) {
            return redirect()->route('password.forgot');
        }
        return view('auth.otp');
    }

    // ── STEP 2: Verify OTP ────────────────────────────────────────────────────
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $email = session('reset_email');

        $record = DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->where('token', $request->otp)
                    ->first();

        if (!$record) {
            return back()->withErrors(['otp' => 'Invalid code. Please try again.']);
        }

        // Check if OTP has expired (10 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return back()->withErrors(['otp' => 'This code has expired. Please request a new one.']);
        }

        // OTP valid — allow access to reset form
        session(['otp_verified' => true]);

        return redirect()->route('password.reset.form');
    }

    // ── STEP 3: Show new password form ────────────────────────────────────────
    public function showResetForm()
    {
        if (!session('reset_email') || !session('otp_verified')) {
            return redirect()->route('password.forgot');
        }
        return view('auth.reset-password');
    }

    // ── STEP 3: Save new password ─────────────────────────────────────────────
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ], [
            'password.confirmed' => 'Passwords do not match.',
            'password.min'       => 'Password must be at least 8 characters.',
        ]);

        $email = session('reset_email');

        if (!$email || !session('otp_verified')) {
            return redirect()->route('password.forgot');
        }

        // Update password
        DB::table('users')
          ->where('email', $email)
          ->update(['password' => Hash::make($request->password)]);

        // Clean up
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        session()->forget(['reset_email', 'otp_verified']);

        return redirect('/auth/login')
               ->with('success', 'Password updated successfully. Please sign in.');
    }

    // ── Resend OTP ────────────────────────────────────────────────────────────
    public function resendOtp()
    {
        $email = session('reset_email');

        if (!$email) {
            return redirect()->route('password.forgot');
        }

        $otp = random_int(100000, 999999);

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email'      => $email,
            'token'      => $otp,
            'created_at' => Carbon::now(),
        ]);

        Mail::send('emails.email_otp', ['otp' => $otp], function ($mail) use ($email) {
            $mail->to($email)
                 ->subject('PolyClinic – Your Password Reset Code');
        });

        return back()->with('success', 'A new code has been sent to ' . $email);
    }
}