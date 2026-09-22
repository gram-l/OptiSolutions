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
        Schema::create('patients', function (Blueprint $table) {
            $table->integer('patient_id', true);
            $table->string('patient_fname', 100)->nullable();
            $table->string('patient_lname', 100)->nullable();
            $table->date('patient_birthdate')->nullable();
            $table->string('patient_email')->nullable();
            $table->string('patient_contact', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
