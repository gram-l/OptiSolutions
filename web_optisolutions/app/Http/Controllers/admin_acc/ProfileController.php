<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
Use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    // GET /admin_acc/profile
    public function show(Request $request)
    {
        $user = $request->user();

        return view('admin_acc.partials.profile', [
            'user' => $user,
        ]);
    }

    // POST /admin_acc/profile
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $validated['profile_photo'] = $request->file('profile_photo')->store('profile_photos', 'public');
        }

        $user->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the logged-in admin's password. (Settings > Security)
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password you entered is incorrect.',
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->back()->with('success', 'Password updated successfully.');
    }

    /**
     * Update session timeout preference. (Settings > Security)
     * Stored in session for now — move to the users table or a settings
     * table if it needs to persist per-admin across devices/logins.
     */
    public function updateSession(Request $request)
    {
        $request->validate([
            'session_timeout' => ['required', 'numeric'],
        ]);

        session(['admin_session_timeout' => $request->session_timeout]);

        return redirect()->back()->with('success', 'Session timeout updated.');
    }
}