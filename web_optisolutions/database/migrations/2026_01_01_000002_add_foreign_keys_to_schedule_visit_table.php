<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedule_visit', function (Blueprint $table) {
            $table->foreign(['doctor_id'], 'fk_schedule_visit_doctor')->references(['doctor_id'])->on('doctors')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['patient_id'], 'fk_schedule_visit_patient')->references(['patient_id'])->on('patients')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_visit', function (Blueprint $table) {
            $table->dropForeign('fk_schedule_visit_doctor');
            $table->dropForeign('fk_schedule_visit_patient');
        });
    }
};
