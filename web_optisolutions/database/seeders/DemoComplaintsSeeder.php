<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoComplaintsSeeder extends Seeder
{
    public function run(): void
    {
        $complaints = [
            'Matagal akong naghintay dahil late dumating ang doctor. Nandoon na ako bago pa yung appointment ko.',
            'Ang tagal ng pila sa cashier kahit tapos na ang checkup. Halos 45 minutes bago ako nakabayad.',
            'My appointment started much later than the scheduled time. I had to ask someone else to pick up my child from school.',
            'Masungit ang staff sa reception nang magtanong ako kung saan ipapasa ang form. Maayos naman ang tanong ko.',
            'May favoritism sa pila. Nauna pang tawagin ang kakilala ng staff kahit mas maaga kaming dumating.',
            'Sobrang mahal ng consultation fee para sa maikling follow-up. Sana nakalagay ang presyo bago mag-book.',
            'Hindi nabigay ang medical certificate kahit bumalik ako sa araw na sinabi sa akin. Kailangan ko sana ito para sa trabaho.',
            'Minadali ang consultation. Hindi ko natapos ipaliwanag kung kailan nagsimula ang nararamdaman ko.',
            'Walang abiso na walang doctor. Nalaman ko lang pagdating sa reception matapos akong bumiyahe mula sa kabilang bayan.',
            'Fully booked at walang available na schedule para sa follow-up. Tatlong beses akong nag-check ngayong linggo.',
            'My appointment was cancelled on the morning of my visit. I had already arranged time off work.',
            'Hindi ako pinansin at walang tumulong nang magtanong ako kung saan pipila ang nanay ko.',
            'I was charged twice for the same visit. Both payments show up in my banking app.',
            'Walang official receipt pagkatapos kong magbayad. Kailangan ko ang kopya para sa reimbursement.',
            'Matagal ang resulta ng blood test. Sabi noong Lunes na makukuha ko kinabukasan, pero hanggang Biyernes wala pa.',
            'Iba-iba ang sinabi ng staff tungkol sa requirements. Nagdala ako ng ID gaya ng sabi sa telepono, pero iba ang hiningi pagdating.',
            'Nag-error ang website habang online booking. Pagkatapos kong pindutin ang submit, bumalik ako sa unang form.',
            'Madumi ang banyo at walang sabon sa lababo nang gumamit kami pagkatapos ng checkup.',
            'Narinig ng ibang pasyente ang usapan tungkol sa resulta ko dahil sa may reception ito ipinaliwanag.',
            'Walang ramp para sa wheelchair sa entrance. Kinailangan naming humingi ng tulong para makapasok ang tatay ko.',
        ];

        DB::transaction(function () use ($complaints) {
            foreach ($complaints as $index => $complaint) {
                $text = '[Demo] '.$complaint;
                if (DB::table('complaints')->where('complaint_text', $text)->exists()) {
                    continue;
                }

                DB::table('complaints')->insert([
                    'complaint_text' => $text,
                    'created_at' => now()->subHours($index),
                ]);
            }
        });
    }
}
