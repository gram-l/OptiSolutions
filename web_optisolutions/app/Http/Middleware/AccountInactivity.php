<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AccountInactivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !in_array($user->user_role, ['Admin', 'Staff'], true)) {
            return $next($request);
        }

        $timeout = (int) ($user->session_timeout ?? 30);
        $lastActivity = (int) $request->session()->get('account_last_activity', now()->timestamp);
        if ($timeout > 0 && now()->timestamp - $lastActivity >= $timeout * 60) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Your session expired after inactivity. Please log in again.'], 401)
                : redirect()->route('login')->withErrors(['email' => 'Your session expired after inactivity. Please log in again.']);
        }

        // Background requests must not keep an unattended account signed in.
        // The browser sends activity explicitly for real interaction.
        if (!$request->session()->has('account_last_activity') ||
            ($request->isMethod('GET') && $request->header('Sec-Fetch-Mode') === 'navigate')) {
            $request->session()->put('account_last_activity', now()->timestamp);
        }

        return $next($request);
    }
}
