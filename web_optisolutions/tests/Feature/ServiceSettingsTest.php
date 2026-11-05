<?php

namespace Tests\Feature;

use App\Models\admin_models\Service;
use App\Models\admin_models\User;
use App\Support\DoctorSpecialties;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        (require database_path('migrations/2026_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_01_01_000001_create_services_table.php'))->up();
        (require database_path('migrations/2026_11_05_000001_create_doctor_service_table.php'))->up();
        Schema::create('doctors', function (Blueprint $table) {
            $table->increments('doctor_id'); $table->string('doctor_name');
        });
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-password', 'user_role' => 'Admin']));
    }

    private function service(string $title = 'Pediatrics'): Service
    {
        return Service::create(['title' => $title, 'service_key' => 'pediatrics', 'icon' => 'fa-child', 'available' => true]);
    }

    public function test_add_without_icon_or_schedule_returns_updated_table_and_filter_option(): void
    {
        $this->postJson('/admin_acc/system_settings/services', ['title' => ' ENT ', 'room' => 'Room 201', 'available' => 1])
            ->assertOk()->assertJsonPath('message', 'Service added.')->assertJsonStructure(['html']);
        $this->assertDatabaseHas('services', ['title' => 'ENT', 'icon' => 'fa-solid fa-stethoscope', 'schedule' => null]);
        $this->assertContains('ENT', DoctorSpecialties::catalog()->all(), 'Services with no assigned doctor are filter options');
    }

    public function test_renaming_preserves_key_and_links_and_refreshes_doctor_specialties(): void
    {
        $service = $this->service();
        DB::table('doctors')->insert(['doctor_id' => 1, 'doctor_name' => 'Doctor A']);
        DB::table('doctor_service')->insert(['doctor_id' => 1, 'service_id' => $service->service_id]);
        $this->putJson('/admin_acc/system_settings/services/'.$service->service_id, ['title' => 'Child Health', 'available' => 0])
            ->assertOk()->assertJsonPath('message', 'Service updated.');
        $this->assertSame('Child Health', \App\Models\admin_models\Doctor::findOrFail(1)->specialty);
        $this->assertDatabaseHas('services', ['title' => 'Child Health', 'service_key' => 'pediatrics', 'available' => 0, 'icon' => 'fa-child']);
        $this->assertContains('Child Health', DoctorSpecialties::catalog()->all());
        $this->assertNotContains('Pediatrics', DoctorSpecialties::catalog()->all());
    }

    public function test_delete_detaches_links_and_keeps_doctors_and_other_services(): void
    {
        $service = $this->service();
        $other = Service::create(['title' => 'ENT', 'service_key' => 'ent', 'icon' => 'fa-hospital']);
        DB::table('doctors')->insert(['doctor_id' => 1, 'doctor_name' => 'Doctor A']);
        foreach ([$service, $other] as $item) DB::table('doctor_service')->insert(['doctor_id' => 1, 'service_id' => $item->service_id]);
        $this->deleteJson('/admin_acc/system_settings/services/'.$service->service_id)->assertOk();
        $this->assertDatabaseMissing('doctor_service', ['service_id' => $service->service_id]);
        $this->assertSame('ENT', \App\Models\admin_models\Doctor::findOrFail(1)->specialty);
        $this->assertSame(['ENT'], DoctorSpecialties::catalog()->all());
    }

    public function test_duplicate_and_blank_titles_are_rejected_and_edit_can_keep_its_title(): void
    {
        $service = $this->service();
        $this->postJson('/admin_acc/system_settings/services', ['title' => ' pediatrics '])->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->postJson('/admin_acc/system_settings/services', ['title' => '   '])->assertUnprocessable();
        $this->postJson('/admin_acc/system_settings/services', ['title' => ['invalid']])->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->putJson('/admin_acc/system_settings/services/'.$service->service_id, ['title' => 'Pediatrics'])->assertOk();
        $this->assertSame(1, Service::count());
    }

    public function test_slug_collisions_get_distinct_short_keys(): void
    {
        foreach (['A+B', 'A B'] as $title) $this->postJson('/admin_acc/system_settings/services', ['title' => $title])->assertOk();
        $keys = Service::pluck('service_key')->all();
        $this->assertCount(2, array_unique($keys));
        foreach ($keys as $key) $this->assertLessThanOrEqual(50, strlen($key));
    }

    public function test_regular_submission_returns_to_services_tab(): void
    {
        $this->post('/admin_acc/system_settings/services', ['title' => 'ENT'])
            ->assertRedirect(route('system_settings.index', ['tab' => 'services']))->assertSessionHas('success');
    }
}
