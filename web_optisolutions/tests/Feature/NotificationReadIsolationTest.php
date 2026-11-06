<?php

namespace Tests\Feature;

use App\Models\admin_models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotificationReadIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        foreach (['2026_01_01_000000_create_users_table.php', '2026_01_01_000001_create_app_notifications_table.php',
            '2026_10_13_000002_add_notification_preferences_to_users.php', '2026_11_06_000002_create_notification_reads_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        foreach ([1 => 'Admin', 2 => 'Admin', 3 => 'Staff', 4 => 'Staff'] as $id => $role) {
            DB::table('users')->insert(['user_id' => $id, 'name' => "$role $id", 'email' => "user$id@example.test", 'password' => 'unused', 'user_role' => $role]);
        }
        foreach ([1 => ['appointment', null], 2 => ['chat_inquiry', null], 3 => ['feedback', null],
            4 => ['appointment', 1], 5 => ['appointment', 2], 6 => ['appointment', 3], 7 => ['appointment', 4],
            8 => ['complaint', null], 9 => ['system', null]] as $id => [$type, $owner]) {
            DB::table('app_notifications')->insert(['notification_id' => $id, 'user_id' => $owner, 'type' => $type,
                'title' => "Notification $id", 'message' => "Message $id", 'is_read' => false]);
        }
    }

    public static function accounts(): array
    {
        return ['admins' => [1, 2, 'admin_acc', 'POST', 6, 5], 'staff' => [3, 4, 'staff', 'PUT', 4, 7]];
    }

    private function listing(int $userId, string $prefix): TestResponse
    {
        return $this->actingAs(User::findOrFail($userId))->getJson($prefix === 'staff' ? '/staff/notifications' : '/admin_acc/notifications/api');
    }

    private function mark(int $userId, string $prefix, string $method, int $id): TestResponse
    {
        return $this->actingAs(User::findOrFail($userId))->json($method, "/$prefix/notifications/$id/read");
    }

    private function markAll(int $userId, string $prefix, string $method): TestResponse
    {
        $action = $prefix === 'staff' ? 'read-all' : 'mark-all-read';
        return $this->actingAs(User::findOrFail($userId))->json($method, "/$prefix/notifications/$action");
    }

    #[DataProvider('accounts')]
    public function test_reading_a_shared_notification_does_not_read_it_for_another_account(int $actor, int $peer, string $prefix, string $method, int $count): void
    {
        $this->mark($actor, $prefix, $method, 1)->assertOk();
        $this->mark($actor, $prefix, $method, 1)->assertOk();
        $mine = $this->listing($actor, $prefix)->assertOk()->assertJsonPath('unread_count', $count - 1)->json('notifications');
        $theirs = $this->listing($peer, $prefix)->assertOk()->assertJsonPath('unread_count', $count)->json('notifications');
        $this->assertSame(1, collect($mine)->firstWhere('notification_id', 1)['is_read']);
        $this->assertSame(0, collect($theirs)->firstWhere('notification_id', 1)['is_read']);
        $this->assertSame(1, DB::table('notification_reads')->count());
        $this->assertSame(0, (int) DB::table('app_notifications')->where('notification_id', 1)->value('is_read'));
    }

    #[DataProvider('accounts')]
    public function test_mark_all_only_reads_the_acting_accounts_notifications_and_is_idempotent(int $actor, int $peer, string $prefix, string $method, int $count): void
    {
        $this->markAll($actor, $prefix, $method)->assertOk();
        $this->markAll($actor, $prefix, $method)->assertOk();
        $this->listing($actor, $prefix)->assertOk()->assertJsonPath('unread_count', 0);
        $this->listing($peer, $prefix)->assertOk()->assertJsonPath('unread_count', $count);
        $this->assertSame($count, DB::table('notification_reads')->where('user_id', $actor)->count());
        $this->assertSame(0, DB::table('notification_reads')->where('user_id', $peer)->count());
        $this->assertSame(0, DB::table('app_notifications')->where('is_read', true)->count());
    }

    #[DataProvider('accounts')]
    public function test_other_users_private_notifications_are_hidden_and_cannot_be_marked(int $actor, int $peer, string $prefix, string $method, int $count, int $privateId): void
    {
        $rows = $this->listing($actor, $prefix)->assertOk()->json('notifications');
        $this->assertNotContains($privateId, array_column($rows, 'notification_id'));
        $this->mark($actor, $prefix, $method, $privateId)->assertNotFound();
        $this->mark($actor, $prefix, $method, 9999)->assertNotFound();
        $this->assertSame(0, DB::table('notification_reads')->count());
    }

    #[DataProvider('accounts')]
    public function test_mark_all_respects_disabled_notification_preferences(int $actor, int $peer, string $prefix, string $method): void
    {
        DB::table('users')->where('user_id', $actor)->update(['notification_preferences' => json_encode(['notify_appointments' => false])]);
        $this->markAll($actor, $prefix, $method)->assertOk();
        $this->listing($actor, $prefix)->assertJsonPath('unread_count', 0);
        $this->mark($actor, $prefix, $method, 1)->assertNotFound();
        DB::table('users')->where('user_id', $actor)->update(['notification_preferences' => null]);
        $this->listing($actor, $prefix)->assertJsonPath('unread_count', 2);
    }

    public function test_staff_cannot_read_or_mark_admin_only_notification_types(): void
    {
        $rows = $this->listing(3, 'staff')->assertOk()->json('notifications');
        $this->assertNotContains(3, array_column($rows, 'notification_id'));
        $this->assertNotContains(8, array_column($rows, 'notification_id'));
        foreach ([3, 8] as $id) {
            $this->mark(3, 'staff', 'PUT', $id)->assertNotFound();
            $this->patchJson("/api/notifications/$id/read")->assertNotFound();
        }
    }

    public function test_admin_panel_and_full_page_use_the_current_accounts_read_state(): void
    {
        $this->mark(1, 'admin_acc', 'POST', 1)->assertOk();
        foreach ([1 => 'read', 2 => 'unread'] as $userId => $state) {
            $this->actingAs(User::findOrFail($userId));
            foreach ([[], ['X-Requested-With' => 'XMLHttpRequest']] as $headers) {
                $html = $this->get('/admin_acc/notifications', $headers)->assertOk()->getContent();
                $this->assertMatchesRegularExpression('/class="notif-row '.$state.'"\s+data-category="appointment"\s+data-id="1"/', $html);
                $this->assertStringNotContainsString('Message '.($userId === 1 ? 5 : 4), $html);
            }
        }
    }

    #[DataProvider('accounts')]
    public function test_api_and_web_use_the_same_per_user_read_records_without_changing_response_fields(int $actor, int $peer, string $prefix, string $method, int $count): void
    {
        $this->actingAs(User::findOrFail($actor));
        $items = $this->getJson('/api/notifications')->assertOk()->json();
        $this->assertCount($count, $items);
        $this->patchJson('/api/notifications/1/read')->assertOk()->assertJsonPath('isRead', true)->assertJsonPath('id', 1);
        $this->listing($actor, $prefix)->assertJsonPath('unread_count', $count - 1);
        $this->listing($peer, $prefix)->assertJsonPath('unread_count', $count);
        $this->actingAs(User::findOrFail($actor))->postJson('/api/notifications/mark-all-read')->assertOk();
        $this->listing($actor, $prefix)->assertJsonPath('unread_count', 0);
        $this->listing($peer, $prefix)->assertJsonPath('unread_count', $count);
    }

    public function test_mark_all_includes_notifications_beyond_the_dropdown_limit(): void
    {
        for ($i = 0; $i < 30; $i++) {
            DB::table('app_notifications')->insert(['type' => 'system', 'title' => 'Older alert', 'message' => 'Alert']);
        }
        $this->listing(3, 'staff')->assertJsonCount(20, 'notifications')->assertJsonPath('unread_count', 34);
        $this->markAll(3, 'staff', 'PUT')->assertOk();
        $this->listing(3, 'staff')->assertJsonPath('unread_count', 0);
        $this->listing(4, 'staff')->assertJsonPath('unread_count', 34);
    }

    public function test_migration_preserves_legacy_read_display_for_existing_recipients_only(): void
    {
        $migration = require database_path('migrations/2026_11_06_000002_create_notification_reads_table.php');
        $migration->down();
        DB::table('app_notifications')->whereIn('notification_id', [1, 5])->update(['is_read' => true]);
        $migration->up();
        $this->assertSame(5, DB::table('notification_reads')->count());
        $this->assertSame(2, (int) DB::table('notification_reads')->where('notification_id', 5)->value('user_id'));
        $this->listing(1, 'admin_acc')->assertJsonPath('unread_count', 5);
        $this->listing(2, 'admin_acc')->assertJsonPath('unread_count', 4);
        DB::table('users')->insert(['user_id' => 5, 'name' => 'New Staff', 'email' => 'new@example.test', 'password' => 'unused', 'user_role' => 'Staff']);
        $rows = $this->listing(5, 'staff')->assertOk()->json('notifications');
        $this->assertSame(0, collect($rows)->firstWhere('notification_id', 1)['is_read']);
    }

    public function test_deleting_a_notification_cleans_up_its_read_records(): void
    {
        $this->mark(1, 'admin_acc', 'POST', 1)->assertOk();
        DB::table('app_notifications')->where('notification_id', 1)->delete();
        $this->assertSame(0, DB::table('notification_reads')->count());
    }

    public function test_notification_api_rejects_inactive_accounts_and_non_clinic_roles(): void
    {
        $user = User::findOrFail(3);
        foreach ([['Staff', 'inactive'], ['Patient', 'active']] as [$role, $status]) {
            $user->forceFill(['user_role' => $role, 'status' => $status])->save();
            $this->actingAs($user);
            $this->getJson('/api/notifications')->assertForbidden();
            $this->patchJson('/api/notifications/1/read')->assertForbidden();
            $this->postJson('/api/notifications/mark-all-read')->assertForbidden();
        }
        $this->assertSame(0, DB::table('notification_reads')->count());
    }
}
