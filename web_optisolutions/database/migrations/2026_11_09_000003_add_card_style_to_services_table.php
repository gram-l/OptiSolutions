<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lets the admin give every service its own card on the website:
// a picture and a colour (the icon column already exists).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'card_image')) {
                $table->string('card_image', 255)->nullable();
            }
            if (!Schema::hasColumn('services', 'card_color')) {
                $table->string('card_color', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            foreach (['card_image', 'card_color'] as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
