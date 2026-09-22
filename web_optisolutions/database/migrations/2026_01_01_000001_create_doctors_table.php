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
        Schema::create('doctors', function (Blueprint $table) {
            $table->integer('doctor_id', true);
            $table->string('doctor_name', 150);
            $table->string('specialty', 100);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->text('description')->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->integer('years_experience')->nullable();
            $table->string('education')->nullable();
            $table->string('license')->nullable();
            $table->string('clinic_room', 100)->nullable();
            $table->string('fellowship')->nullable();
            $table->string('profile_image')->nullable();
            $table->boolean('available')->nullable()->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
