<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    // GET /admin_acc/users
    public function index()
    {
        $users = DB::table('users')
            ->select('user_id', 'name', 'email', 'user_role', 'status', 'last_login_at')
            ->orderBy('name')
            ->get();

        $stats = [
            'total'  => $users->count(),
            'active' => $users->where('status', 'active')->count(),
            'admins' => $users->where('user_role', 'Admin')->count(),
        ];

        return view('admin_acc.user_management', compact('users', 'stats'));
    }

    // POST /admin_acc/users
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'user_role' => 'required|in:Admin,Staff',
            'password'  => 'required|string|min:6',
            'status'    => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

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
            ->select('user_id', 'name', 'email', 'user_role', 'status', 'last_login_at')
            ->where('user_id', $user_id)
            ->first();

        return response()->json(['success' => true, 'message' => "Staff account for {$user->name} has been created.", 'user' => $user]);
    }

    // PUT /admin_acc/users/{user_id}
    public function update(Request $request, $user_id)
    {
        $user = DB::table('users')->where('user_id', $user_id)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found.'], 404);

        $rules = [
            'name'      => 'required|string|max:255',
            'email'     => "required|email|unique:users,email,{$user_id},user_id",
            'user_role' => 'required|in:Admin,Staff',
            'status'    => 'required|in:active,inactive',
        ];
        if ($request->filled('password')) $rules['password'] = 'string|min:6';

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

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
            ->select('user_id', 'name', 'email', 'user_role', 'status', 'last_login_at')
            ->where('user_id', $user_id)
            ->first();

        return response()->json(['success' => true, 'message' => "{$updated->name}'s account has been updated.", 'user' => $updated]);
    }

    // PATCH /admin_acc/users/{user_id}/toggle
    public function toggleStatus($user_id)
    {
        $user = DB::table('users')->where('user_id', $user_id)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found.'], 404);

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        DB::table('users')->where('user_id', $user_id)->update(['status' => $newStatus, 'updated_at' => now()]);
        $action = $newStatus === 'active' ? 'activated' : 'deactivated';

        return response()->json(['success' => true, 'message' => "{$user->name}'s account has been {$action}.", 'new_status' => $newStatus]);
    }

    // DELETE /admin_acc/users/{user_id}
    public function destroy($user_id)
    {
        $user = DB::table('users')->where('user_id', $user_id)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found.'], 404);

        if (session('user_id') == $user_id) {
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 403);
        }

        DB::table('users')->where('user_id', $user_id)->delete();
        return response()->json(['success' => true, 'message' => "{$user->name}'s account has been permanently deleted."]);
    }


public function getUsers(){
    $users = DB::table('users')
        ->select('user_id', 'name', 'email', 'user_role', 'status', 'last_login_at')
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