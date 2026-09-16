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
        Schema::table('complaints', function (Blueprint $table) {
            $table->foreign(['patient_id'], 'fk_complaint_patient')->references(['patient_id'])->on('patients')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['log_id'], 'fk_complaints_log')->references(['log_id'])->on('chatbot_logs')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign('fk_complaint_patient');
            $table->dropForeign('fk_complaints_log');
        });
    }
};
