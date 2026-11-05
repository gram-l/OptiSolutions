<?php

namespace App\Http\Controllers\admin_acc;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Services\AdminAccountVerification;
class UserManagementController extends Controller
{
    private function accountData(Request $request, ?int $id = null): array
    {
        return Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($id, 'user_id')],
            'user_role' => 'required|in:Admin,Staff',
            'status' => 'required|in:active,inactive',
            'password' => $id ? 'sometimes|nullable|string|min:6' : 'required|string|min:6',
        ])->validate();
    }

    public function verificationStatus(Request $request)
    {
        return response()->json(['success' => true] + app(AdminAccountVerification::class)->status($request));
    }

    public function sendVerificationCode(Request $request)
    {
        return app(AdminAccountVerification::class)->send($request);
    }

    public function verifyAccess(Request $request)
    {
        return app(AdminAccountVerification::class)->verify($request);
    }
    // GET /admin_acc/users
    public function index(Request $request)
    {
        $verification = app(AdminAccountVerification::class)->status($request);
        $users = DB::table('users')
            ->select('user_id', 'name', 'email', 'user_role', 'status', 'last_login_at', 'profile_photo')
            ->orderBy('name')
            ->get();

        $stats = [
            'total'  => $users->count(),
            'active' => $users->where('status', 'active')->count(),
            'admins' => $users->where('user_role', 'Admin')->count(),
        ];

        return view('admin_acc.user_management', compact('users', 'stats', 'verification'));
    }

    // POST /admin_acc/users
    public function store(Request $request)
    {
        $data = $this->accountData($request);
        $user_id = DB::table('users')->insertGetId([
            'name'       => $request->name,
            'email'      => $request->email,
            'user_role'  => $request->user_role,
            'password'   => Hash::make($request->password),
            'status'     => $request->status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = DB::table('users')
            ->select('user_id', 'name', 'email', 'user_role', 'status', 'last_login_at', 'profile_photo')
            ->where('user_id', $user_id)
            ->first();

        return response()->json(['success' => true, 'message' => "Account for {$user->name} has been created.", 'user' => $user]);
    }

    // PUT /admin_acc/users/{user_id}
    public function update(Request $request, $user_id)
    {
        $user = DB::table('users')->where('user_id', $user_id)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found.'], 404);

        $validated = $this->accountData($request, (int) $user_id);
        $data = [
            'name'       => $request->name,
            'email'      => $request->email,
            'user_role'  => $request->user_role,
            'status'     => $request->status,
            'updated_at' => now(),
        ];
        if ($request->filled('password')) $data['password'] = Hash::make($request->password);

        DB::table('users')->where('user_id', $user_id)->update($data);

        $updated = DB::table('users')
            ->select('user_id', 'name', 'email', 'user_role', 'status', 'last_login_at', 'profile_photo')
            ->where('user_id', $user_id)
            ->first();

        return response()->json(['success' => true, 'message' => "{$updated->name}'s account has been updated.", 'user' => $updated]);
    }

    // PATCH /admin_acc/users/{user_id}/toggle
    public function toggleStatus($user_id)
    {
        $user = DB::table('users')->where('user_id', $user_id)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        $status = $user->status === 'active' ? 'inactive' : 'active';
        DB::table('users')->where('user_id', $user_id)->update(['status' => $status, 'updated_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Account status updated.', 'new_status' => $status]);
    }

    // DELETE /admin_acc/users/{user_id}
    public function destroy($user_id)
    {
        $user = DB::table('users')->where('user_id', $user_id)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found.'], 404);

        if (auth()->id() == $user_id) {
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 403);
        }

        DB::table('users')->where('user_id', $user_id)->delete();
        return response()->json(['success' => true, 'message' => "{$user->name}'s account has been permanently deleted."]);
    }


public function getUsers(){
    $users = DB::table('users')
        ->select('user_id', 'name', 'email', 'user_role', 'status', 'last_login_at', 'profile_photo')
        ->orderBy('name')
        ->get();

    $stats = [
        'total'  => $users->count(),
        'active' => $users->where('status', 'active')->count(),
        'admins' => $users->where('user_role', 'Admin')->count(),
    ];

    return response()->json([
        'success' => true,
        'users'   => $users,
        'stats'   => $stats,
    ]);
}
}
