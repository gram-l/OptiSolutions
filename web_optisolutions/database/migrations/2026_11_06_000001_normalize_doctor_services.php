<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('doctors', 'specialty')) return;

        // Existing explicit assignments take precedence over the old, truncated label.
        // Backfill unassigned doctors before removing the duplicate column.
        DB::transaction(function () {
            $services = DB::table('services')->get();
            foreach (DB::table('doctors')->orderBy('doctor_id')->get() as $doctor) {
                if (DB::table('doctor_service')->where('doctor_id', $doctor->doctor_id)->exists()) continue;
                $remaining = trim((string) $doctor->specialty);
                while ($remaining !== '') {
                    // Longest title first preserves titles containing slashes, e.g. General / Adult Medicine.
                    $match = $services->sortByDesc(fn ($service) => mb_strlen($service->title))
                        ->first(fn ($service) => preg_match('/^'.preg_quote($service->title, '/').'(?=\s*[,\/]|$)/iu', $remaining));
                    if ($match) {
                        $title = mb_substr($remaining, 0, mb_strlen($match->title));
                        $id = $match->service_id;
                    } else {
                        $title = trim(preg_split('/\s*[,\/]\s*/', $remaining, 2)[0]);
                        $ids = \App\Support\DoctorServices::idsFromSpecialtyText($title);
                        $id = $ids[0] ?? null;
                        if (!$id) {
                            $id = DB::table('services')->insertGetId([
                                'title' => $title, 'service_key' => 'legacy_'.Str::uuid(),
                                'icon' => 'fa-solid fa-stethoscope', 'available' => true,
                            ], 'service_id');
                            $services->push((object) ['service_id' => $id, 'title' => $title]);
                        }
                    }
                    DB::table('doctor_service')->insertOrIgnore(['doctor_id' => $doctor->doctor_id, 'service_id' => $id]);
                    $remaining = ltrim(mb_substr($remaining, mb_strlen($title)), " \t\n\r\0\x0B,/ ");
                }
            }
        });

        Schema::table('doctors', fn (Blueprint $table) => $table->dropColumn('specialty'));
    }

    public function down(): void
    {
        if (Schema::hasColumn('doctors', 'specialty')) return;
        Schema::table('doctors', fn (Blueprint $table) => $table->text('specialty')->nullable());
        $labels = \App\Support\DoctorServices::withLabels(DB::table('doctors')->get(['doctor_id']));
        foreach ($labels as $doctor) {
            DB::table('doctors')->where('doctor_id', $doctor->doctor_id)->update(['specialty' => $doctor->specialty]);
        }
    }
};
