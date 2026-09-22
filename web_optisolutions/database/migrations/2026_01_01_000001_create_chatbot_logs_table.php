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
        Schema::create('chatbot_logs', function (Blueprint $table) {
            $table->integer('log_id', true);
            $table->unsignedBigInteger('user_id')->nullable()->index('fk_chatbot_logs_user');
            $table->string('conversation_id', 191)->nullable()->index('idx_chatbot_logs_conversation_id');
            $table->text('user_message');
            $table->text('bot_message');
            $table->timestamp('chat_time')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_logs');
    }
};
