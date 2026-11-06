<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyUsersMigrationSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
    }

    public function test_users_migration_still_creates_a_new_database_table(): void
    {
        (require database_path('migrations/2026_01_01_000000_create_users_table.php'))->up();
        DB::table('users')->insert(['name' => 'Existing Admin', 'email' => 'admin@example.test', 'password' => 'preserved-hash', 'user_role' => 'Admin']);
        $this->assertSame('active', DB::table('users')->value('status'));
    }

    public function test_pending_initial_migration_cannot_drop_existing_accounts_or_notifications(): void
    {
        $migration = require database_path('migrations/2026_01_01_000000_create_users_table.php');
        $migration->up();
        (require database_path('migrations/2026_01_01_000001_create_app_notifications_table.php'))->up();
        DB::table('users')->insert(['user_id' => 1, 'name' => 'Existing Admin', 'email' => 'admin@example.test', 'password' => 'preserved-hash', 'user_role' => 'Admin']);
        DB::table('app_notifications')->insert(['user_id' => 1, 'type' => 'system', 'title' => 'Keep notification', 'message' => 'Keep message']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Existing accounts have not been changed.');
        try {
            $migration->up();
        } finally {
            $this->assertSame('preserved-hash', DB::table('users')->where('user_id', 1)->value('password'));
            $this->assertSame('Keep message', DB::table('app_notifications')->value('message'));
        }
    }
}
