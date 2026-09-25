<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/auth/login');
        }

        if (Auth::user()->user_role !== 'Admin') {
            return $this->forceLogout($request);
        }

        return $next($request);
    }

    protected function forceLogout(Request $request): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();


        return redirect('/auth/login')
            ->with('unauthorized', 'Unauthorized access — you have been logged out.');
    }
}

