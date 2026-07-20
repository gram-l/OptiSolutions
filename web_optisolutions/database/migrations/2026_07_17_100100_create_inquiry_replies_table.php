<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bagong table para sa buong thread ng mensahe sa pagitan ng
 * patient/chatbot at ng Admin/Staff, kapalit ng dating iisang
 * `inquiries.inquiry_reply` column (na isang reply lang ang kaya).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_replies', function (Blueprint $table) {
            $table->id('reply_id');
            $table->integer('inquiry_id');
            $table->unsignedInteger('user_id')->nullable(); // Admin/Staff na sumagot (users.user_id)
            $table->string('sender', 100)->default('Staff'); // e.g. "Staff", "Admin", "Patient"
            $table->boolean('is_staff')->default(true);
            $table->text('message');
            $table->timestamps();

            $table->index('inquiry_id');
            $table->foreign('inquiry_id')->references('inquiry_id')->on('inquiries')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_replies');
    }
};