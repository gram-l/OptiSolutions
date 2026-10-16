<?php

namespace Database\Seeders;

use App\Models\ChatbotCommand;
use App\Models\ChatbotCommandTrigger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClinicChatbotCommandsSeeder extends Seeder
{
    public static function commands(): array
    {
        return [
            ['Visit booking help', ['how to book a visit', 'how do i book an appointment', 'booking instructions', 'paano magpa appointment', 'paano magpa schedule', 'paano magpabook'], 'To request a visit, choose Schedule Visit in the chatbot menu and follow the prompts for your service, doctor, and schedule. Para magpa-schedule, piliin ang Schedule Visit at sundin ang mga tanong ng chatbot.'],
            ['Walk-in inquiries', ['do you accept walk ins', 'can i walk in', 'walk in patient', 'pwede ba walk in', 'puwede bang walang appointment', 'tumatanggap ba ng walk in'], 'Please ask clinic staff to confirm walk-in availability for your preferred doctor and day. Magtanong muna sa staff kung tumatanggap ng walk-in ang doktor sa napili mong araw. You can also request a visit through Schedule Visit.'],
            ['Reschedule a visit', ['how to reschedule my visit', 'change my appointment date', 'reschedule appointment help', 'paano ilipat ang appointment', 'palitan ang schedule ko', 'paano mag reschedule'], 'Please contact clinic staff to request a new visit date. Staff will check your doctor’s schedule and email the updated details after rescheduling. Makipag-ugnayan sa staff para ma-check ang schedule ng doktor at ma-email ang bagong detalye.'],
            ['Cancel a visit', ['how to cancel my appointment', 'cancel my visit', 'appointment cancellation', 'paano mag cancel ng appointment', 'hindi ako makakapunta', 'ipakansela ang appointment'], 'Please inform clinic staff if you cannot attend and provide your visit details so they can assist with cancellation. Ipaalam sa staff kung hindi makakapunta at ibigay ang detalye ng iyong scheduled visit.'],
            ['Prepare for your visit', ['what should i bring', 'prepare for appointment', 'visit requirements', 'ano ang dadalhin sa checkup', 'paano maghanda sa checkup', 'ano kailangan sa appointment'], 'Have your visit details and any relevant previous medical records ready. Ask staff about requirements specific to your doctor or service. Ihanda ang detalye ng visit at kaugnay na dating medical records; itanong sa staff ang ibang kailangan.'],
            ['Doctor and specialty help', ['how to choose a doctor', 'find a specialist', 'which doctor should i see', 'paano pumili ng doktor', 'anong specialist ang kailangan', 'hanap ng espesyalista'], 'Use Find Your Specialist on the website to browse doctors and specialties, or choose a service in Schedule Visit. Kung hindi sigurado sa tamang doktor, humingi ng tulong sa clinic staff; the chatbot cannot diagnose your condition.'],
            ['Consultation fees', ['consultation fee', 'how much is a consultation', 'price of checkup', 'magkano ang checkup', 'magkano ang konsultasyon', 'presyo ng pagpapatingin'], 'Please ask clinic staff for the current fee for your chosen doctor or service before your visit. Itanong sa staff ang kasalukuyang bayad para sa napiling doktor o serbisyo; fees are not confirmed by this chatbot.'],
            ['Payment methods', ['payment methods', 'can i pay by card', 'do you accept gcash', 'paano magbayad', 'anong paraan ng bayad', 'pwede ba gcash'], 'Please confirm accepted payment methods with clinic staff before your visit. I-confirm muna sa staff kung anong paraan ng pagbabayad ang tinatanggap.'],
            ['HMO and insurance', ['do you accept hmo', 'insurance coverage', 'is my health card accepted', 'tumatanggap ba ng hmo', 'pwede ba health card', 'sakop ba ng insurance'], 'Ask clinic staff to verify whether your HMO or insurance covers your doctor and service, and whether authorization is required. Ipa-check sa staff ang coverage ng inyong health card o insurance bago ang visit.'],
            ['Laboratory and diagnostic services', ['laboratory test inquiry', 'do you offer blood tests', 'diagnostic test availability', 'may laboratory ba', 'may blood test ba', 'anong diagnostic tests'], 'Check General Information for listed clinic services and ask staff to confirm your specific test, schedule, fee, and preparation instructions. Ipa-confirm sa staff ang availability at paghahanda para sa kailangan mong test; do not assume fasting is required.'],
            ['Test result inquiries', ['how to get test results', 'are my results ready', 'laboratory results', 'paano kunin ang resulta', 'ready na ba results ko', 'nasaan ang lab results'], 'Please contact clinic staff about result availability and the correct collection process. Makipag-ugnayan sa staff tungkol sa resulta. Avoid posting medical results or sensitive personal details in this chat.'],
            ['Medical record requests', ['request medical records', 'get a copy of my records', 'medical certificate inquiry', 'paano kumuha ng medical records', 'kopya ng medical records', 'paano kumuha ng medical certificate'], 'Please request records or certificates directly from clinic staff. They will confirm identity checks, requirements, availability, and any applicable fee. Sa staff mag-request ng records o certificate upang ma-confirm ang tamang proseso.'],
            ['Prescription and refill inquiries', ['prescription refill', 'renew my prescription', 'medicine refill inquiry', 'paano magpa refill ng gamot', 'renew ng reseta', 'kailangan ko ng bagong reseta'], 'Please ask your doctor or clinic staff about prescription renewal and whether a consultation is needed. Makipag-ugnayan sa doktor tungkol sa reseta; this chatbot cannot prescribe medicine or change your dosage.'],
            ['Accessibility assistance', ['wheelchair assistance', 'accessibility information', 'help for senior patients', 'may wheelchair assistance ba', 'tulong para sa pwd', 'assistance para sa senior'], 'Please contact clinic staff before your visit to discuss accessibility or assistance needs and confirm available arrangements. Ipaalam sa staff kung kailangan ng tulong para sa PWD, senior, o pasyenteng may mobility needs.'],
            ['Review transaction code help', ['where is my review code', 'review transaction code', 'lost my transaction code', 'nasaan ang review code ko', 'wala akong transaction code', 'paano makuha ang review code'], 'After staff marks your visit completed, your review pass is emailed to your recorded address. Check your inbox and spam folder, or ask staff for help. Kailangan ang unused transaction code para sa Submit Review/Rating; one code permits one review.'],
        ];
    }

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::commands() as [$label, $triggers, $reply]) {
                // Keep existing admin-edited replies and activation choices.
                $command = ChatbotCommand::firstOrCreate(['label' => $label], ['reply_text' => $reply, 'is_active' => true]);
                foreach ($triggers as $phrase) {
                    $trigger = ChatbotCommand::normalizeTrigger($phrase);
                    if (in_array($trigger, ChatbotCommand::RESERVED_TRIGGERS, true)) continue;
                    $existing = ChatbotCommandTrigger::where('trigger_value', $trigger)->first();
                    if (!$existing) $command->triggers()->create(['trigger_value' => $trigger]);
                    elseif ($existing->command_id !== $command->command_id) $this->command?->warn("Kept existing trigger owner: {$trigger}");
                }
            }
        });
        $this->command?->info('15 clinic command definitions added/checked; existing commands and trigger owners preserved.');
    }
}
