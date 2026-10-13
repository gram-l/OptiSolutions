<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->integer('visit_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropUnique(['visit_id']);
            $table->dropColumn('visit_id');
        });
    }
};
