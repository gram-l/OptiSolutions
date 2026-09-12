<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();

        // Named error bag ('profile') and a scoped success key here —
        // both `session('success')` and the default $errors bag are
        // global to the request, so every other page's form (e.g. System
        // Settings' "Save Hours") was also tripping the header's profile
        // modal open on its own success/validation messages.
        $validated = $request->validateWithBag('profile', [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email,' . $user->user_id . ',user_id',
            'birthday' => 'nullable|date',
            'photo'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user->name     = $validated['name'];
        $user->email    = $validated['email'];
        $user->birthday = $validated['birthday'] ?? null;

        if ($request->hasFile('photo')) {
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $path = $request->file('photo')->store('profile_photos', 'public');
            $user->profile_photo = $path;
        }

        $user->save();

        return redirect()->back()->with('profile_success', 'Profile updated successfully.');
    }
}