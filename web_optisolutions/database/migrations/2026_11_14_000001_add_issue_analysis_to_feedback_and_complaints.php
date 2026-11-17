<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['feedback', 'complaints'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->json('issue_analysis')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['feedback', 'complaints'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('issue_analysis');
            });
        }
    }
};
