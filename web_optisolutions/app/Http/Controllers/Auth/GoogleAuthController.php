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
        return $this->authenticate($request, false);
    }

    public function webLogin(Request $request)
    {
        return $this->authenticate($request, true);
    }

    private function authenticate(Request $request, bool $web)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $clientId = config('services.google.client_id');
        if (!$clientId) {
            return response()->json(['message' => 'Google sign-in is not configured. Please contact your administrator.'], 503);
        }

        try {
            $client = app(Google_Client::class);
            $client->setClientId($clientId);
            $payload = $client->verifyIdToken($request->id_token);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['message' => 'Google sign-in could not be verified. Please try again shortly.'], 503);
        }

        if (!$payload || empty($payload['email']) || empty($payload['sub']) || empty($payload['email_verified'])) {
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

        if ($user->status !== 'active') {
            return response()->json(['message' => 'This account is Deactivated'], 403);
        }

        if ($user->google_id && $user->google_id !== $googleId) {
            return response()->json(['message' => 'This account is linked to a different Google account. Please contact your administrator.'], 403);
        }

        if ($web && $user->dashboardRoute() === '/auth/login') {
            return response()->json(['message' => 'This account does not have access to the staff website.'], 403);
        }

        // Link the Google ID on first-time Google login, so future logins can match on google_id too
        if (!$user->google_id) {
            $user->update(['google_id' => $googleId]);
        }

        if ($web) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();
            $request->session()->put('account_last_activity', now()->timestamp);
            $user->forceFill(['last_login_at' => now()])->save();

            return response()->json([
                'message' => 'Login successful',
                'redirect' => $user->dashboardRoute(),
            ]);
        }

        // Mobile clients continue to authenticate with a Sanctum token.
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
            'redirect' => $user->dashboardRoute(),
        ]);
    }
}
