<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ApiForgotPasswordController extends Controller
{
    // POST /api/forgot-password/send-otp
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'No account found with that email address.',
        ]);

        $otp = random_int(100000, 999999);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => $otp,
            'created_at' => Carbon::now(),
        ]);

        Mail::send('emails.email_otp', ['otp' => $otp], function ($mail) use ($request) {
            $mail->to($request->email)
                 ->subject('PolyClinic – Your Password Reset Code');
        });

        return response()->json([
            'success' => true,
            'message' => 'A 6-digit code has been sent to ' . $request->email,
        ]);
    }

    // POST /api/forgot-password/verify-otp
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);

        $record = DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->where('token', $request->otp)
                    ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid code. Please try again.',
            ], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'This code has expired. Please request a new one.',
            ], 422);
        }

        $resetToken = bin2hex(random_bytes(32));

        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->update(['token' => $resetToken]);

        return response()->json([
            'success'     => true,
            'message'     => 'Code verified.',
            'reset_token' => $resetToken,
        ]);
    }

    // POST /api/forgot-password/reset
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'        => 'required|email',
            'reset_token'  => 'required|string',
            'password'     => 'required|min:8|confirmed',
        ], [
            'password.confirmed' => 'Passwords do not match.',
            'password.min'       => 'Password must be at least 8 characters.',
        ]);

        $record = DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->where('token', $request->reset_token)
                    ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Reset session expired. Please start over.',
            ], 422);
        }

        DB::table('users')
            ->where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully. Please sign in.',
        ]);
    }

    // POST /api/forgot-password/resend-otp
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $otp = random_int(100000, 999999);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => $otp,
            'created_at' => Carbon::now(),
        ]);

        Mail::send('emails.email_otp', ['otp' => $otp], function ($mail) use ($request) {
            $mail->to($request->email)
                 ->subject('PolyClinic – Your Password Reset Code');
        });

        return response()->json([
            'success' => true,
            'message' => 'A new code has been sent to ' . $request->email,
        ]);
    }
}