<?php
// app/Http/Controllers/Api/ApiProfileController.php
namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApiProfileController extends Controller
{
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