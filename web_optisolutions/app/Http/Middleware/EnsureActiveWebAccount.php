<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveWebAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('web');
        $user = $guard->user();
        if (!$user || !in_array($user->user_role, ['Admin', 'Staff'], true) || $user->status === 'active') {
            return $next($request);
        }

        $guard->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = 'This account is Deactivated';

        return $request->expectsJson()
            ? response()->json(['message' => $message], 401)
            : redirect()->route('login')->withErrors(['email' => $message]);
    }
}
