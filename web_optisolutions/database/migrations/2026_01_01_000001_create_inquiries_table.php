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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->integer('inquiry_id', true);
            $table->integer('patient_id')->nullable()->index('fk_inquiries_patient');
            $table->string('guest_name')->nullable();
            $table->integer('log_id')->index('fk_inquiries_log');
            $table->string('conversation_id', 191)->nullable()->index('idx_inquiries_conversation_id');
            $table->timestamp('created_at')->nullable();
            $table->string('inquiry_type', 100)->nullable();
            $table->enum('resolved_status', ['Pending', 'In Progress', 'Resolved'])->nullable()->default('Pending');
            $table->text('inquiry_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
