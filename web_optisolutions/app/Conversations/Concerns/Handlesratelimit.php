<?php

namespace App\Conversations\Concerns;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Shared spam guard for conversation steps that write a DB record and
 * notify staff (Review, Complaint, Inquiry submission). Keyed by IP so
 * a single visitor can't flood the admin dashboard with junk
 * submissions by repeating the same flow over and over — separate
 * from and stricter than the general per-IP throttle on the /botman
 * route itself, since these specific actions are the ones that create
 * a DB row and an AppNotification for staff to act on.
 */
trait HandlesRateLimit
{
    /**
     * @param string $bucket        Distinguishes review/complaint/inquiry so
     *                               hitting the limit on one doesn't block
     *                               the others.
     * @param int    $maxAttempts   Submissions allowed within the window.
     * @param int    $decayMinutes  Window length in minutes.
     */
    protected function tooManySubmissions(string $bucket, int $maxAttempts = 3, int $decayMinutes = 10): bool
    {
        $key = 'chatbot-submit:' . $bucket . ':' . (request()->ip() ?? 'unknown');

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return true;
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        return false;
    }

    protected function submissionCooldownMessage(): string
    {
        return "You've sent a few of these recently. Please wait a few minutes before submitting again so our staff can catch up.";
    }
}