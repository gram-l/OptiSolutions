<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WebAccountDeactivationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'hashing.bcrypt.rounds' => 4]);
        (require database_path('migrations/2026_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_10_13_000003_add_account_session_timeout_to_users.php'))->up();
        \Illuminate\Support\Facades\Route::middleware('web')->get('/_deactivation-test', fn () => response('Allowed'));
    }

    public static function roles(): array
    {
        return [['Admin', 'admin_acc'], ['Staff', 'staff']];
    }

    private function signIn(string $role, string $prefix): void
    {
        DB::table('users')->insert([
            'user_id' => 1, 'name' => $role, 'email' => 'user@example.test',
            'password' => Hash::make('test-password'), 'user_role' => $role,
            'status' => 'active', 'session_timeout' => 0,
        ]);
        $this->post('/auth/login', ['email' => 'user@example.test', 'password' => 'test-password'])
            ->assertRedirect("/$prefix/dashboard");
        $this->postJson("/$prefix/profile/activity")->assertOk();
        $this->assertAuthenticated();
    }

    private function deactivate(): void
    {
        // Another administrator changes the database while this session exists.
        DB::table('users')->where('user_id', 1)->update(['status' => 'inactive']);
        // Recreate guards as a real subsequent HTTP request does; keep the session.
        $this->app['auth']->forgetGuards();
    }

    #[DataProvider('roles')]
    public function test_deactivated_session_cannot_open_a_protected_page(string $role, string $prefix): void
    {
        $this->signIn($role, $prefix);
        $oldToken = session()->token();
        $this->withSession(['verification_marker' => 'must-be-cleared']);
        $this->deactivate();
        $this->get("/$prefix/dashboard")->assertRedirect('/auth/login')
            ->assertSessionHasErrors(['email' => 'This account is Deactivated'])
            ->assertSessionMissing('verification_marker');
        $this->assertGuest();
        $this->assertNotSame($oldToken, session()->token());
    }

    #[DataProvider('roles')]
    public function test_background_requests_cannot_keep_a_deactivated_session_alive(string $role, string $prefix): void
    {
        $this->signIn($role, $prefix);
        $this->deactivate();
        $this->postJson("/$prefix/profile/activity")->assertUnauthorized()
            ->assertJsonPath('message', 'This account is Deactivated')
            ->assertSessionMissing('account_last_activity');
        $this->assertGuest();
    }

    #[DataProvider('roles')]
    public function test_deactivated_session_cannot_change_a_password(string $role, string $prefix): void
    {
        $this->signIn($role, $prefix);
        $this->deactivate();
        $this->putJson("/$prefix/profile/password", [
            'current_password' => 'test-password', 'password' => 'blocked-password',
            'password_confirmation' => 'blocked-password',
        ])->assertUnauthorized();
        $this->assertTrue(Hash::check('test-password', DB::table('users')->value('password')));
        $this->assertGuest();
    }

    #[DataProvider('roles')]
    public function test_active_sessions_and_guests_keep_their_existing_access(string $role, string $prefix): void
    {
        $this->get('/_deactivation-test')->assertOk();
        $this->signIn($role, $prefix);
        $this->app['auth']->forgetGuards();
        $this->postJson("/$prefix/profile/activity")->assertOk();
        $this->get('/_deactivation-test')->assertOk();
        $this->assertAuthenticated();
    }
}
