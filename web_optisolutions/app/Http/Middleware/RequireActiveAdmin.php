<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireActiveAdmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user() && $request->user()->user_role === 'Admin' && $request->user()->status === 'active', 403, 'An active administrator account is required.');
        return $next($request);
    }
}
