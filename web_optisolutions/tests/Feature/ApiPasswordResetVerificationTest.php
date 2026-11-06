<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiPasswordResetVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'hashing.bcrypt.rounds' => 4]);
        Schema::create('users', function (Blueprint $t) {
            $t->id('user_id'); $t->string('email')->unique(); $t->string('password');
        });
        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary(); $t->string('token'); $t->timestamp('created_at')->nullable();
        });
        foreach (['first@example.test', 'second@example.test'] as $email) {
            DB::table('users')->insert(['email' => $email, 'password' => Hash::make('original-password')]);
        }
        Mail::fake();
    }

    public static function prefixes(): array
    {
        return [['/api/forgot-password'], ['/api/admin/forgot-password']];
    }

    private function sendCode(string $prefix): string
    {
        $this->postJson("$prefix/send-otp", ['email' => 'first@example.test'])->assertOk();
        return (string) DB::table('password_reset_tokens')->value('token');
    }

    private function verify(string $prefix, string $otp): string
    {
        $response = $this->postJson("$prefix/verify-otp", ['email' => 'first@example.test', 'otp' => $otp])->assertOk();
        $proof = $response->json('reset_token');
        $this->assertSame(64, strlen($proof));
        return $proof;
    }

    private function resetBody(string $proof, string $email = 'first@example.test'): array
    {
        return ['email' => $email, 'reset_token' => $proof, 'password' => 'changed-password', 'password_confirmation' => 'changed-password'];
    }

    private function assertOriginalPasswords(): void
    {
        foreach (DB::table('users')->pluck('password') as $hash) {
            $this->assertTrue(Hash::check('original-password', $hash));
        }
    }

    #[DataProvider('prefixes')]
    public function test_verified_reset_succeeds_once_and_cannot_target_another_email(string $prefix): void
    {
        $otp = $this->sendCode($prefix);
        $proof = $this->verify($prefix, $otp);
        $this->postJson("$prefix/reset", $this->resetBody($proof, 'second@example.test'))->assertStatus(422);
        $this->assertOriginalPasswords();
        $this->postJson("$prefix/reset", $this->resetBody($proof))->assertOk();
        $this->assertTrue(Hash::check('changed-password', DB::table('users')->where('email', 'first@example.test')->value('password')));
        $this->assertTrue(Hash::check('original-password', DB::table('users')->where('email', 'second@example.test')->value('password')));
        $this->postJson("$prefix/reset", $this->resetBody($proof))->assertStatus(422);
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    #[DataProvider('prefixes')]
    public function test_raw_otp_cannot_reset_a_password_even_before_it_expires(string $prefix): void
    {
        $otp = $this->sendCode($prefix);
        $this->postJson("$prefix/reset", $this->resetBody($otp))->assertStatus(422);
        $this->travel(2)->days();
        $this->postJson("$prefix/reset", $this->resetBody($otp))->assertStatus(422);
        $this->assertOriginalPasswords();
    }

    #[DataProvider('prefixes')]
    public function test_otp_expires_at_exactly_ten_minutes(string $prefix): void
    {
        $otp = $this->sendCode($prefix);
        $this->travel(10)->minutes();
        $this->postJson("$prefix/verify-otp", ['email' => 'first@example.test', 'otp' => $otp])->assertStatus(422);
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
        $this->assertOriginalPasswords();
    }

    #[DataProvider('prefixes')]
    public function test_verification_does_not_extend_expiry_and_expired_proof_cannot_reset(string $prefix): void
    {
        $otp = $this->sendCode($prefix);
        $this->travel(9)->minutes();
        $proof = $this->verify($prefix, $otp);
        $this->travel(1)->minutes();
        $this->postJson("$prefix/reset", $this->resetBody($proof))->assertStatus(422);
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
        $this->assertOriginalPasswords();
    }

    #[DataProvider('prefixes')]
    public function test_two_day_old_verified_token_is_rejected(string $prefix): void
    {
        $proof = $this->verify($prefix, $this->sendCode($prefix));
        $this->travel(2)->days();
        $this->postJson("$prefix/reset", $this->resetBody($proof))->assertStatus(422);
        $this->assertOriginalPasswords();
    }

    #[DataProvider('prefixes')]
    public function test_missing_timestamp_cannot_authorize_verification_or_reset(string $prefix): void
    {
        $otp = $this->sendCode($prefix);
        DB::table('password_reset_tokens')->update(['created_at' => null]);
        $this->postJson("$prefix/verify-otp", ['email' => 'first@example.test', 'otp' => $otp])->assertStatus(422);
        $proof = $this->verify($prefix, $this->sendCode($prefix));
        DB::table('password_reset_tokens')->update(['created_at' => null]);
        $this->postJson("$prefix/reset", $this->resetBody($proof))->assertStatus(422);
        $this->assertOriginalPasswords();
    }

    #[DataProvider('prefixes')]
    public function test_resending_invalidates_the_previous_verified_token(string $prefix): void
    {
        $proof = $this->verify($prefix, $this->sendCode($prefix));
        $this->postJson("$prefix/resend-otp", ['email' => 'first@example.test'])->assertOk();
        $this->postJson("$prefix/reset", $this->resetBody($proof))->assertStatus(422);
        $this->assertOriginalPasswords();
    }

    #[DataProvider('prefixes')]
    public function test_repeated_recovery_attempts_are_rate_limited(string $prefix): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson("$prefix/verify-otp", ['email' => 'first@example.test', 'otp' => '123456'])->assertStatus(422);
        }
        $this->postJson("$prefix/verify-otp", ['email' => 'first@example.test', 'otp' => '123456'])->assertStatus(429);
    }
}
