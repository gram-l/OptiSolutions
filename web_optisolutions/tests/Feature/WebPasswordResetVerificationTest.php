<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebPasswordResetVerificationTest extends TestCase
{
    private array $password = ['password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'hashing.bcrypt.rounds' => 4]);
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id'); $table->string('email')->unique(); $table->string('password');
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); $table->string('token'); $table->timestamp('created_at')->nullable();
        });
        foreach (['first@example.test', 'second@example.test'] as $email) {
            DB::table('users')->insert(['email' => $email, 'password' => Hash::make('original-password')]);
        }
        Mail::fake();
    }

    private function requestCode(string $email = 'first@example.test'): string
    {
        $this->post('/auth/forgot-password', ['email' => $email])->assertRedirect(route('password.otp.form'));
        return (string) DB::table('password_reset_tokens')->where('email', $email)->value('token');
    }

    private function verify(string $email = 'first@example.test'): string
    {
        $otp = $this->requestCode($email);
        $this->post('/auth/verify-otp', ['otp' => $otp])->assertRedirect(route('password.reset.form'));
        $this->assertSame($email, session('reset_verified_email'));
        $proof = session('reset_verified_token');
        $this->assertSame(64, strlen($proof));
        $this->assertNotSame($otp, $proof);
        return $proof;
    }

    private function assertUnchanged(string $email): void
    {
        $this->assertTrue(Hash::check('original-password', DB::table('users')->where('email', $email)->value('password')));
    }

    public function test_verified_reset_updates_only_the_verified_account_and_consumes_approval(): void
    {
        $this->verify();
        $this->get('/auth/reset-password')->assertOk();
        $this->post('/auth/reset-password', $this->password)->assertRedirect('/auth/login')->assertSessionHas('success');
        $this->assertTrue(Hash::check('new-password-123', DB::table('users')->where('email', 'first@example.test')->value('password')));
        $this->assertUnchanged('second@example.test');
        $this->assertNull(session('reset_verified_token'));
        $this->assertNull(session('reset_email'));
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    public function test_verification_for_first_email_cannot_reset_second_email_after_a_new_request(): void
    {
        $this->verify();
        $this->requestCode('second@example.test');
        $this->assertNull(session('reset_verified_email'));
        $this->assertNull(session('reset_verified_token'));
        $this->post('/auth/reset-password', $this->password)->assertRedirect(route('password.forgot'));
        $this->assertUnchanged('first@example.test');
        $this->assertUnchanged('second@example.test');
    }

    public function test_resending_invalidates_approval_even_for_the_same_email(): void
    {
        $this->verify();
        $this->from('/auth/verify-otp')->post('/auth/resend-otp')->assertRedirect('/auth/verify-otp');
        $this->assertNull(session('reset_verified_token'));
        $this->post('/auth/reset-password', $this->password)->assertRedirect(route('password.forgot'));
        $this->assertUnchanged('first@example.test');
    }

    public function test_resend_then_verification_of_the_new_code_succeeds(): void
    {
        $this->verify();
        $this->post('/auth/resend-otp');
        $otp = DB::table('password_reset_tokens')->where('email', 'first@example.test')->value('token');
        $this->post('/auth/verify-otp', ['otp' => $otp])->assertRedirect(route('password.reset.form'));
        $this->post('/auth/reset-password', $this->password)->assertRedirect('/auth/login')->assertSessionHas('success');
    }

    public function test_legacy_boolean_flag_alone_cannot_approve_a_password_reset(): void
    {
        $this->requestCode();
        $this->withSession(['otp_verified' => true]);
        $this->post('/auth/reset-password', $this->password)->assertRedirect(route('password.forgot'));
        $this->assertUnchanged('first@example.test');
    }

    public function test_changed_session_email_does_not_match_the_verified_email(): void
    {
        $this->verify();
        $this->withSession(['reset_email' => 'second@example.test']);
        $this->post('/auth/reset-password', $this->password)->assertRedirect(route('password.forgot'));
        $this->assertUnchanged('second@example.test');
    }

    public function test_expired_approval_is_rejected_again_at_password_submission(): void
    {
        $this->verify();
        $this->travel(10)->minutes();
        $this->post('/auth/reset-password', $this->password)->assertRedirect(route('password.forgot'));
        $this->assertUnchanged('first@example.test');
    }

    public function test_expired_approval_cannot_open_the_reset_form(): void
    {
        $this->verify();
        $this->travel(10)->minutes();
        $this->get('/auth/reset-password')->assertRedirect(route('password.forgot'));
    }

    public function test_replaced_reset_record_invalidates_old_session_approval(): void
    {
        $this->verify();
        DB::table('password_reset_tokens')->where('email', 'first@example.test')->update(['token' => '654321', 'created_at' => now()]);
        $this->post('/auth/reset-password', $this->password)->assertRedirect(route('password.forgot'));
        $this->assertUnchanged('first@example.test');
    }

    public function test_consumed_approval_cannot_be_replayed(): void
    {
        $proof = $this->verify();
        $this->post('/auth/reset-password', $this->password)->assertRedirect('/auth/login');
        $this->withSession(['reset_email' => 'first@example.test', 'reset_verified_email' => 'first@example.test', 'reset_verified_token' => $proof]);
        $this->post('/auth/reset-password', ['password' => 'another-password', 'password_confirmation' => 'another-password'])->assertRedirect(route('password.forgot'));
        $this->assertTrue(Hash::check('new-password-123', DB::table('users')->where('email', 'first@example.test')->value('password')));
    }

    public function test_invalid_new_email_also_clears_existing_approval(): void
    {
        $this->verify();
        $this->post('/auth/forgot-password', ['email' => 'unknown@example.test'])->assertSessionHasErrors('email');
        $this->post('/auth/reset-password', $this->password)->assertRedirect(route('password.forgot'));
        $this->assertUnchanged('first@example.test');
    }

    public function test_expired_otp_cannot_create_an_approval(): void
    {
        $otp = $this->requestCode();
        $this->travel(10)->minutes();
        $this->post('/auth/verify-otp', ['otp' => $otp])->assertSessionHasErrors('otp');
        $this->assertNull(session('reset_verified_token'));
        $this->post('/auth/reset-password', $this->password)->assertRedirect(route('password.forgot'));
    }
}
