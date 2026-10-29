<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $deactivated = false;
        if (Auth::attemptWhen($credentials, function ($user) use (&$deactivated) {
            $deactivated = $user->status !== 'active';
            return !$deactivated;
        })) {
            $request->session()->regenerate();
            $user = Auth::user();

            // Record accurate last login timestamp
            $user->forceFill(['last_login_at' => now()])->save();
            $request->session()->put('account_last_activity', now()->timestamp);

            return redirect($user->dashboardRoute());
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $deactivated ? 'This account is Deactivated' : 'Invalid email or password.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/auth/login');
    }
}
