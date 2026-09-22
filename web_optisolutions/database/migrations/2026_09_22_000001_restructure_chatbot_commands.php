<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. New child table: one row per trigger word/phrase, so a
        //    command can have multiple triggers instead of just one.
        Schema::create('chatbot_command_triggers', function (Blueprint $table) {
            $table->bigIncrements('trigger_id');
            $table->unsignedBigInteger('command_id');
            $table->string('trigger_value', 100)->unique();
            $table->timestamps();

            $table->foreign('command_id')
                ->references('command_id')->on('chatbot_commands')
                ->onDelete('cascade');
        });

        // 2. Copy each existing single trigger_value into the new table
        //    before the column is dropped below.
        DB::table('chatbot_commands')->select('command_id', 'trigger_value', 'created_at', 'updated_at')
            ->orderBy('command_id')
            ->get()
            ->each(function ($command) {
                DB::table('chatbot_command_triggers')->insert([
                    'command_id'    => $command->command_id,
                    'trigger_value' => $command->trigger_value,
                    'created_at'    => $command->created_at,
                    'updated_at'    => $command->updated_at,
                ]);
            });

        // 3. Drop the now-migrated trigger_value column, plus
        //    show_in_menu and sort_order (both unused — never
        //    wired into any menu-building or ordering logic).
        Schema::table('chatbot_commands', function (Blueprint $table) {
            $table->dropUnique(['trigger_value']);
            $table->dropColumn(['trigger_value', 'show_in_menu', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Restore the dropped columns.
        Schema::table('chatbot_commands', function (Blueprint $table) {
            $table->string('trigger_value', 100)->nullable()->after('label');
            $table->boolean('show_in_menu')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
        });

        // 2. Restore one trigger_value per command from the child table.
        //    If a command ended up with more than one trigger while this
        //    migration was applied, only the first (lowest trigger_id)
        //    is kept — the rest are lost, since the old column only
        //    ever held a single value.
        DB::table('chatbot_command_triggers')
            ->orderBy('trigger_id')
            ->get()
            ->groupBy('command_id')
            ->each(function ($triggers, $commandId) {
                DB::table('chatbot_commands')
                    ->where('command_id', $commandId)
                    ->update(['trigger_value' => $triggers->first()->trigger_value]);
            });

        Schema::table('chatbot_commands', function (Blueprint $table) {
            $table->string('trigger_value', 100)->nullable(false)->unique()->change();
        });

        // 3. Drop the child table.
        Schema::dropIfExists('chatbot_command_triggers');
    }
};