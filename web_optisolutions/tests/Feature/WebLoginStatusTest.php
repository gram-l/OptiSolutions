<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebLoginStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'hashing.bcrypt.rounds' => 4]);
        Schema::create('users', function (Blueprint $t) {
            $t->increments('user_id'); $t->string('name'); $t->string('email')->unique();
            $t->string('password'); $t->string('user_role'); $t->string('status');
            $t->dateTime('last_login_at')->nullable(); $t->timestamps();
        });
    }

    public function test_deactivated_admin_and_staff_cannot_log_in_or_update_last_login(): void
    {
        foreach (['Admin', 'Staff'] as $index => $role) {
            DB::table('users')->insert(['user_id' => $index + 1, 'name' => $role, 'email' => strtolower($role).'@example.test',
                'password' => Hash::make('test-password'), 'user_role' => $role, 'status' => 'inactive']);
            $this->from('/auth/login')->post('/auth/login', [
                'email' => strtolower($role).'@example.test', 'password' => 'test-password',
            ])->assertRedirect('/auth/login')->assertSessionHasErrors(['email' => 'This account is Deactivated']);
            $this->assertGuest();
            $this->assertNull(DB::table('users')->where('user_id', $index + 1)->value('last_login_at'));
            $this->assertNull(session()->getOldInput('password'));
        }
    }

    public function test_active_accounts_can_log_in_and_reactivated_accounts_can_log_in_again(): void
    {
        DB::table('users')->insert(['user_id' => 1, 'name' => 'Staff', 'email' => 'staff@example.test',
            'password' => Hash::make('test-password'), 'user_role' => 'Staff', 'status' => 'inactive']);
        DB::table('users')->where('user_id', 1)->update(['status' => 'active']);
        $this->post('/auth/login', ['email' => 'staff@example.test', 'password' => 'test-password'])
            ->assertRedirect('/staff/dashboard');
        $this->assertAuthenticated();
        $this->assertNotNull(DB::table('users')->where('user_id', 1)->value('last_login_at'));
    }

    public function test_wrong_password_keeps_the_normal_invalid_credentials_message(): void
    {
        DB::table('users')->insert(['user_id' => 1, 'name' => 'Staff', 'email' => 'staff@example.test',
            'password' => Hash::make('test-password'), 'user_role' => 'Staff', 'status' => 'inactive']);
        $this->from('/auth/login')->post('/auth/login', ['email' => 'staff@example.test', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'Invalid email or password.']);
        $this->assertGuest();
    }
}
