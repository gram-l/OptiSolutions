<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ApiAuthController extends Controller
{
    // POST /api/login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = DB::table('users')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        // Optional: block inactive accounts if you added a `status` column
        if (isset($user->status) && $user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This account has been deactivated.',
            ], 403);
        }

        // Update last login timestamp if the column exists
        if (isset($user->last_login_at) || \Schema::hasColumn('users', 'last_login_at')) {
            DB::table('users')->where('user_id', $user->user_id)->update([
                'last_login_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'user' => [
                'user_id'   => $user->user_id,
                'name'      => $user->name,
                'email'     => $user->email,
                'user_role' => $user->user_role,
            ],
        ]);
    }
}