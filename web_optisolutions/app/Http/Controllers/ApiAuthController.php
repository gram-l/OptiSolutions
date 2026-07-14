<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\admin_models\User;
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

        $user = User::where('email', $request->email)->first();

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
        if (\Schema::hasColumn('users', 'last_login_at')) {
            $user->forceFill(['last_login_at' => now()])->save();
        }

        // Revoke old tokens for this device/app so we don't pile up stale ones,
        // then issue a fresh Sanctum token for the Flutter app to use on
        // every subsequent request (Authorization: Bearer <token>).
        $user->tokens()->delete();
        $token = $user->createToken('flutter-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token'   => $token,
            'user' => [
                'user_id'   => $user->user_id,
                'name'      => $user->name,
                'email'     => $user->email,
                'user_role' => $user->user_role,
            ],
        ]);
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}