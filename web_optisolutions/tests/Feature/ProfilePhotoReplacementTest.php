<?php

namespace Tests\Feature;

use App\Models\admin_models\User;
use App\Services\ProfilePhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfilePhotoReplacementTest extends TestCase
{
    private string $oldPath = 'profile_photos/original.png';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        (require database_path('migrations/2026_01_01_000000_create_users_table.php'))->up();
        Storage::fake('public')->put($this->oldPath, 'original image');
        $this->beforeApplicationDestroyed(fn () => User::flushEventListeners());
    }

    public static function endpoints(): array
    {
        return [
            'admin web' => ['Admin', '/admin_acc/profile', 'profile_photo', 302],
            'staff web' => ['Staff', '/staff/profile/photo', 'profile_photo', 302],
            'admin API' => ['Admin', '/api/admin/profile/photo', 'photo', 200],
            'staff API' => ['Staff', '/api/profile/photo', 'photo', 200],
        ];
    }

    private function account(string $role): User
    {
        $user = User::create(['name' => 'Original name', 'email' => 'user@example.test', 'password' => 'test-password', 'user_role' => $role]);
        $user->forceFill(['profile_photo' => $this->oldPath])->save();
        return $user->refresh();
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('replacement.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a24sAAAAASUVORK5CYII='));
    }

    private function upload(User $user, string $url, string $field): TestResponse
    {
        return $this->actingAs($user)->post($url, [
            'name' => 'Updated name', 'email' => $user->email, $field => $this->image(),
        ]);
    }

    private function assertOriginalRemains(User $user): void
    {
        $this->assertSame($this->oldPath, $user->fresh()->profile_photo);
        $this->assertSame('Original name', $user->fresh()->name);
        Storage::disk('public')->assertExists($this->oldPath);
        $this->assertCount(1, Storage::disk('public')->allFiles('profile_photos'));
    }

    #[DataProvider('endpoints')]
    public function test_old_photo_is_deleted_only_after_the_new_path_is_committed(string $role, string $url, string $field, int $status): void
    {
        $user = $this->account($role);
        $disk = Storage::disk('public');
        $committedBeforeDeletion = false;
        $wrapped = \Mockery::mock($disk);
        $wrapped->shouldReceive('delete')->with($this->oldPath)->once()->andReturnUsing(function ($path) use ($disk, $user, &$committedBeforeDeletion) {
            $newPath = DB::table('users')->where('user_id', $user->getKey())->value('profile_photo');
            $committedBeforeDeletion = DB::transactionLevel() === 0 && $newPath !== $this->oldPath && $disk->exists($newPath);
            return $disk->delete($path);
        });
        Storage::set('public', $wrapped);
        $this->upload($user, $url, $field)->assertStatus($status);
        $this->assertTrue($committedBeforeDeletion);
        $newPath = $user->fresh()->profile_photo;
        $disk->assertExists($newPath);
        $disk->assertMissing($this->oldPath);
        $this->assertCount(1, $disk->allFiles('profile_photos'));
    }

    #[DataProvider('endpoints')]
    public function test_failed_storage_preserves_the_previous_photo_and_profile(string $role, string $url, string $field): void
    {
        $user = $this->account($role);
        $disk = \Mockery::mock(Storage::disk('public'));
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::set('public', $disk);
        $this->upload($user, $url, $field)->assertStatus(500);
        $this->assertOriginalRemains($user);
    }

    #[DataProvider('endpoints')]
    public function test_database_failure_preserves_old_photo_and_removes_new_upload(string $role, string $url, string $field): void
    {
        $user = $this->account($role);
        User::saving(fn () => throw new \RuntimeException('Simulated database save failure'));
        $this->upload($user, $url, $field)->assertStatus(500);
        $this->assertOriginalRemains($user);
    }

    #[DataProvider('endpoints')]
    public function test_cancelled_model_save_is_not_treated_as_a_successful_replacement(string $role, string $url, string $field): void
    {
        $user = $this->account($role);
        User::saving(fn () => false);
        $this->upload($user, $url, $field)->assertStatus(500);
        $this->assertOriginalRemains($user);
    }

    #[DataProvider('endpoints')]
    public function test_old_file_cleanup_failure_keeps_the_successfully_saved_new_photo(string $role, string $url, string $field, int $status): void
    {
        $user = $this->account($role);
        $disk = Storage::disk('public');
        $wrapped = \Mockery::mock($disk);
        $wrapped->shouldReceive('delete')->with($this->oldPath)->once()->andThrow(new \RuntimeException('Simulated cleanup failure'));
        Storage::set('public', $wrapped);
        $this->upload($user, $url, $field)->assertStatus($status);
        $this->assertNotSame($this->oldPath, $user->fresh()->profile_photo);
        $disk->assertExists($user->fresh()->profile_photo);
    }

    public function test_editing_admin_details_without_an_upload_keeps_the_existing_photo(): void
    {
        $user = $this->account('Admin');
        $this->actingAs($user)->post('/admin_acc/profile', [
            'name' => 'Updated name', 'email' => $user->email, 'profile_photo' => null,
        ])->assertRedirect()->assertSessionHas('profile_success');
        $this->assertSame($this->oldPath, $user->fresh()->profile_photo);
        $this->assertSame('Updated name', $user->fresh()->name);
        Storage::disk('public')->assertExists($this->oldPath);
    }

    public function test_a_stale_request_uses_the_current_database_photo_for_cleanup(): void
    {
        $user = $this->account('Admin');
        $staleUser = $user->fresh();
        $service = app(ProfilePhotoService::class);
        $first = $service->replace($user, $this->image());
        $second = $service->replace($staleUser, $this->image());
        $this->assertSame($second, $user->fresh()->profile_photo);
        Storage::disk('public')->assertExists($second);
        Storage::disk('public')->assertMissing($first);
        $this->assertCount(1, Storage::disk('public')->allFiles('profile_photos'));
    }
}
