<?php

namespace Tests\Feature;

use App\Http\Controllers\admin_acc\DoctorController;
use App\Models\admin_models\Doctor;
use App\Models\admin_models\Service;
use App\Support\DoctorServices;
use App\Support\PublicCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DoctorServiceNormalizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        foreach (['doctors', 'services', 'doctor_schedules'] as $table) {
            (require database_path("migrations/2026_01_01_000001_create_{$table}_table.php"))->up();
        }
        (require database_path('migrations/2026_11_05_000001_create_doctor_service_table.php'))->up();
    }

    private function normalize(): void
    {
        (require database_path('migrations/2026_11_06_000001_normalize_doctor_services.php'))->up();
        (require database_path('migrations/2026_11_06_000002_constrain_doctor_services.php'))->up();
    }

    public function test_backfill_preserves_combined_titles_unknown_specialties_and_existing_assignments(): void
    {
        $general = Service::create(['title' => 'General / Adult Medicine', 'service_key' => 'adultmedicine', 'icon' => 'fa-user']);
        $ent = Service::create(['title' => 'ENT', 'service_key' => 'ent', 'icon' => 'fa-user']);
        DB::table('doctors')->insert([
            ['doctor_id' => 1, 'doctor_name' => 'Doctor A', 'specialty' => 'General / Adult Medicine / ENT'],
            ['doctor_id' => 2, 'doctor_name' => 'Doctor B', 'specialty' => 'New Specialty'],
            ['doctor_id' => 3, 'doctor_name' => 'Doctor C', 'specialty' => 'Stale old label'],
        ]);
        DoctorServices::sync(3, [$ent->service_id]);
        $this->normalize();
        $this->assertFalse(Schema::hasColumn('doctors', 'specialty'));
        $this->assertSame('ENT / General / Adult Medicine', Doctor::findOrFail(1)->specialty);
        $this->assertCount(2, Doctor::findOrFail(1)->services);
        $this->assertSame('New Specialty', Doctor::findOrFail(2)->specialty);
        $this->assertSame('ENT', Doctor::findOrFail(3)->specialty);
        $this->assertDatabaseMissing('services', ['title' => 'Stale old label']);
        $this->assertSame(2, $ent->doctors()->count());
        $this->assertSame(1, PublicCatalog::doctorsForService($general->service_id)->count());
    }

    public function test_create_update_and_rename_use_links_and_full_length_labels(): void
    {
        $this->normalize();
        $one = Service::create(['title' => str_repeat('A', 90), 'service_key' => 'one', 'icon' => 'fa-user']);
        $two = Service::create(['title' => str_repeat('B', 90), 'service_key' => 'two', 'icon' => 'fa-user']);
        $input = ['doctor_name' => 'Doctor Test', 'service_ids' => [$one->service_id, $two->service_id],
            'schedule_sessions' => [['day' => 'Monday', 'start_time' => '08:00', 'end_time' => '12:00']]];
        $controller = app(DoctorController::class);
        $created = $controller->store(Request::create('/', 'POST', $input))->getData(true)['doctor'];
        $this->assertSame($one->title.' / '.$two->title, $created['specialty']);
        $this->assertCount(2, $created['services']);
        $two->update(['title' => 'Renamed service']);
        $this->assertSame($one->title.' / Renamed service', Doctor::findOrFail($created['doctor_id'])->specialty);
        $input['service_ids'] = [$two->service_id];
        $controller->update(Request::create('/', 'POST', $input), $created['doctor_id']);
        $this->assertSame('Renamed service', Doctor::findOrFail($created['doctor_id'])->specialty);
        $this->assertDatabaseCount('doctor_service', 1);
        $two->delete();
        $this->assertDatabaseCount('doctor_service', 0);
        $this->assertDatabaseCount('doctors', 1);
    }

    public function test_foreign_keys_reject_invalid_assignments(): void
    {
        $this->normalize();
        $this->expectException(QueryException::class);
        DB::table('doctor_service')->insert(['doctor_id' => 999, 'service_id' => 999]);
    }

    public function test_rollback_restores_labels_and_doctor_deletion_cascades(): void
    {
        DB::table('doctors')->insert(['doctor_id' => 1, 'doctor_name' => 'Doctor A', 'specialty' => 'ENT']);
        $this->normalize();
        (require database_path('migrations/2026_11_06_000002_constrain_doctor_services.php'))->down();
        (require database_path('migrations/2026_11_06_000001_normalize_doctor_services.php'))->down();
        $this->assertDatabaseHas('doctors', ['doctor_id' => 1, 'specialty' => 'ENT']);
        $this->normalize();
        DB::table('doctors')->where('doctor_id', 1)->delete();
        $this->assertDatabaseCount('doctor_service', 0);
        $this->assertDatabaseCount('services', 1);
    }
}
