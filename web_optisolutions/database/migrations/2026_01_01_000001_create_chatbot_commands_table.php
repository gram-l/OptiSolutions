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
        Schema::create('chatbot_commands', function (Blueprint $table) {
            $table->bigIncrements('command_id');
            $table->string('label', 100);
            $table->string('trigger_value', 100)->unique();
            $table->text('reply_text');
            $table->boolean('show_in_menu')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_commands');
    }
};
