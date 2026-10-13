<?php

namespace Tests\Feature;

use App\Models\admin_models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        (require database_path('migrations/2026_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_10_13_000003_add_account_session_timeout_to_users.php'))->up();
        Route::middleware('web')->get('/_account-test', fn () => response('Page'));
    }

    public static function roles(): array
    {
        return [['Admin', 'admin_acc', 'PUT'], ['Staff', 'staff', 'POST']];
    }

    public function test_staff_notification_preferences_are_saved_and_control_alerts_and_email(): void
    {
        (require database_path('migrations/2026_10_13_000002_add_notification_preferences_to_users.php'))->up();
        (require database_path('migrations/2026_01_01_000001_create_app_notifications_table.php'))->up();
        $staff = $this->account('Staff');
        $staff->forceFill(['status' => 'active'])->save();
        $other = User::create(['name' => 'Other Staff', 'email' => 'other@example.com', 'password' => 'old-password', 'user_role' => 'Staff']);
        $this->actingAs($staff)->putJson('/staff/notification-preferences', ['notify_chatbot' => 'invalid'])->assertUnprocessable();
        $this->putJson('/staff/notification-preferences', ['notify_chatbot' => false, 'notify_email' => false])->assertOk();
        $this->assertFalse(\App\Services\NotificationPreferences::forUser($staff->user_id)['notify_email']);
        $this->assertTrue(\App\Services\NotificationPreferences::forUser($other->user_id)['notify_email']);
        foreach (['chat_inquiry', 'appointment', 'complaint'] as $type) DB::table('app_notifications')->insert(['type' => $type, 'title' => $type, 'message' => 'Test', 'is_read' => 0]);
        $this->getJson('/staff/notifications')->assertOk()->assertJsonCount(1, 'notifications')->assertJsonPath('unread_count', 1);
        \Illuminate\Support\Facades\Mail::fake();
        \App\Services\InquiryEmailService::send('Inquiry', 'Needs attention', 1);
        \Illuminate\Support\Facades\Mail::assertNotSent(\App\Mail\ChatbotInquiryAlert::class, fn ($mail) => $mail->hasTo($staff->email));
        $this->putJson('/staff/notification-preferences', ['notify_chatbot' => true, 'notify_email' => true])->assertOk();
        $this->getJson('/staff/notifications')->assertJsonCount(2, 'notifications')->assertJsonPath('unread_count', 2);
        \App\Services\InquiryEmailService::send('Inquiry', 'Needs attention', 1);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ChatbotInquiryAlert::class, fn ($mail) => $mail->hasTo($staff->email));
    }

    private function account(string $role = 'Admin'): User
    {
        return User::create(['name' => 'Clinic User', 'email' => strtolower($role).'@example.com',
            'password' => 'old-password', 'user_role' => $role]);
    }

    #[DataProvider('roles')]
    public function test_password_requires_the_current_password_and_confirmation(string $role, string $prefix): void
    {
        $user = $this->account($role);
        $this->actingAs($user)->from('/_account-test')->put("/$prefix/profile/password", [
            'current_password' => 'wrong', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->put("/$prefix/profile/password", [
            'current_password' => 'old-password', 'password' => 'new-password', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
        $this->put("/$prefix/profile/password", [
            'current_password' => 'old-password', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertRedirect('/_account-test')->assertSessionHas('account_settings_tab', 'security');
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    #[DataProvider('roles')]
    public function test_timeout_is_validated_and_persists_on_the_account(string $role, string $prefix, string $method): void
    {
        $user = $this->account($role);
        $this->actingAs($user)->from('/_account-test')->call($method, "/$prefix/profile/session", ['session_timeout' => -1])
            ->assertSessionHasErrors('session_timeout');
        foreach ([15, 30, 60, 0] as $minutes) {
            $this->call($method, "/$prefix/profile/session", ['session_timeout' => $minutes])
                ->assertRedirect('/_account-test')->assertSessionHas('account_settings_status');
            $this->assertSame($minutes, (int) $user->fresh()->session_timeout);
        }
    }

    #[DataProvider('roles')]
    public function test_polling_does_not_reset_inactivity_and_expired_activity_cannot_revive_it(string $role, string $prefix): void
    {
        $user = $this->account($role);
        $user->forceFill(['session_timeout' => 15])->save();
        $lastActivity = now()->subMinutes(14)->timestamp;
        $this->actingAs($user)->withSession(['account_last_activity' => $lastActivity])
            ->getJson('/_account-test')->assertOk()->assertSessionHas('account_last_activity', $lastActivity);
        $this->travel(61)->seconds();
        $this->postJson("/$prefix/profile/activity")->assertUnauthorized();
        $this->assertGuest();
    }

    #[DataProvider('roles')]
    public function test_real_activity_extends_the_session_and_never_disables_the_extra_timeout(string $role, string $prefix): void
    {
        $user = $this->account($role);
        $old = now()->subMinutes(14)->timestamp;
        $this->actingAs($user)->withSession(['account_last_activity' => $old])
            ->postJson("/$prefix/profile/activity")->assertOk()->assertJson(['timeout' => 30])
            ->assertSessionHas('account_last_activity', now()->timestamp);
        $user->forceFill(['session_timeout' => 0])->save();
        $this->withSession(['account_last_activity' => now()->subDays(2)->timestamp])
            ->postJson("/$prefix/profile/activity")->assertOk()->assertJson(['timeout' => 0]);
        $this->assertAuthenticated();
    }

    #[DataProvider('roles')]
    public function test_cache_clear_only_clears_generated_views(string $role, string $prefix): void
    {
        $user = $this->account($role);
        Cache::put('unrelated-conversation', 'keep', 600);
        Artisan::shouldReceive('call')->once()->with('view:clear')->andReturn(0);
        $this->actingAs($user)->postJson("/$prefix/profile/cache")->assertOk();
        $this->assertSame('keep', Cache::get('unrelated-conversation'));
        $this->assertDatabaseHas('users', ['user_id' => $user->getKey(), 'email' => $user->email]);
        $this->assertAuthenticated();
    }

    public function test_cache_failure_reports_failure_and_endpoints_require_the_correct_role(): void
    {
        $admin = $this->account();
        Artisan::shouldReceive('call')->once()->with('view:clear')->andReturn(1);
        $this->actingAs($admin)->postJson('/admin_acc/profile/cache')->assertStatus(500);
        $this->postJson('/staff/profile/cache')->assertForbidden();
        $this->post('/logout')->assertRedirect('/auth/login');
        $this->postJson('/admin_acc/profile/cache')->assertUnauthorized();
    }

    #[DataProvider('roles')]
    public function test_web_login_saves_the_timestamp(string $role): void
    {
        $user = $this->account($role);
        $this->post('/auth/login', ['email' => $user->email, 'password' => 'old-password'])->assertRedirect($user->dashboardRoute());
        $this->assertSame(now()->format('Y-m-d H:i:s'), $user->fresh()->last_login_at);
    }

    public function test_admin_profile_saves_birthday_and_rejects_duplicate_email(): void
    {
        $user = $this->account();
        $staff = $this->account('Staff');
        $this->actingAs($user)->post('/admin_acc/profile', ['name' => 'New name', 'email' => $staff->email])
            ->assertSessionHasErrors('email');
        $this->post('/admin_acc/profile', ['name' => 'New name', 'email' => $user->email, 'birthday' => '1995-05-20'])
            ->assertSessionHas('profile_success');
        $this->assertDatabaseHas('users', ['user_id' => $user->getKey(), 'name' => 'New name', 'birthday' => '1995-05-20']);
    }

    #[DataProvider('roles')]
    public function test_patient_csv_downloads_for_both_roles(string $role, string $prefix): void
    {
        (require database_path('migrations/2026_01_01_000001_create_patients_table.php'))->up();
        Schema::create('doctors', function (Blueprint $table) {
            $table->integer('doctor_id')->primary(); $table->string('doctor_name');
        });
        Schema::create('schedule_visit', function (Blueprint $table) {
            $table->integer('patient_id'); $table->integer('doctor_id'); $table->string('service_type');
            $table->date('visit_date'); $table->text('notes')->nullable();
        });
        DB::table('patients')->insert(['patient_fname' => 'Sample', 'patient_lname' => 'Patient', 'patient_email' => 'sample@example.com']);
        $response = $this->actingAs($this->account($role))->get("/$prefix/patients/export");
        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('sample@example.com', $response->streamedContent());
    }

    public function test_both_settings_views_render_real_account_information_and_controls(): void
    {
        foreach (['Admin', 'Staff'] as $role) {
            $user = $this->account($role);
            $user->forceFill(['last_login_at' => '2026-10-13 01:00:00', 'session_timeout' => 60])->save();
            $this->actingAs($user);
            $view = $role === 'Admin' ? 'admin_acc.partials.settings' : 'staff.layouts.app';
            $this->view($view, ['errors' => new \Illuminate\Support\ViewErrorBag])->assertSee('Oct 13, 2026, 1:00 AM PHT')->assertSee($user->email)
                ->assertSee('data-account-clear-cache', false)->assertSee('accountLogoutForm');
        }
    }
}
