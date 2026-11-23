<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks the "please confirm you are coming" reminder email.
 *
 *  - reminder_sent_at         when the reminder email went out (null = not yet)
 *  - attendance_response      null (no reply) | 'confirmed' | 'declined'
 *  - attendance_responded_at  when the patient replied
 *
 * Only adds nullable columns. No data is changed or removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_visit', function (Blueprint $table) {
            if (!Schema::hasColumn('schedule_visit', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable();
            }
            if (!Schema::hasColumn('schedule_visit', 'attendance_response')) {
                $table->string('attendance_response', 20)->nullable();
            }
            if (!Schema::hasColumn('schedule_visit', 'attendance_responded_at')) {
                $table->timestamp('attendance_responded_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedule_visit', function (Blueprint $table) {
            foreach (['reminder_sent_at', 'attendance_response', 'attendance_responded_at'] as $column) {
                if (Schema::hasColumn('schedule_visit', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
