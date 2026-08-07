<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentimentAnalysisService
{
    public function analyze(string $text): ?array
    {
        try {
            $response = Http::timeout(5)->post(
                config('services.django.url') . '/api/predict/',
                ['feedback_text' => $text]
            );

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Sentiment API non-success', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::error('Sentiment API call failed: ' . $e->getMessage());
        }

        return null; // caller decides how to handle a failed analysis
    }

    public function categorize(string $text): ?string
    {
        try {
            $response = Http::timeout(5)->post(
                config('services.django.url') . '/api/categorize/',
                ['complaint_text' => $text]
            );

            if ($response->successful()) {
                return $response->json()['category'] ?? null;
            }

            Log::warning('Categorize API non-success', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::error('Categorize API call failed: ' . $e->getMessage());
        }

        return null;
    }
}