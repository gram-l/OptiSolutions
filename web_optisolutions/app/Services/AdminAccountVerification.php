<?php

namespace App\Services;

use App\Mail\AdminAccountOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AdminAccountVerification
{
    private function actor(Request $request)
    {
        $admin = $request->user();
        abort_unless($admin && $admin->user_role === 'Admin' && $admin->status === 'active', 403, 'An active administrator account is required.');
        return $admin;
    }

    private function context(Request $request): string
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $context = 'token:'.$token->getKey();
        } else {
            abort_unless($request->hasSession(), 403, 'Please sign in again.');
            $context = $request->session()->get('user_management_context');
            if (!$context) {
                $context = 'session:'.Str::uuid();
                $request->session()->put('user_management_context', $context);
            }
        }
        return hash_hmac('sha256', $context, config('app.key'));
    }

    public function status(Request $request): array
    {
        $admin = $this->actor($request);
        $grant = DB::table('admin_account_otps')->where('admin_id', $admin->getKey())
            ->where('admin_email', $admin->email)->where('action', 'access')
            ->where('payload_hash', $this->context($request))->whereNotNull('consumed_at')
            ->where('expires_at', '>', now())->orderByDesc('expires_at')->first();
        [$local, $domain] = explode('@', $admin->email, 2);
        return ['verified' => (bool) $grant, 'admin_name' => $admin->name,
            'masked_email' => mb_substr($local, 0, 1).'***@'.$domain,
            'expires_at' => $grant ? \Carbon\Carbon::parse($grant->expires_at)->toIso8601String() : null];
    }

    public function send(Request $request)
    {
        $admin = $this->actor($request);
        if ($this->status($request)['verified']) return response()->json(['success' => true] + $this->status($request));
        $lock = Cache::lock('admin-account-otp-send:'.$admin->getKey(), 60);
        if (!$lock->get()) return response()->json(['success' => false, 'message' => 'A code is already being sent. Please wait.', 'retry_after' => 60], 429);
        try {
            return $this->sendCode($request, $admin);
        } finally {
            $lock->release();
        }
    }

    private function sendCode(Request $request, $admin)
    {
        $key = 'admin-account-otp:'.$admin->getKey();
        if (RateLimiter::tooManyAttempts($key.':minute', 1) || RateLimiter::tooManyAttempts($key.':hour', 10)) {
            $retry = RateLimiter::tooManyAttempts($key.':hour', 10)
                ? RateLimiter::availableIn($key.':hour') : RateLimiter::availableIn($key.':minute');
            return response()->json(['success' => false, 'message' => 'Please wait before requesting another code.', 'retry_after' => $retry], 429);
        }
        $code = (string) random_int(100000, 999999);
        $id = (string) Str::uuid();
        $context = $this->context($request);
        DB::table('admin_account_otps')->insert([
            'id' => $id, 'admin_id' => $admin->getKey(), 'admin_email' => $admin->email,
            'action' => 'access', 'target_id' => null, 'payload_hash' => $context,
            'code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes(10), 'created_at' => now(),
        ]);
        try {
            Mail::to($admin->email)->send(new AdminAccountOtp($code));
        } catch (\Throwable $exception) {
            DB::table('admin_account_otps')->where('id', $id)->delete();
            report($exception);
            return response()->json(['success' => false, 'message' => 'The verification email could not be sent. Please try again.'], 503);
        }
        // Resending affects pending codes in this login only, never an existing access grant.
        DB::table('admin_account_otps')->where('admin_id', $admin->getKey())->where('payload_hash', $context)
            ->where('id', '!=', $id)->whereNull('consumed_at')->delete();
        RateLimiter::hit($key.':minute', 60);
        RateLimiter::hit($key.':hour', 3600);
        return response()->json(['success' => true, 'challenge_id' => $id, 'expires_in' => 600, 'retry_after' => 60,
            'message' => 'A verification code was sent to '.$this->status($request)['masked_email'].'.']);
    }

    public function verify(Request $request)
    {
        $admin = $this->actor($request);
        $proof = $request->validate(['challenge_id' => 'required|uuid', 'otp' => 'required|digits:6']);
        $context = $this->context($request);
        return DB::transaction(function () use ($request, $admin, $proof, $context) {
            $challenge = DB::table('admin_account_otps')->where('id', $proof['challenge_id'])
                ->where('admin_id', $admin->getKey())->lockForUpdate()->first();
            $invalid = !$challenge || $challenge->consumed_at || $challenge->attempts >= 5
                || now()->gte($challenge->expires_at) || $challenge->admin_email !== $admin->email
                || $challenge->action !== 'access' || !hash_equals($challenge->payload_hash, $context);
            if ($invalid) return response()->json(['success' => false, 'message' => 'This code expired or is no longer valid. Please request a new code.'], 422);
            if (!Hash::check($proof['otp'], $challenge->code_hash)) {
                DB::table('admin_account_otps')->where('id', $challenge->id)->increment('attempts');
                return response()->json(['success' => false, 'message' => $challenge->attempts >= 4 ? 'Too many incorrect codes. Request a new code.' : 'Incorrect verification code. Please try again.'], 422);
            }
            DB::table('admin_account_otps')->where('id', $challenge->id)->update(['consumed_at' => now(), 'expires_at' => now()->addDay()]);
            return response()->json(['success' => true, 'message' => 'User Management is unlocked for 24 hours.'] + $this->status($request));
        });
    }
}
