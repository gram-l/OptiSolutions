<?php
// app/Services/TypingStatusService.php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

// Tracks whether someone is typing, backed by cache for short-lived data.
class TypingStatusService
{
    // Flag expires automatically in case it's never cleared.
    private const TTL_SECONDS = 6;

    // Builds the cache key.
    private static function cacheKey(string $role, string $conversationId): string
    {
        return "typing:{$role}:{$conversationId}";
    }

    // Sets or clears the typing flag.
    public static function setTyping(string $role, string $conversationId, bool $isTyping): void
    {
        $key = self::cacheKey($role, $conversationId);

        if ($isTyping) {
            Cache::put($key, true, self::TTL_SECONDS);
        } else {
            Cache::forget($key);
        }
    }

    // Checks whether the typing flag is currently set.
    public static function isTyping(string $role, string $conversationId): bool
    {
        return (bool) Cache::get(self::cacheKey($role, $conversationId), false);
    }
}