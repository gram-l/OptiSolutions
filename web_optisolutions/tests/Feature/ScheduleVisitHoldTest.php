<?php

namespace Tests\Feature;

use App\Models\Staff\Inquiry;
use App\Models\Staff\InquiryReply;
use App\Services\ScheduleVisitHold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Schedule Visit "hold": a patient who chats while a schedule-visit form is open
 * gets the form paused (locked, nothing typed is lost) until the bot's answer is
 * acknowledged or staff resolves the inquiry. See App\Services\ScheduleVisitHold.
 */
class ScheduleVisitHoldTest extends TestCase
{
    use RefreshDatabase;

    private string $cid = 'test-chat-1';

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();   // sentiment / translation APIs: never hit the network
        Mail::fake();

        DB::table('services')->insert(['service_id' => 1, 'service_key' => 'pediatrics', 'title' => 'Pediatrics', 'icon' => 'x', 'available' => 1]);
        DB::table('doctors')->insert(['doctor_id' => 1, 'doctor_name' => 'Dr. Reyes', 'available' => 1]);
        DB::table('doctor_service')->insert(['doctor_id' => 1, 'service_id' => 1]);
        DB::table('doctor_schedules')->insert(['schedule_id' => 7, 'doctor_id' => 1, 'day' => 'Monday', 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
    }

    // helpers

    /** POST /botman and return the decoded messages (BotMan's web driver echoes the JSON itself). */
    private function chat(string $message, array $extra = []): array
    {
        ob_start();
        $this->postJson('/botman', array_merge(['driver' => 'web', 'message' => $message, 'userId' => $this->cid], $extra));
        $raw = ob_get_clean();

        $decoded = json_decode($raw, true);
        $this->assertIsArray($decoded, 'BotMan returned no JSON: ' . $raw);

        return $decoded['messages'] ?? [];
    }

    private function texts(array $messages): array
    {
        return array_map(fn ($m) => (string) ($m['text'] ?? ''), $messages);
    }

    private function form(array $messages): ?array
    {
        foreach ($this->texts($messages) as $t) {
            if (str_starts_with($t, 'FORM_CARD::')) {
                return json_decode(substr($t, strlen('FORM_CARD::')), true);
            }
        }
        return null;
    }

    private function marker(array $messages): ?array
    {
        foreach ($this->texts($messages) as $t) {
            if (str_starts_with($t, ScheduleVisitHold::MARKER_PREFIX)) {
                return json_decode(substr($t, strlen(ScheduleVisitHold::MARKER_PREFIX)), true);
            }
        }
        return null;
    }

    private function buttonValues(array $messages): array
    {
        $values = [];
        foreach ($messages as $m) {
            foreach (($m['actions'] ?? $m['attachment']['buttons'] ?? []) as $b) {
                $values[] = $b['value'] ?? null;
            }
        }
        return $values;
    }

    private function fieldValues(array $form): array
    {
        $out = [];
        foreach ($form['fields'] as $f) {
            $out[$f['name']] = $f['value'];
        }
        return $out;
    }

    private function poll(): array
    {
        return $this->getJson("/api/chat/{$this->cid}/updates")->assertOk()->json();
    }

    private function hold(): array
    {
        return $this->poll()['scheduleHold'];
    }

    /** Opens the patient-details form (step 1). */
    private function startVisit(): array
    {
        $form = $this->form($this->chat('schedule visit'));
        $this->assertSame('Your details', $form['title']);
        return $form;
    }

    private function submitDetails(): array
    {
        return $this->chat('FORM_SUBMIT::' . json_encode([
            'full_name' => 'Maria Santos', 'phone' => '09171234568', 'dob' => '1990-05-15',
            'email' => 'maria@example.com', 'send_copy' => true,
        ]));
    }

    private function resolveInquiry(int $id): void
    {
        Inquiry::where('inquiry_id', $id)->update(['resolved_status' => 'Resolved']);
    }

    // 1) the bot can answer -> form paused, the 3 buttons come right away

    public function test_bot_answer_pauses_the_form_and_shows_the_three_buttons_immediately(): void
    {
        $this->startVisit();

        $reply = $this->chat('thanks', ['formDraft' => ['full_name' => 'Maria San', 'phone' => '0917', 'dob' => '', 'email' => '', 'send_copy' => false]]);

        $this->assertNull($this->form($reply), 'the form must not be re-sent while on hold');
        $this->assertSame('bot', $this->marker($reply)['mode']);
        $this->assertSame([ScheduleVisitHold::CONTINUE, ScheduleVisitHold::RESTART, ScheduleVisitHold::CANCEL], $this->buttonValues($reply));
        $this->assertContains(ScheduleVisitHold::CHOICES_QUESTION, $this->texts($reply));

        $hold = $this->hold();
        $this->assertTrue($hold['active']);
        $this->assertSame('bot', $hold['mode']);
        $this->assertTrue($hold['ready']);
        $this->assertSame('patient_form', $hold['step']);
        $this->assertSame(0, Inquiry::count(), 'a bot answer is not an inquiry for staff');
    }

    public function test_clinic_info_question_is_answered_by_the_bot_and_pauses_the_form(): void
    {
        $this->startVisit();

        $reply = $this->chat('Where is the clinic located?');

        $this->assertNull($this->form($reply));
        $this->assertSame('bot', $this->marker($reply)['mode']);
        $this->assertSame([ScheduleVisitHold::CONTINUE, ScheduleVisitHold::RESTART, ScheduleVisitHold::CANCEL], $this->buttonValues($reply));
        $this->assertSame(0, Inquiry::count());
    }

    public function test_continue_returns_to_the_same_step_with_everything_the_patient_had_typed(): void
    {
        $this->startVisit();
        $this->chat('thanks', ['formDraft' => ['full_name' => 'Maria San', 'phone' => '0917555', 'dob' => '1990-05-15', 'email' => 'm@x', 'send_copy' => false]]);

        $form = $this->form($this->chat(ScheduleVisitHold::CONTINUE));

        $this->assertSame('Your details', $form['title'], 'same step');
        $this->assertSame('Step 1 of 3', $form['step']);
        $values = $this->fieldValues($form);
        $this->assertSame('Maria San', $values['full_name']);
        $this->assertSame('0917555', $values['phone']);
        $this->assertSame('1990-05-15', $values['dob']);
        $this->assertSame('m@x', $values['email']);
        $this->assertFalse($values['send_copy']);
        $this->assertFalse($this->hold()['active'], 'the hold is released once they continue');
    }

    public function test_continue_on_step_two_restores_the_details_already_submitted_and_the_choices_in_progress(): void
    {
        $this->startVisit();
        $step2 = $this->form($this->submitDetails());
        $this->assertSame('Choose your visit', $step2['title']);

        $this->chat('hello', ['formDraft' => ['service' => 'pediatrics', 'doctor' => '1', 'slot' => '7']]);
        $this->assertSame('visit_form', $this->hold()['step']);

        $form = $this->form($this->chat(ScheduleVisitHold::CONTINUE));

        $this->assertSame('Choose your visit', $form['title']);
        $this->assertSame(['service' => 'pediatrics', 'doctor' => '1', 'slot' => '7'], $this->fieldValues($form));
        $this->assertStringContainsString('Maria Santos', (string) $form['notice'] . ' Maria Santos', 'earlier answers are still known');

        // Going Back still shows the step-1 details the patient had submitted.
        $back = $this->form($this->chat('back_to_details'));
        $this->assertSame('Maria Santos', $this->fieldValues($back)['full_name']);
    }

    public function test_start_over_goes_back_to_a_blank_first_step(): void
    {
        $this->startVisit();
        $this->submitDetails();
        $this->chat('thanks', ['formDraft' => ['service' => 'pediatrics']]);

        $reply = $this->chat(ScheduleVisitHold::RESTART);
        $form = $this->form($reply);

        $this->assertSame('Your details', $form['title']);
        $this->assertSame('', $this->fieldValues($form)['full_name']);
        $this->assertFalse($this->hold()['active']);
    }

    public function test_cancel_cancels_the_visit_and_brings_back_the_menu(): void
    {
        $this->startVisit();
        $this->chat('thanks');

        $reply = $this->chat(ScheduleVisitHold::CANCEL);

        $this->assertNull($this->form($reply));
        $this->assertStringContainsString('cancelled', implode(' ', $this->texts($reply)));
        $this->assertContains('schedule visit', $this->buttonValues($reply));
        $this->assertFalse($this->hold()['active']);

        // And the visit really is over: Continue now has nothing to continue.
        $stale = $this->chat(ScheduleVisitHold::CONTINUE);
        $this->assertNull($this->form($stale));
        $this->assertStringContainsString('no longer active', implode(' ', $this->texts($stale)));
    }

    // 2) needs staff -> form stays on hold until they resolve it

    public function test_inquiry_for_staff_puts_the_form_on_hold_until_staff_resolves_it(): void
    {
        $this->startVisit();

        $reply = $this->chat('Do you accept PhilHealth for pediatric checkups?', ['formDraft' => ['full_name' => 'Maria San', 'phone' => '0917']]);

        $this->assertNull($this->form($reply), 'the form must not come back while staff has the question');
        $marker = $this->marker($reply);
        $this->assertSame('staff', $marker['mode']);
        $this->assertSame([], $this->buttonValues($reply), 'no Continue / Start Over / Cancel yet');
        $this->assertContains(\App\Conversations\InquiryConversation::FORWARDED_TEXT, $this->texts($reply));

        $inquiry = Inquiry::firstOrFail();
        $this->assertSame($this->cid, $inquiry->conversation_id);
        $this->assertSame($inquiry->inquiry_id, $marker['inquiryId']);

        $hold = $this->hold();
        $this->assertTrue($hold['active']);
        $this->assertSame('staff', $hold['mode']);
        $this->assertFalse($hold['ready'], 'still waiting for staff');

        // Staff replies but has not resolved it yet: still on hold.
        InquiryReply::create(['inquiry_id' => $inquiry->inquiry_id, 'sender' => 'Staff', 'message' => 'Checking that for you.']);
        $this->assertFalse($this->hold()['ready']);

        // Resolved -> the three buttons are due, and Continue restores the typed values.
        $this->resolveInquiry($inquiry->inquiry_id);
        $hold = $this->hold();
        $this->assertTrue($hold['ready']);
        $this->assertSame('resolved', $hold['readyReason']);

        $form = $this->form($this->chat(ScheduleVisitHold::CONTINUE));
        $this->assertSame('Your details', $form['title']);
        $this->assertSame('Maria San', $this->fieldValues($form)['full_name']);
        $this->assertFalse($this->hold()['active']);
    }

    public function test_a_statement_the_bot_cannot_answer_also_goes_to_staff(): void
    {
        $this->startVisit();

        $reply = $this->chat('my son has a fever since yesterday');

        $this->assertNull($this->form($reply));
        $this->assertSame('staff', $this->marker($reply)['mode']);
        $this->assertSame(1, Inquiry::count());
    }

    public function test_start_over_and_cancel_work_after_staff_resolves(): void
    {
        $this->startVisit();
        $this->chat('Do you accept PhilHealth for pediatric checkups?');
        $this->resolveInquiry(Inquiry::first()->inquiry_id);

        $form = $this->form($this->chat(ScheduleVisitHold::RESTART));
        $this->assertSame('Your details', $form['title']);

        $this->chat('Do you accept PhilHealth for pediatric checkups?');
        $this->resolveInquiry(Inquiry::orderByDesc('inquiry_id')->first()->inquiry_id);
        $reply = $this->chat(ScheduleVisitHold::CANCEL);
        $this->assertNull($this->form($reply));
        $this->assertFalse($this->hold()['active']);
    }

    public function test_follow_up_messages_while_waiting_stay_in_the_same_inquiry(): void
    {
        $this->startVisit();
        $this->chat('Do you accept PhilHealth for pediatric checkups?');

        $reply = $this->chat('it is for my 3 year old daughter');

        $this->assertSame(1, Inquiry::count(), 'one thread per chat');
        $this->assertSame('staff', $this->marker($reply)['mode']);
        $this->assertNotContains(\App\Conversations\InquiryConversation::FORWARDED_TEXT, $this->texts($reply), 'no repeated acknowledgment');
        $this->assertNull($this->form($reply));
        $this->assertSame(1, InquiryReply::where('message', 'it is for my 3 year old daughter')->count());
        $this->assertTrue($this->hold()['active']);
        $this->assertFalse($this->hold()['ready']);
    }

    public function test_bot_answerable_message_while_waiting_for_staff_does_not_release_the_form(): void
    {
        $this->startVisit();
        $this->chat('Do you accept PhilHealth for pediatric checkups?');

        $reply = $this->chat('thanks');

        $this->assertSame([], $this->buttonValues($reply), 'still waiting for staff: no buttons yet');
        $this->assertFalse($this->hold()['ready']);
    }

    public function test_a_new_question_after_a_bot_answer_hands_the_hold_over_to_staff(): void
    {
        $this->startVisit();
        $this->chat('thanks');                       // bot answered -> 3 buttons
        $this->assertSame('bot', $this->hold()['mode']);

        $reply = $this->chat('Do you accept PhilHealth for pediatric checkups?');

        $this->assertSame('staff', $this->marker($reply)['mode']);
        $hold = $this->hold();
        $this->assertSame('staff', $hold['mode']);
        $this->assertFalse($hold['ready']);
        $this->assertSame(1, Inquiry::count());
    }

    // 3) safeguards so the patient is never stuck

    public function test_continue_anyway_while_staff_has_not_resolved_it_returns_to_the_exact_step(): void
    {
        $this->startVisit();
        $this->chat('Do you accept PhilHealth for pediatric checkups?', ['formDraft' => ['full_name' => 'Maria San']]);
        $inquiry = Inquiry::first();
        InquiryReply::create(['inquiry_id' => $inquiry->inquiry_id, 'sender' => 'Staff', 'message' => 'One moment please.']);

        $form = $this->form($this->chat(ScheduleVisitHold::CONTINUE));

        $this->assertSame('Your details', $form['title']);
        $this->assertSame('Maria San', $this->fieldValues($form)['full_name']);
        $this->assertFalse($this->hold()['active']);
        $this->assertNotSame('Resolved', $inquiry->fresh()->resolved_status, 'the inquiry itself stays open for staff');
    }

    public function test_cancel_while_waiting_for_staff_cancels_the_visit_but_not_the_inquiry(): void
    {
        $this->startVisit();
        $this->chat('Do you accept PhilHealth for pediatric checkups?');

        $reply = $this->chat(ScheduleVisitHold::CANCEL);

        $this->assertNull($this->form($reply));
        $this->assertFalse($this->hold()['active']);
        $this->assertSame('Pending', Inquiry::first()->resolved_status);
    }

    public function test_typing_the_choice_works_like_tapping_the_button(): void
    {
        $this->startVisit();
        $this->chat('thanks', ['formDraft' => ['full_name' => 'Maria San']]);

        $form = $this->form($this->chat('Continue schedule visit'));

        $this->assertSame('Maria San', $this->fieldValues($form)['full_name']);
    }

    public function test_auto_release_after_ten_quiet_minutes_once_per_quiet_period(): void
    {
        Carbon::setTestNow('2026-11-06 10:00:00');
        $this->startVisit();
        $this->chat('Do you accept PhilHealth for pediatric checkups?');
        $inquiry = Inquiry::first();

        Carbon::setTestNow('2026-11-06 10:09:59');
        $this->assertFalse($this->hold()['ready'], 'not yet: 9 min 59 s');

        Carbon::setTestNow('2026-11-06 10:10:00');
        $hold = $this->hold();
        $this->assertTrue($hold['ready']);
        $this->assertSame('idle', $hold['readyReason']);
        $this->assertSame('staff', $hold['mode'], 'the form is still the staff-wait kind of hold');

        // Activity (a staff reply) starts a new quiet period: no buttons until 10 more minutes.
        Carbon::setTestNow('2026-11-06 10:12:00');
        InquiryReply::create(['inquiry_id' => $inquiry->inquiry_id, 'sender' => 'Staff', 'message' => 'Still checking.']);
        $this->assertFalse($this->hold()['ready']);

        Carbon::setTestNow('2026-11-06 10:21:59');
        $this->assertFalse($this->hold()['ready']);
        Carbon::setTestNow('2026-11-06 10:22:00');
        $this->assertTrue($this->hold()['ready']);

        // A message from the patient is activity too.
        Carbon::setTestNow('2026-11-06 10:25:00');
        $this->chat('hello? any update?');
        $this->assertFalse($this->hold()['ready']);

        Carbon::setTestNow();
    }

    public function test_auto_release_does_not_apply_once_the_inquiry_is_resolved_it_is_a_normal_release(): void
    {
        Carbon::setTestNow('2026-11-06 10:00:00');
        $this->startVisit();
        $this->chat('Do you accept PhilHealth for pediatric checkups?');
        $this->resolveInquiry(Inquiry::first()->inquiry_id);

        $this->assertSame('resolved', $this->hold()['readyReason']);
        Carbon::setTestNow();
    }

    // attachments, other flows, stale state

    public function test_an_attachment_sent_while_the_form_is_open_puts_the_form_on_hold(): void
    {
        Storage::fake('public');
        $this->startVisit();

        $this->post('/chatbot/attachment', [
            'attachment' => UploadedFile::fake()->create('rx.pdf', 10, 'application/pdf'),
            'userId'     => $this->cid,
            'caption'    => 'my lab result',
        ], ['Accept' => 'application/json'])->assertOk();

        // The widget then sends this hidden message (with what is typed in the form).
        $reply = $this->chat(ScheduleVisitHold::FOR_INQUIRY_TOKEN, ['formDraft' => ['full_name' => 'Maria San']]);

        $this->assertNull($this->form($reply));
        $this->assertSame('staff', $this->marker($reply)['mode']);
        $this->assertSame(1, Inquiry::count());

        $hold = $this->hold();
        $this->assertSame('staff', $hold['mode']);
        $this->assertFalse($hold['ready']);

        $this->resolveInquiry(Inquiry::first()->inquiry_id);
        $form = $this->form($this->chat(ScheduleVisitHold::CONTINUE));
        $this->assertSame('Maria San', $this->fieldValues($form)['full_name']);
    }

    public function test_menu_and_starting_another_flow_drop_the_held_visit(): void
    {
        $this->startVisit();
        $this->chat('thanks');
        $reply = $this->chat('menu');
        $this->assertNull($this->form($reply));
        $this->assertFalse($this->hold()['active']);

        $this->startVisit();
        $this->chat('thanks');
        $this->chat('schedule visit');   // a fresh visit replaces the held one
        $this->assertFalse($this->hold()['active']);
    }

    public function test_general_information_while_on_hold_shows_the_buttons_not_the_menu(): void
    {
        $this->startVisit();
        $this->chat('thanks');

        $reply = $this->chat('general information');

        $this->assertSame([ScheduleVisitHold::CONTINUE, ScheduleVisitHold::RESTART, ScheduleVisitHold::CANCEL], $this->buttonValues($reply));
        $this->assertTrue($this->hold()['active']);
    }

    public function test_hold_markers_are_not_written_to_the_chatbot_logs(): void
    {
        $this->startVisit();
        $this->chat('thanks');

        $this->assertSame(0, DB::table('chatbot_logs')->where('bot_message', 'like', '%HOLD::%')->count());
        $this->assertGreaterThan(0, DB::table('chatbot_logs')->count());
    }

    public function test_draft_only_keeps_known_fields_and_limits_their_size(): void
    {
        $draft = ScheduleVisitHold::normalizeDraft('patient_form', [
            'full_name' => str_repeat('a', 1000), 'phone' => ['x'], 'evil' => 'x', 'send_copy' => 'false',
        ]);

        $this->assertSame(['full_name', 'send_copy'], array_keys($draft));
        $this->assertSame(300, mb_strlen($draft['full_name']));
        $this->assertFalse($draft['send_copy']);
        $this->assertNull(ScheduleVisitHold::normalizeDraft('review', ['full_name' => 'x']));
        $this->assertNull(ScheduleVisitHold::normalizeDraft('patient_form', 'not json'));
    }

    public function test_without_a_hold_nothing_changes_for_ordinary_chat(): void
    {
        $reply = $this->chat('Do you accept PhilHealth for pediatric checkups?');
        $this->assertNull($this->marker($reply));
        $this->assertSame(1, Inquiry::count());
        $this->assertFalse($this->hold()['active']);
    }

    // Admin/Staff must not see who the patient is until the visit is confirmed

    public function test_an_inquiry_sent_before_the_visit_is_confirmed_does_not_reveal_the_patient(): void
    {
        $this->startVisit();
        $this->submitDetails();   // "Maria Santos" is typed into step 1 and kept in the hold

        $this->chat('Do you accept PhilHealth for pediatric checkups?');

        $inquiry = Inquiry::firstOrFail();
        $this->assertNull($inquiry->patient_id, 'no patient id while the visit is unfinished');
        $this->assertNull($inquiry->guest_name, 'no name typed in the form either');
        $this->assertSame(0, DB::table('chatbot_logs')->whereNotNull('user_id')->count());
        $this->assertStringNotContainsString('Maria', json_encode(DB::table('inquiries')->get()));
        $this->assertStringNotContainsString('Maria', json_encode(DB::table('chatbot_logs')->get()));
        $this->assertSame('', $inquiry->toApiArray()['patientId']);
    }

    // Admin/Staff can read the patient's "thank you" and the bot's answer

    public function test_thank_you_and_the_bots_answer_are_saved_in_the_open_inquiry_for_staff(): void
    {
        $this->startVisit();
        $this->chat('Do you accept PhilHealth for pediatric checkups?');
        $inquiry = Inquiry::firstOrFail();

        $this->chat('thanks');

        $patientReply = InquiryReply::where('inquiry_id', $inquiry->inquiry_id)->where('message', 'thanks')->first();
        $botReply = InquiryReply::where('inquiry_id', $inquiry->inquiry_id)->where('sender', 'Chatbot')->first();

        $this->assertNotNull($patientReply, "the patient's thank you is in the thread");
        $this->assertSame('Patient', $patientReply->sender);
        $this->assertNotNull($botReply, "the bot's answer is in the thread");
        $this->assertTrue($botReply->is_staff, 'shown on the answering side in the Admin/Staff view');
        $this->assertGreaterThan($patientReply->reply_id, $botReply->reply_id);

        // ...but the patient's own widget is not sent the bot's answer a second time.
        $updates = $this->poll()['updates'];
        $this->assertSame([], array_values(array_filter($updates, fn ($u) => ($u['sender'] ?? '') === 'Chatbot')));
    }

    public function test_a_thank_you_with_no_open_inquiry_is_not_written_to_any_thread(): void
    {
        $this->chat('thanks');

        $this->assertSame(0, Inquiry::count());
        $this->assertSame(0, InquiryReply::count());
    }

    public function test_bot_answers_while_the_form_is_open_are_also_saved_in_an_open_inquiry(): void
    {
        $this->startVisit();
        $this->chat('Do you accept PhilHealth for pediatric checkups?');
        $this->chat(ScheduleVisitHold::CONTINUE);          // back on the form, inquiry still open
        $inquiry = Inquiry::firstOrFail();

        $this->chat('thanks');                              // typed while the form is on screen

        $this->assertSame(1, InquiryReply::where('inquiry_id', $inquiry->inquiry_id)->where('sender', 'Chatbot')->count());
        $this->assertSame(1, InquiryReply::where('inquiry_id', $inquiry->inquiry_id)->where('message', 'thanks')->count());
    }

    // The patient is told how to bring back the 4 main buttons

    public function test_the_patient_is_told_to_type_menu_for_the_main_options(): void
    {
        // Question forwarded to staff.
        $reply = $this->chat('Do you accept PhilHealth for pediatric checkups?');
        $this->assertStringContainsString('"Menu"', implode(' ', $this->texts($reply)));

        // Bot answer that comes without any buttons.
        $reply = $this->chat('thanks');
        $this->assertContains(\App\Conversations\InquiryConversation::MENU_HINT, $this->texts($reply));
    }

    public function test_the_menu_hint_is_not_added_while_a_schedule_visit_is_on_hold(): void
    {
        $this->startVisit();
        $this->chat('Do you accept PhilHealth for pediatric checkups?');

        $reply = $this->chat('thanks');

        $this->assertNotContains(\App\Conversations\InquiryConversation::MENU_HINT, $this->texts($reply));
    }
}