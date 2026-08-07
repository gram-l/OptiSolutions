<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Builds the "INFO_CARD::" payload for the clinic-information card.
 * Shared by BotManController (top-level "general information" command)
 * and AppointmentConversation (mid-flow interrupt), so both places
 * render the exact same card from the exact same query.
 */
class ClinicInfoService
{
    const INFO_CARD_PREFIX = 'INFO_CARD::';

    /**
     * Returns the full "INFO_CARD::{json}" string ready to be passed
     * to $bot->reply() or $this->say(). Returns a plain warning string
     * (no prefix) if clinic info couldn't be loaded.
     */
    public static function infoCardMessage(): string
    {
        $info = DB::table('clinic_info')->first();

        if (!$info) {
            return "⚠️ Sorry, we couldn't load our clinic information right now. Please try again later.";
        }

        $data = (array) $info;
        $sections = [];

        // Section 1: identity + contact
        $identityRows = [];
        $identityMap = [
            'clinic_name'    => 'Clinic Name',
            'address'        => 'Address',
            'email'          => 'Email',
            'contact_no' => 'Contact No',
        ];
        foreach ($identityMap as $key => $label) {
            if (!empty($data[$key])) {
                $identityRows[] = ['label' => $label, 'value' => $data[$key], 'type' => 'text'];
            }
        }
        if (!empty($identityRows)) {
            $sections[] = ['rows' => $identityRows];
        }

        // Section 2: operating hours, one schedule segment per line
        if (!empty($data['operating_hours'])) {
            $lines = array_map('trim', explode('|', $data['operating_hours']));
            $sections[] = [
                'rows' => [
                    ['label' => 'Operating Hours', 'value' => $lines, 'type' => 'multiline'],
                ],
            ];
        }

        // Section 3: Facebook link
        if (!empty($data['facebook_link'])) {
            $sections[] = [
                'rows' => [
                    ['label' => 'Facebook Link', 'value' => $data['facebook_link'], 'type' => 'link'],
                ],
            ];
        }

        $card = [
            'title' => 'PolyClinic Lipa - Clinic Information',
            'sections' => $sections,
        ];

        return self::INFO_CARD_PREFIX . json_encode($card);
    }
}