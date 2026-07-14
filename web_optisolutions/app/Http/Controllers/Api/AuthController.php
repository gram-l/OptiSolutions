<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Handle login and issue a Sanctum token.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Return the currently authenticated user.
     * Matches fields expected by ProfileData.load() in Flutter:
     * user_id, name, email, phone_number
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user_id'      => $user->user_id,
            'name'         => $user->name,
            'email'        => $user->email,
            'phone_number' => $user->phone_number,
        ]);
    }

    /**
     * Update the currently authenticated user's profile.
     * Matches fields sent by ProfileData.updateProfile() in Flutter.
     */
    public function updateMe(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($user->user_id, 'user_id'),
            ],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $user->update($validated);

        return response()->json([
            'user_id'      => $user->user_id,
            'name'         => $user->name,
            'email'        => $user->email,
            'phone_number' => $user->phone_number,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}