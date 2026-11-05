<?php

namespace Tests\Feature;

use App\Mail\AdminAccountOtp;
use App\Models\admin_models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAccountOtpTest extends TestCase
{
    private User $admin;
    private string $base = '/admin_acc/user_management';
    private array $account = ['name' => 'New Staff', 'email' => 'staff@example.test', 'user_role' => 'Staff', 'status' => 'active', 'password' => 'test-password'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        (require database_path('migrations/2026_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_11_05_000002_create_admin_account_otps_table.php'))->up();
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id(); $t->morphs('tokenable'); $t->string('name'); $t->string('token', 64)->unique();
            $t->text('abilities')->nullable(); $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable(); $t->timestamps();
        });
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-password', 'user_role' => 'Admin'])->fresh();
        $this->actingAs($this->admin);
        Mail::fake();
    }

    private function proof(?string $base = null): array
    {
        $response = $this->postJson(($base ?? $this->base).'/verification-code')->assertOk();
        return ['challenge_id' => $response->json('challenge_id'), 'otp' => Mail::sent(AdminAccountOtp::class)->last()->code];
    }

    private function unlock(): array
    {
        return $this->postJson($this->base.'/verify-access', $this->proof())->assertOk()->assertJsonPath('verified', true)->json();
    }

    public function test_accounts_are_visible_but_mutations_are_locked_before_verification(): void
    {
        $staff = User::create($this->account);
        $this->getJson($this->base.'/accounts')->assertOk()->assertJsonCount(2, 'users');
        foreach ([['POST', '', $this->account], ['PUT', '/'.$staff->user_id, $this->account],
            ['PATCH', '/'.$staff->user_id.'/toggle', []], ['DELETE', '/'.$staff->user_id, []]] as [$method, $path, $data]) {
            $this->json($method, $this->base.$path, $data)->assertForbidden()->assertJsonPath('verification_required', true);
        }
        $this->assertSame(2, User::count());
        $this->assertSame('active', $staff->fresh()->status);
        $this->getJson($this->base.'/verification-status')->assertOk()->assertJsonPath('masked_email', 'a***@example.test')->assertJsonPath('verified', false);
    }

    public function test_one_otp_unlocks_create_edit_status_delete_and_list_without_additional_codes(): void
    {
        $this->unlock();
        Mail::assertSent(AdminAccountOtp::class, 1);
        Mail::assertSent(AdminAccountOtp::class, fn ($mail) => $mail->hasTo('admin@example.test'));
        $response = $this->postJson($this->base, $this->account)->assertOk();
        $id = $response->json('user.user_id');
        $this->assertTrue(Hash::check('test-password', User::findOrFail($id)->password));
        $this->putJson($this->base.'/'.$id, array_replace($this->account, ['name' => 'Updated']))->assertOk();
        $this->patchJson($this->base.'/'.$id.'/toggle')->assertOk()->assertJsonPath('new_status', 'inactive');
        $this->getJson($this->base.'/accounts')->assertOk()->assertJsonCount(2, 'users');
        $this->deleteJson($this->base.'/'.$id)->assertOk();
        Mail::assertSent(AdminAccountOtp::class, 1);
    }

    public function test_access_lasts_exactly_24_hours_and_actions_do_not_extend_it(): void
    {
        $until = $this->unlock()['expires_at'];
        $this->travel(23)->hours();
        $this->withSession(['account_last_activity' => now()->timestamp]);
        $this->getJson($this->base.'/accounts')->assertOk();
        $this->getJson($this->base.'/verification-status')->assertJsonPath('expires_at', $until);
        $this->travel(1)->hours();
        $this->withSession(['account_last_activity' => now()->timestamp]);
        $this->postJson($this->base, $this->account)->assertForbidden()->assertJsonPath('verification_required', true);
        $this->getJson($this->base.'/verification-status')->assertJsonPath('verified', false);
    }

    public function test_the_24_hour_period_starts_at_successful_verification_not_code_request(): void
    {
        $proof = $this->proof();
        $this->getJson($this->base.'/verification-status')->assertJsonPath('verified', false);
        $this->travel(5)->minutes();
        $expected = now()->addDay()->toIso8601String();
        $this->postJson($this->base.'/verify-access', $proof)->assertOk()->assertJsonPath('expires_at', $expected);
    }

    public function test_verified_page_refresh_does_not_require_another_code(): void
    {
        $this->withoutVite();
        $this->get($this->base)->assertOk()->assertSee('Verify your identity')->assertSee('user-management-access.js')
            ->assertViewHas('users', fn ($users) => $users->count() === 1);
        $this->unlock();
        $this->get($this->base)->assertOk()->assertViewHas('users', fn ($users) => $users->count() === 1);
        $this->getJson($this->base.'/verification-status')->assertJsonPath('verified', true);
        $this->postJson($this->base.'/verification-code')->assertOk()->assertJsonPath('verified', true);
        Mail::assertSent(AdminAccountOtp::class, 1);
    }

    public function test_another_session_and_another_admin_cannot_reuse_access(): void
    {
        $this->unlock();
        $this->withSession(['user_management_context' => 'another-browser']);
        $this->postJson($this->base, $this->account)->assertForbidden();
        $other = User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => 'test-password', 'user_role' => 'Admin'])->fresh();
        $this->actingAs($other)->postJson($this->base, $this->account)->assertForbidden();
    }

    public function test_code_is_hashed_and_single_use_and_not_an_access_grant_until_verified(): void
    {
        $proof = $this->proof();
        $row = DB::table('admin_account_otps')->first();
        $this->assertNotSame($proof['otp'], $row->code_hash);
        $this->assertTrue(Hash::check($proof['otp'], $row->code_hash));
        $this->postJson($this->base, $this->account)->assertForbidden();
        $this->postJson($this->base.'/verify-access', $proof)->assertOk();
        $this->postJson($this->base.'/verify-access', $proof)->assertUnprocessable();
        $this->getJson($this->base.'/accounts')->assertOk();
    }

    public function test_code_expires_after_ten_minutes(): void
    {
        $proof = $this->proof(); $this->travel(10)->minutes();
        $this->postJson($this->base.'/verify-access', $proof)->assertUnprocessable();
        $this->postJson($this->base, $this->account)->assertForbidden();
    }

    public function test_five_wrong_attempts_lock_the_code(): void
    {
        $proof = $this->proof();
        $wrong = $proof['otp'] === '111111' ? '222222' : '111111';
        for ($i = 0; $i < 5; $i++) $this->postJson($this->base.'/verify-access', array_replace($proof, ['otp' => $wrong]))->assertUnprocessable();
        $this->assertSame(5, DB::table('admin_account_otps')->first()->attempts);
        $this->postJson($this->base.'/verify-access', $proof)->assertUnprocessable();
    }

    public function test_resend_invalidates_previous_code_and_has_cooldown(): void
    {
        $old = $this->proof();
        $this->postJson($this->base.'/verification-code')->assertStatus(429)->assertJsonPath('retry_after', 60);
        $this->travel(61)->seconds(); $new = $this->proof();
        $this->postJson($this->base.'/verify-access', $old)->assertUnprocessable();
        $this->postJson($this->base.'/verify-access', $new)->assertOk();
    }

    public function test_mail_failure_does_not_unlock_access(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Mail unavailable'));
        $this->postJson($this->base.'/verification-code')->assertStatus(503);
        $this->assertSame(0, DB::table('admin_account_otps')->count());
        $this->postJson($this->base, $this->account)->assertForbidden();
    }

    public function test_mobile_token_can_unlock_but_does_not_share_a_browser_grant(): void
    {
        $this->unlock();
        $token = $this->admin->createToken('mobile')->plainTextToken;
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/admin/users')->assertOk();
        $this->postJson('/api/admin/users', $this->account)->assertForbidden();
        $this->travel(61)->seconds();
        $proof = $this->proof('/api/admin/users');
        $this->postJson('/api/admin/users/verify-access', $proof)->assertOk();
        $this->getJson('/api/admin/users')->assertOk();
        $this->postJson('/api/admin/users', $this->account)->assertOk();
    }

    public function test_non_admin_and_inactive_accounts_cannot_unlock(): void
    {
        $staff = User::create($this->account)->fresh();
        $this->actingAs($staff)->getJson('/api/admin/users/verification-status')->assertForbidden();
        $this->admin->forceFill(['status' => 'inactive'])->save();
        $this->actingAs($this->admin)->postJson($this->base.'/verification-code')->assertForbidden();
    }

    public function test_email_change_invalidates_the_grant(): void
    {
        $this->unlock();
        $this->admin->forceFill(['email' => 'changed@example.test'])->save();
        $this->postJson($this->base, $this->account)->assertForbidden();
    }
}
