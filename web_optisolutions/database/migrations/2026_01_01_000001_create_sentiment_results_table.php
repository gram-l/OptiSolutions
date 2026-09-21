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
        Schema::create('sentiment_results', function (Blueprint $table) {
            $table->integer('sentiment_id', true);
            $table->integer('feedback_id')->unique('uq_feedback');
            $table->enum('sentiment_label', ['Positive', 'Neutral', 'Negative']);
            $table->decimal('confidence_score', 4, 3);
            $table->timestamp('analyzed_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentiment_results');
    }
};
