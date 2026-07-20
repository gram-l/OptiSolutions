<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class StaffMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/auth/login');
        }

        if (Auth::user()->user_role !== 'Staff') {
            abort(403, 'Unauthorized. Staff access only.');
        }

        return $next($request);
    }
}