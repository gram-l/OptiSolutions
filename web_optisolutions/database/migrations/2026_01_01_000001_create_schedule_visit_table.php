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
        Schema::create('schedule_visit', function (Blueprint $table) {
            $table->integer('visit_id', true);
            $table->integer('doctor_id')->nullable()->index('fk_schedule_visit_doctor');
            $table->integer('patient_id')->nullable()->index('fk_schedule_visit_patient');
            $table->string('service_type', 100)->nullable();
            $table->date('visit_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('scheduled_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_visit');
    }
};
