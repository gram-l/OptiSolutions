<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These are the chatbot's admin-editable "things it can do":
     * simple quick-reply buttons/keywords that trigger a static text
     * reply. The 4 built-in flows (schedule visit, general information,
     * submit complaint, submit review/rating) stay hardcoded in
     * BotManController because they start real multi-step
     * Conversation classes, not a plain text reply — this table is for
     * everything else (FAQs like "parking", "insurance", "walk-ins",
     * etc.) that admins should be able to manage without touching code.
     */
    public function up(): void
    {
        Schema::create('chatbot_commands', function (Blueprint $table) {
            $table->id('command_id');
            $table->string('label', 100);
            // What the bot matches on (button value / typed keyword).
            // Always stored lowercase-trimmed so matching is consistent.
            $table->string('trigger_value', 100)->unique();
            $table->text('reply_text');
            // Show as a quick-reply button on the main greeting menu.
            $table->boolean('show_in_menu')->default(false);
            $table->boolean('is_active')->default(true);
            // Controls button/menu ordering, ascending.
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_commands');
    }
};
