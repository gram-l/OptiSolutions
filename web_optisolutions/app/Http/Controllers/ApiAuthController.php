<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\admin_models\User;

class ApiAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $userRow = DB::table('users')->where('email', $request->email)->first();

        if (!$userRow || !Hash::check($request->password, $userRow->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if (isset($userRow->status) && $userRow->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This account has been deactivated.',
            ], 403);
        }

        DB::table('users')->where('user_id', $userRow->user_id)->update([
            'last_login_at' => now(),
        ]);

        $user = User::find($userRow->user_id);
        $token = $user->createToken('mobile-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'user' => [
                'user_id'   => $user->user_id,
                'name'      => $user->name,
                'email'     => $user->email,
                'user_role' => $user->user_role,
            ],
            'token' => $token,
        ]);
    }

    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user_id'       => $user->user_id,
            'name'          => $user->name,
            'email'         => $user->email,
            'user_role'     => $user->user_role,
            'profile_photo' => $user->profile_photo,
        ]);
    }

    public function updatePhoto(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $path = $request->file('photo')->store('profile_photos', 'public');
        $user->profile_photo = $path;
        $user->save();

        return response()->json([
            'message' => 'Profile photo updated.',
            'profile_photo' => $user->profile_photo,
        ]);
    }
}