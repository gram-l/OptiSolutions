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
        Schema::create('inquiry_replies', function (Blueprint $table) {
            $table->bigIncrements('reply_id');
            $table->integer('inquiry_id')->index('idx_inquiry_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('sender', 100)->default('Staff');
            $table->boolean('is_staff')->default(true);
            $table->text('message');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_replies');
    }
};
