<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff can no longer confirm schedule visits, so any visit still "Pending"
 * would stay Pending forever. Bookings are now Confirmed automatically, so
 * existing Pending visits are moved to Confirmed. Cancelled visits are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('schedule_visit', 'status')) {
            DB::table('schedule_visit')->where('status', 'Pending')->update(['status' => 'Confirmed']);
        }
    }

    public function down(): void
    {
        // Cannot tell which visits were Pending before. Nothing to restore.
    }
};
