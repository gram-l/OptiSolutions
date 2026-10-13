<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('schedule_visit', function (Blueprint $table) {
            $table->time('visit_time')->nullable();
            $table->time('visit_end_time')->nullable();
        });
    }
    public function down(): void {
        Schema::table('schedule_visit', fn (Blueprint $table) => $table->dropColumn(['visit_time', 'visit_end_time']));
    }
};
