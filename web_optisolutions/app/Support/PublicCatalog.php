<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

// Manages the doctors and services displayed on the website and chatbot.
class PublicCatalog
{
    // Stores the doctors available for chatbot interactions.
    public static function doctors(): Builder
    {
        return DB::table('doctors')->where('doctors.available', 1);
    }

    // Stores the services available for chatbot interactions.
    public static function services(): Builder
    {
        return DB::table('services')->where('services.available', 1);
    }
}