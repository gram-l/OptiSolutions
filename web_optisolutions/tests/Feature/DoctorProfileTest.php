<?php

namespace Tests\Feature;

use App\Http\Controllers\admin_acc\DoctorController;
use App\Models\admin_models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DoctorProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        foreach (['doctors', 'doctor_schedules'] as $table) {
            (require database_path("migrations/2026_01_01_000001_create_{$table}_table.php"))->up();
        }
        Storage::fake('public');
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'doctor_name' => 'Dr. Test Doctor', 'specialty' => 'Ophthalmology / General Medicine / ENT / Medical Oncology / Pediatrics',
            'gender' => 'female', 'years_experience' => 0,
            'education' => 'Medical school', 'license' => 'PRC-123', 'fellowship' => 'Eye Institute',
            'schedule_sessions' => [['day' => 'Monday', 'start_time' => '08:00', 'end_time' => '12:00']],
        ], $changes);
    }

    public function test_details_round_trip_and_photo_can_be_replaced_then_removed(): void
    {
        $controller = app(DoctorController::class);
        $request = Request::create('/', 'POST', $this->payload());
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a24sAAAAASUVORK5CYII=');
        $request->files->set('profile_image', UploadedFile::fake()->createWithContent('first.png', $image));
        $controller->store($request);
        $doctor = Doctor::firstOrFail();
        $firstPhoto = $doctor->profile_image;
        Storage::disk('public')->assertExists($firstPhoto);
        $listed = $controller->listJson()->getData(true)['doctors'][0];
        foreach (['specialty', 'gender', 'years_experience', 'education', 'license', 'fellowship'] as $field) {
            $this->assertEquals($this->payload()[$field], $listed[$field]);
        }

        $request = Request::create('/', 'POST', $this->payload(['years_experience' => 12, 'education' => null]));
        $request->files->set('profile_image', UploadedFile::fake()->createWithContent('second.png', $image));
        $controller->update($request, $doctor->doctor_id);
        $doctor->refresh();
        $secondPhoto = $doctor->profile_image;
        Storage::disk('public')->assertMissing($firstPhoto);
        Storage::disk('public')->assertExists($secondPhoto);
        $this->assertEquals(12, $doctor->years_experience);
        $this->assertNull($doctor->education);

        $controller->update(Request::create('/', 'POST', $this->payload(['remove_profile_image' => '1'])), $doctor->doctor_id);
        $this->assertNull($doctor->fresh()->profile_image);
        Storage::disk('public')->assertMissing($secondPhoto);
    }

    public function test_negative_and_fractional_experience_and_invalid_gender_are_rejected(): void
    {
        foreach ([['years_experience' => -1], ['years_experience' => 1.5], ['gender' => 'other'], ['specialty' => str_repeat('x', 101)], ['specialty' => '']] as $invalid) {
            try {
                app(DoctorController::class)->store(Request::create('/', 'POST', $this->payload($invalid)));
                $this->fail('Invalid doctor details were accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey(array_key_first($invalid), $exception->errors());
            }
        }
        $this->assertEquals(0, Doctor::count());
    }
}
