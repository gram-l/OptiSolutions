<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Ayusin ang `inquiries` table:
 *  - `inquiry_id` ay dapat auto-increment primary key (dati, wala itong
 *    AUTO_INCREMENT/PRIMARY KEY sa orihinal na SQL dump — posibleng
 *    magdulot ito ng duplicate key errors sa susunod na insert).
 *  - `patient_id` at `log_id` ginawang nullable dahil hindi lahat ng
 *    chatbot inquiry ay may kilalang patient (hal. hindi pa nag-schedule
 *    visit ang site visitor kaya wala pang record sa `patients` table).
 *  - Dagdag na `created_at` para sa tamang pag-sort/pag-display ng bagong
 *    inquiries sa Admin/Staff Flutter apps.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Kung wala pang primary key ang inquiry_id (mula sa lumang dump),
        //    gawin itong auto-increment primary key.
        $hasPrimary = collect(DB::select("SHOW KEYS FROM inquiries WHERE Key_name = 'PRIMARY'"))->isNotEmpty();

        if (!$hasPrimary) {
            DB::statement('ALTER TABLE inquiries MODIFY inquiry_id INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (inquiry_id)');
        }

        Schema::table('inquiries', function (Blueprint $table) {
            if (!Schema::hasColumn('inquiries', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('log_id');
            }
        });

        // 2) Gawing nullable ang patient_id at log_id (hiwalay na ALTER
        //    dahil MODIFY hindi covered ng Blueprint->nullable()->change()
        //    kapag walang doctrine/dbal; gamitin na lang ang raw SQL).
        DB::statement('ALTER TABLE inquiries MODIFY patient_id INT NULL');
        DB::statement('ALTER TABLE inquiries MODIFY log_id INT NULL');

        // 3) Dagdagan ang enum ng 'In Progress' — ginagamit na ito ng
        //    InquiryController@sendMessage pero wala pa dati sa enum
        //    kaya sana ay nagfa-fail (o na-truncate) ang pag-save nito.
        DB::statement("ALTER TABLE inquiries MODIFY resolved_status ENUM('Pending','In Progress','Resolved') DEFAULT 'Pending'");

        Schema::table('inquiries', function (Blueprint $table) {
            if (!Schema::hasColumn('inquiries', 'conversation_id')) {
                $table->string('conversation_id', 191)->nullable()->after('log_id');
                $table->index('conversation_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            if (Schema::hasColumn('inquiries', 'created_at')) {
                $table->dropColumn('created_at');
            }
            if (Schema::hasColumn('inquiries', 'conversation_id')) {
                $table->dropIndex(['conversation_id']);
                $table->dropColumn('conversation_id');
            }
        });
    }
};