<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoService
{
    public function replace(Model $user, UploadedFile $photo, array $attributes = []): string
    {
        $path = $photo->store('profile_photos', 'public');
        if (!is_string($path) || $path === '') {
            throw new \RuntimeException('The new profile photo could not be stored.');
        }

        try {
            $saved = $user->getConnection()->transaction(function () use ($user, $path, $attributes) {
                // Read the current path under a lock so concurrent uploads do
                // not delete another request's winning photo or leak its file.
                $current = $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
                $previous = $current->profile_photo;
                if (!$current->forceFill(array_merge($attributes, ['profile_photo' => $path]))->save()) {
                    throw new \RuntimeException('The profile update could not be saved.');
                }

                if ($previous && $previous !== $path) {
                    $user->getConnection()->afterCommit(fn () => $this->deleteSafely($previous));
                }

                return $current;
            });
        } catch (\Throwable $exception) {
            // A failed database save must leave the previous photo untouched.
            $this->deleteSafely($path);
            throw $exception;
        }

        $user->setRawAttributes($saved->getAttributes(), true);

        return $path;
    }

    private function deleteSafely(string $path): void
    {
        try {
            if (Storage::disk('public')->delete($path)) return;
        } catch (\Throwable $exception) {
            // A cleanup failure must not undo a successfully saved replacement.
        }

        try {
            Log::warning('Could not clean up a replaced profile photo.', ['path' => $path]);
        } catch (\Throwable $exception) {
            // Cleanup and logging must never invalidate the saved photo.
        }
    }
}
