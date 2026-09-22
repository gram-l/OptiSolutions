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
        Schema::table('feedback', function (Blueprint $table) {
            $table->foreign(['log_id'], 'fk_feedback_log')->references(['log_id'])->on('chatbot_logs')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['patient_id'], 'fk_feedback_patient')->references(['patient_id'])->on('patients')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropForeign('fk_feedback_log');
            $table->dropForeign('fk_feedback_patient');
        });
    }
};
