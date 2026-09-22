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
        Schema::table('sentiment_results', function (Blueprint $table) {
            $table->foreign(['feedback_id'], 'fk_sentiment_feedback')->references(['feedback_id'])->on('feedback')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sentiment_results', function (Blueprint $table) {
            $table->dropForeign('fk_sentiment_feedback');
        });
    }
};
