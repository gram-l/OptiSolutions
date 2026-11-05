<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $orphans = DB::table('doctor_service as ds')
            ->leftJoin('doctors as d', 'd.doctor_id', '=', 'ds.doctor_id')
            ->leftJoin('services as s', 's.service_id', '=', 'ds.service_id')
            ->where(fn ($q) => $q->whereNull('d.doctor_id')->orWhereNull('s.service_id'))->exists();
        if ($orphans) throw new RuntimeException('Resolve orphaned doctor_service assignments before adding foreign keys.');

        // Match the signed INT primary keys of doctors and services.
        Schema::table('doctor_service', function (Blueprint $table) {
            $table->integer('doctor_id')->change();
            $table->integer('service_id')->change();
        });
        Schema::table('doctor_service', function (Blueprint $table) {
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->cascadeOnDelete();
            $table->foreign('service_id')->references('service_id')->on('services')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('doctor_service', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropForeign(['service_id']);
        });
        Schema::table('doctor_service', function (Blueprint $table) {
            $table->unsignedBigInteger('doctor_id')->change();
            $table->unsignedBigInteger('service_id')->change();
        });
    }
};
