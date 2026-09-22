<?php

namespace App\Services;

/**
 * Fallback classifier used ONLY when the Django sentiment-analysis
 * microservice (SentimentAnalysisService) is unreachable or returns
 * a failure. It approximates a sentiment label from the star rating
 * so feedback isn't left permanently unclassified while that service
 * is down.
 *
 * Remove/bypass this once the real ML service is reliably running —
 * SentimentAnalysisService::analyze() is always tried first, this is
 * only ever the backup path.
 */
class RatingSentimentFallback
{
    public static function labelFor(int $starRating): string
    {
        if ($starRating >= 4) {
            return 'Positive';
        }

        if ($starRating === 3) {
            return 'Neutral';
        }

        return 'Negative';
    }
}