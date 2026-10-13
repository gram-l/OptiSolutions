<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountSettingsController extends Controller
{
    public function password(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $user = $request->user();
        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }
        $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        $request->session()->regenerate();

        return back()->with('account_settings_status', 'Password updated successfully.')
            ->with('account_settings_tab', 'security');
    }

    public function session(Request $request)
    {
        $validated = $request->validate(['session_timeout' => ['required', 'integer', Rule::in([0, 15, 30, 60])]]);
        $request->user()->forceFill(['session_timeout' => (int) $validated['session_timeout']])->save();
        $request->session()->put('account_last_activity', now()->timestamp);

        return back()->with('account_settings_status', 'Session timeout saved.')
            ->with('account_settings_tab', 'security');
    }

    public function activity(Request $request)
    {
        $request->session()->put('account_last_activity', now()->timestamp);

        return response()->json(['timeout' => (int) ($request->user()->session_timeout ?? 30)]);
    }

    public function clearCache(Request $request)
    {
        // Only generated page templates; do not flush sessions, throttles,
        // chatbot conversations, patient records, or saved preferences.
        if (Artisan::call('view:clear') !== 0) {
            return response()->json(['message' => 'Could not clear cached pages. Please try again.'], 500);
        }

        return response()->json(['message' => 'Cached page templates cleared. Fresh templates will load on your next page visit.']);
    }
}
