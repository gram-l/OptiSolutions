<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_visit', function (Blueprint $table) {
            $table->boolean('visited')->default(false);
            $table->timestamp('visited_at')->nullable();
            $table->text('review_pass_code')->nullable();
            $table->char('review_pass_hash', 64)->nullable()->unique();
            $table->timestamp('review_pass_sent_at')->nullable();
            $table->timestamp('review_pass_used_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('schedule_visit', function (Blueprint $table) {
            $table->dropUnique(['review_pass_hash']);
            $table->dropColumn(['visited', 'visited_at', 'review_pass_code', 'review_pass_hash', 'review_pass_sent_at', 'review_pass_used_at']);
        });
    }
};
