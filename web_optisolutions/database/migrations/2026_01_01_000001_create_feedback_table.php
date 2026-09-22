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
        Schema::create('feedback', function (Blueprint $table) {
            $table->integer('feedback_id', true);
            $table->integer('log_id')->nullable()->index('fk_feedback_log');
            $table->integer('patient_id')->nullable()->index('fk_feedback_patient');
            $table->text('feedback_text')->nullable();
            $table->integer('star_rating');
            $table->timestamp('submitted_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
