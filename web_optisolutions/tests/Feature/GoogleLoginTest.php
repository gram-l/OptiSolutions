<?php

namespace Tests\Feature;

use App\Models\admin_models\User;
use Google_Client;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'services.google.client_id' => 'test-client.apps.googleusercontent.com']);
        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('user_role');
            $table->string('status');
            $table->string('google_id')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    private function account(string $role = 'Admin', string $status = 'active'): User
    {
        DB::table('users')->insert(['user_id' => 1, 'name' => 'Clinic User', 'email' => 'clinic@example.test',
            'password' => 'unused', 'user_role' => $role, 'status' => $status]);
        return User::findOrFail(1);
    }

    private function google(array|false $payload = ['email' => 'clinic@example.test', 'sub' => 'google-123', 'email_verified' => true]): void
    {
        $client = Mockery::mock(Google_Client::class);
        $client->shouldReceive('setClientId')->with('test-client.apps.googleusercontent.com')->once();
        $client->shouldReceive('verifyIdToken')->with('test-token')->once()->andReturn($payload);
        $this->app->instance(Google_Client::class, $client);
    }

    public function test_admin_google_login_creates_a_browser_session_without_a_mobile_token(): void
    {
        $user = $this->account();
        $this->google();
        $this->postJson('/auth/google', ['id_token' => 'test-token'])
            ->assertOk()->assertJsonPath('redirect', '/admin_acc/dashboard')->assertJsonMissingPath('token');
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotNull(session('account_last_activity'));
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertSame('google-123', $user->fresh()->google_id);
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
        $this->postJson('/admin_acc/profile/activity')->assertOk();
    }

    public function test_staff_google_login_uses_the_staff_dashboard(): void
    {
        $user = $this->account('Staff');
        $this->google();
        $this->postJson('/auth/google', ['id_token' => 'test-token'])
            ->assertOk()->assertJsonPath('redirect', '/staff/dashboard');
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_mobile_google_login_still_issues_a_token_without_a_browser_session(): void
    {
        $this->account();
        $this->google();
        $this->postJson('/api/auth/google', ['id_token' => 'test-token'])
            ->assertOk()->assertJsonStructure(['token', 'user', 'redirect']);
        $this->assertGuest('web');
        $this->assertSame(1, DB::table('personal_access_tokens')->count());
    }

    public function test_inactive_accounts_cannot_sign_in(): void
    {
        $this->account('Staff', 'inactive');
        $this->google();
        $this->postJson('/auth/google', ['id_token' => 'test-token'])->assertForbidden();
        $this->assertGuest();
    }

    public function test_unknown_accounts_are_not_created(): void
    {
        $this->google();
        $this->postJson('/auth/google', ['id_token' => 'test-token'])->assertNotFound();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function test_invalid_tokens_are_rejected(): void
    {
        $this->google(false);
        $this->postJson('/auth/google', ['id_token' => 'test-token'])->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_missing_configuration_returns_a_visible_error(): void
    {
        config(['services.google.client_id' => null]);
        $this->postJson('/auth/google', ['id_token' => 'test-token'])
            ->assertStatus(503)->assertJsonPath('message', 'Google sign-in is not configured. Please contact your administrator.');
        $this->assertGuest();
    }
}
