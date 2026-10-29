<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPatientNotesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        Schema::create('patients', function (Blueprint $t) {
            $t->increments('patient_id');
            $t->string('patient_fname'); $t->string('patient_lname');
            $t->date('patient_birthdate')->nullable();
            $t->string('patient_email')->nullable(); $t->string('patient_contact')->nullable();
        });
        Schema::create('doctors', function (Blueprint $t) {
            $t->increments('doctor_id'); $t->string('doctor_name');
        });
        Schema::create('schedule_visit', function (Blueprint $t) {
            $t->increments('visit_id'); $t->integer('patient_id');
            $t->integer('doctor_id')->nullable(); $t->string('service_type')->nullable();
            $t->date('visit_date'); $t->text('notes')->nullable();
        });
        DB::table('patients')->insert([
            ['patient_id' => 1, 'patient_fname' => 'Ana', 'patient_lname' => 'Reyes'],
            ['patient_id' => 2, 'patient_fname' => 'Other', 'patient_lname' => 'Patient'],
        ]);
        DB::table('schedule_visit')->insert([
            ['visit_id' => 1, 'patient_id' => 1, 'visit_date' => '2026-10-12', 'notes' => 'Older note'],
            ['visit_id' => 2, 'patient_id' => 1, 'visit_date' => '2026-10-19', 'notes' => 'Current note'],
            ['visit_id' => 3, 'patient_id' => 2, 'visit_date' => '2026-10-19', 'notes' => 'Other patient note'],
        ]);
        $this->actingAs(new \App\Models\admin_models\User([
            'name' => 'Admin', 'email' => 'admin@example.test', 'user_role' => 'Admin',
        ]));
    }

    public function test_admin_can_save_and_clear_latest_visit_notes(): void
    {
        $this->getJson('/admin_acc/patients/list')->assertOk()
            ->assertJsonFragment(['notes_visit_id' => 2, 'notes' => 'Current note']);
        foreach (['Updated note', null] as $note) {
            $this->putJson('/admin_acc/patients/1', [
                'patient_fname' => 'Ana', 'patient_lname' => 'Reyes',
                'notes_visit_id' => 2, 'notes' => $note,
            ])->assertOk()->assertJsonPath('success', true);
            $this->assertSame($note, DB::table('schedule_visit')->where('visit_id', 2)->value('notes'));
        }
        $this->assertSame('Older note', DB::table('schedule_visit')->where('visit_id', 1)->value('notes'));
    }

    public function test_invalid_notes_cannot_change_another_patients_visit_or_partially_save_details(): void
    {
        foreach ([['notes_visit_id' => 3, 'notes' => 'Wrong visit'],
            ['notes_visit_id' => 2, 'notes' => str_repeat('x', 2001)],
            ['notes' => null]] as $invalid) {
            $this->putJson('/admin_acc/patients/1', array_merge([
                'patient_fname' => 'Changed', 'patient_lname' => 'Reyes',
            ], $invalid))->assertUnprocessable();
            $this->assertSame('Ana', DB::table('patients')->where('patient_id', 1)->value('patient_fname'));
        }
        $this->assertSame('Other patient note', DB::table('schedule_visit')->where('visit_id', 3)->value('notes'));
        $this->putJson('/admin_acc/patients/1', [
            'patient_fname' => 'Ana', 'patient_lname' => 'Reyes',
        ])->assertOk();
        $this->assertSame('Current note', DB::table('schedule_visit')->where('visit_id', 2)->value('notes'));
    }
}
