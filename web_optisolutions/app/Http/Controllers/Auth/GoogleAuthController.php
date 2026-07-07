<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google_Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function googleLogin(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
        $payload = $client->verifyIdToken($request->id_token);

        if (!$payload) {
            return response()->json(['message' => 'Invalid Google token'], 401);
        }

        $email = $payload['email'];
        $name  = $payload['name'] ?? $email;
        $googleId = $payload['sub'];

        // Adjust to your actual users table columns (user_id, user_role, etc.)
        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'email' => $email,
                'name' => $name,
                'google_id' => $googleId,
                'password' => Hash::make(Str::random(24)), // unused, but keep column satisfied
                'user_role' => 'patient', // default role — adjust per your business rules
            ]);
        } elseif (!$user->google_id) {
            // Link existing account to Google
            $user->update(['google_id' => $googleId]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
        ]);
    }
}