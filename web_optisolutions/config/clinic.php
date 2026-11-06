<?php

return [
    /*
    | Short reminders shown in the visit confirmation email (one per line).
    | Leave empty to hide the "Reminders" section. Only add what the clinic
    | actually requires, e.g. 'Please arrive 15 minutes before your schedule.'
    */
    'visit_reminders' => [],

    // Used only when the clinic_info table has no value for the field.
    'fallback' => [
        'address'         => '',
        'contact_no'      => '',
        'operating_hours' => '',
    ],
];
