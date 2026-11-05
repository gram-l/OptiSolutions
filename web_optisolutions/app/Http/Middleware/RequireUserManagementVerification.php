<?php

namespace App\Http\Middleware;

use App\Services\AdminAccountVerification;
use Closure;
use Illuminate\Http\Request;

class RequireUserManagementVerification
{
    public function handle(Request $request, Closure $next)
    {
        if (!app(AdminAccountVerification::class)->status($request)['verified']) {
            return response()->json(['success' => false, 'verification_required' => true,
                'message' => 'Verify your admin email to unlock User Management for 24 hours.'], 403);
        }
        return $next($request);
    }
}
