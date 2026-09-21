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
        Schema::table('inquiry_replies', function (Blueprint $table) {
            $table->foreign(['inquiry_id'], 'fk_inquiry_reply')->references(['inquiry_id'])->on('inquiries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiry_replies', function (Blueprint $table) {
            $table->dropForeign('fk_inquiry_reply');
        });
    }
};
