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
        Schema::create('services', function (Blueprint $table) {
            $table->integer('service_id', true);
            $table->string('service_key', 50);
            $table->string('title', 100);
            $table->string('icon', 50);
            $table->text('description')->nullable();
            $table->string('room', 50)->nullable();
            $table->string('schedule')->nullable();
            $table->boolean('available')->nullable()->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
