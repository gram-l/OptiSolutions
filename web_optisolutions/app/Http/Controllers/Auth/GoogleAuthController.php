<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\admin_models\User;
use Google_Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $googleId = $payload['sub'];

        // Only allow login for pre-existing accounts
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account found for this Google email. Please contact your administrator.'
            ], 404);
        }

        // Link the Google ID on first-time Google login, so future logins can match on google_id too
        if (!$user->google_id) {
            $user->update(['google_id' => $googleId]);
        }

        // Establish session for web (Blade) side
        Auth::login($user, true);
        $request->session()->regenerate();

        // Also issue a token for mobile clients
        $token = $user->createToken('web-or-mobile')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
            'redirect' => '/dashboard', // adjust to your actual staff landing route
        ]);
    }
}