<?php

return [

    /*
    | Schedule Visit limits used by the chatbot.
    | max_patients_per_hour: patients accepted per hour of a doctor's schedule
    |   window. A 2-hour window accepts 2 x this number (min. 1 per window).
    | max_visits_per_patient_per_day: schedule visits one patient can hold on
    |   the same date (Pending and Confirmed count; Cancelled does not).
    */
    'max_patients_per_hour' => (int) env('CLINIC_MAX_PATIENTS_PER_HOUR', 2),
    'max_visits_per_patient_per_day' => (int) env('CLINIC_MAX_VISITS_PER_PATIENT_PER_DAY', 2),
    /*
    | Short reminders shown in the visit confirmation email (one per line).
    | Leave empty to hide the "Reminders" section. Only add what the clinic
    | actually requires, e.g. 'Please arrive 15 minutes before your schedule.'
    */
    'visit_reminders' => [],

    /*
    | How many minutes before a visit's start time the "please confirm you are
    | coming" reminder email is sent. Needs the scheduler running
    | (php artisan schedule:work) - see docker/supervisord.conf.
    */
    'reminder_minutes' => (int) env('CLINIC_REMINDER_MINUTES', 30),

    // Chatbot email check: reject domains that have no DNS/MX record (made-up domains).
    // Set CLINIC_EMAIL_DNS_CHECK=false in .env for offline/local development.
    'email_dns_check' => env('CLINIC_EMAIL_DNS_CHECK', true),

    // Used only when the clinic_info table has no value for the field.
    'fallback' => [
        'address'         => '',
        'contact_no'      => '',
        'operating_hours' => '',
    ],
];