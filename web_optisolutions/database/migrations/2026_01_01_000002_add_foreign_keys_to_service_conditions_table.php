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
        Schema::table('service_conditions', function (Blueprint $table) {
            $table->foreign(['service_id'], 'fk_service_conditions_service')->references(['service_id'])->on('services')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_conditions', function (Blueprint $table) {
            $table->dropForeign('fk_service_conditions_service');
        });
    }
};
