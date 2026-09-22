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
        Schema::create('clinic_info', function (Blueprint $table) {
            $table->integer('clinic_id')->primary();
            $table->string('clinic_name', 200);
            $table->text('address')->nullable();
            $table->string('contact_no', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('operating_hours')->nullable();
            $table->year('founded_year')->nullable();
            $table->text('about_us')->nullable();
            $table->text('mission')->nullable();
            $table->text('vision')->nullable();
            $table->json('core_values')->nullable();
            $table->string('facebook_link')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('website')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_info');
    }
};
