<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a status to every schedule visit (Pending -> Confirmed, or Cancelled).
 *
 * Existing rows are treated as Confirmed (they were already accepted before
 * statuses existed). The chatbot saves new requests as Pending until the
 * clinic confirms them. Pending and Confirmed visits hold their slot;
 * Cancelled visits release it.
 *
 * Only adds a column and an index. No data is changed or removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('schedule_visit', 'status')) {
            Schema::table('schedule_visit', function (Blueprint $table) {
                $table->string('status', 20)->default('Confirmed');
            });
        }

        Schema::table('schedule_visit', function (Blueprint $table) {
            $table->index(['doctor_id', 'visit_date', 'visit_time'], 'idx_schedule_visit_slot');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_visit', function (Blueprint $table) {
            $table->dropIndex('idx_schedule_visit_slot');
        });

        if (Schema::hasColumn('schedule_visit', 'status')) {
            Schema::table('schedule_visit', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
