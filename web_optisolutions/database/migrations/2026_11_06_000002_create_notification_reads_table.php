<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_reads', function (Blueprint $table) {
            $table->unsignedInteger('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at');
            $table->primary(['notification_id', 'user_id']);
            $table->index(['user_id', 'notification_id']);
            $table->foreign('notification_id')->references('notification_id')->on('app_notifications')->cascadeOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
        });

        // The old shared flag cannot identify who actually read a broadcast.
        // Preserve its current display for existing recipients as a baseline;
        // every subsequent read is recorded for only the acting account.
        DB::table('notification_reads')->insertUsing(['notification_id', 'user_id', 'read_at'],
            DB::table('app_notifications as n')->crossJoin('users as u')
                ->where('n.is_read', true)
                ->whereIn(DB::raw('LOWER(u.user_role)'), ['admin', 'staff'])
                ->where(fn ($q) => $q->whereNull('n.user_id')->orWhereColumn('n.user_id', 'u.user_id'))
                ->select('n.notification_id', 'u.user_id')
                ->selectRaw('COALESCE(n.updated_at, n.created_at, CURRENT_TIMESTAMP) as read_at')
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
    }
};
