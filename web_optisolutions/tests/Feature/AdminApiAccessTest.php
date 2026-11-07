<?php

namespace Tests\Feature;

use App\Models\admin_models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiAccessTest extends TestCase
{
    private function assertAdminRoutesReject(int $status): void
    {
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (!str_starts_with($uri, 'api/admin/') || str_starts_with($uri, 'api/admin/forgot-password/')) {
                continue;
            }
            $url = '/' . preg_replace('/\{[^}]+\}/', '1', $uri);
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $this->json($method, $url)->assertStatus($status);
                $checked++;
            }
        }
        $this->assertGreaterThan(30, $checked);
        $this->getJson('/feedback')->assertStatus($status);
    }

    public function test_anonymous_requests_cannot_access_any_admin_records_or_actions(): void
    {
        $this->assertAdminRoutesReject(401);
    }

    public function test_staff_cannot_access_admin_apis(): void
    {
        Sanctum::actingAs((new User())->forceFill(['user_id' => 1, 'user_role' => 'Staff', 'status' => 'active']));
        $this->assertAdminRoutesReject(403);
    }

    public function test_inactive_admin_cannot_access_admin_apis(): void
    {
        Sanctum::actingAs((new User())->forceFill(['user_id' => 1, 'user_role' => 'Admin', 'status' => 'inactive']));
        $this->assertAdminRoutesReject(403);
    }

    public function test_active_admin_can_still_read_patients_and_update_visit_notes(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        Schema::create('patients', function (Blueprint $t) {
            $t->increments('patient_id'); $t->string('patient_fname'); $t->string('patient_lname');
        });
        Schema::create('schedule_visit', function (Blueprint $t) {
            $t->increments('visit_id'); $t->text('notes')->nullable();
        });
        DB::table('patients')->insert(['patient_fname' => 'Audit', 'patient_lname' => 'Patient']);
        DB::table('schedule_visit')->insert(['notes' => 'Original note']);
        Sanctum::actingAs((new User())->forceFill(['user_id' => 1, 'user_role' => 'Admin', 'status' => 'active']));
        $this->getJson('/api/admin/patients')->assertOk()->assertJsonFragment(['patient_fname' => 'Audit']);
        $this->patchJson('/api/admin/visits/1/notes', ['notes' => 'Authorized update'])->assertOk();
        $this->assertSame('Authorized update', DB::table('schedule_visit')->value('notes'));
    }
}
