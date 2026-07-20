<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sentiment_results', function (Blueprint $table) {
            $table->id('sentiment_id');
            $table->unsignedInteger('feedback_id');
            $table->string('sentiment_label', 50); // e.g. "Positive", "Negative", "Neutral"
            $table->decimal('confidence_score', 5, 4)->nullable(); // 0.0000–1.0000
            $table->timestamp('analyzed_at')->nullable();

            $table->foreign('feedback_id')
                ->references('feedback_id')->on('feedback')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sentiment_results');
    }
};