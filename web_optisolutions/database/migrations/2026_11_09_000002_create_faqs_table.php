<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Admin-managed FAQs for the public website (Admin > System Settings > FAQs).
// The questions that used to be hardcoded in the website footer are seeded once.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->increments('faq_id');
                $table->string('question', 255);
                $table->text('answer');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (DB::table('faqs')->count() === 0) {
            $defaults = [
            ['What are your clinic\'s operating hours?', '{operating_hours}'],
            ['Do you accept walk-in patients?', 'Yes, but we recommend scheduling a visit ahead of time through our chatbot so you can lock in a slot with your preferred doctor.'],
            ['How do I schedule a visit?', 'Click "Schedule Visit" here in the footer, or open the chat widget and tap "Schedule Visit" — you\'ll be guided step by step through choosing a service, doctor, and time slot, no phone call needed.'],
            ['What services and specialties do you offer?', 'We offer {services}.'],
            ['Where is PolyClinic Lipa located?', '{address} You can check the map or tap "Get Directions" in our contact section.'],
            ['How can I contact the clinic?', 'You can also message us through the chatbot inside of this website. When it\'s emergency, please call or text us at {contact_no}.'],
            ['How do I leave feedback or file a complaint?', 'Click "Leave Feedback" here in the footer, or type "Submit Review/Rating" or "Submit Complaint" directly in the chatbot.'],
            ['How do I choose the right doctor?', 'Use **Find Your Specialist** on our website to browse our doctors and specialties, or choose a service when you tap Schedule Visit. If you are not sure which doctor to see, our staff will gladly help. Please note that the chatbot cannot diagnose your condition.'],
            ['How do I reschedule my visit?', 'Please contact our clinic staff to request a new visit date. They will check your doctor\'s schedule and email you the updated details once the change is made.'],
            ['How do I cancel my visit?', 'If you can no longer make it, please let our staff know and give them your visit details so they can assist you with the cancellation.'],
            ['What should I bring to my visit?', 'Please have your visit details and any relevant previous medical records ready. For requirements specific to your doctor or service, ask our staff before you come.'],
            ['How much is a consultation?', 'Fees depend on the doctor and service you choose. Please ask our staff for the current fee before your visit. The chatbot does not confirm fees.'],
            ['What payment methods do you accept?', 'Please confirm the accepted payment methods with our staff before your visit.'],
            ['Do you accept HMO or insurance?', 'Please ask our staff to verify whether your HMO or insurance covers your doctor and service, and whether an authorization is required, before your visit.'],
            ['Do you offer laboratory or diagnostic tests?', 'Please ask our staff to confirm whether your specific test is available, along with its schedule, fee and preparation instructions. Do not assume that fasting is required unless you have been told so.'],
            ['How do I get my test results?', 'Please contact our staff about the availability of your results and the correct way to collect them. For your privacy, avoid posting medical results or sensitive personal details in the chat.'],
            ['How do I request my medical records or a medical certificate?', 'Please request these directly from our staff. They will confirm the identity checks, requirements, availability and any applicable fee.'],
            ['Can I ask for a prescription refill?', 'Please ask your doctor or our staff whether a new consultation is needed. The chatbot cannot prescribe medicine or change your dosage.'],
            ['Do you have assistance for seniors, PWD or patients with mobility needs?', 'Yes, we want every patient to feel comfortable. Please contact our staff before your visit to discuss the assistance you need so we can confirm the available arrangements.'],
            ['Where is my review code?', 'After our staff marks your visit as completed, a review code is emailed to the address we have on record. Please check your inbox and spam folder, or ask our staff for help. Each code can be used for one review.'],
            ['How is my personal information protected?', 'We handle your personal and medical information in line with our [Privacy Notice](/privacy-notice).'],
            ];

            foreach ($defaults as $i => [$question, $answer]) {
                DB::table('faqs')->insert([
                    'question'   => $question,
                    'answer'     => $answer,
                    'sort_order' => $i + 1,
                    'is_active'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
