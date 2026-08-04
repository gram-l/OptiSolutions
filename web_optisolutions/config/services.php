<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */
    'botman' => [
    'web' => [
        'matchingData' => [
            'driver' => 'web'
        ]
    ]
],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    /* for smsinteg*/
    'iprogsms' => [
    'token' => env('IPROGSMS_API_TOKEN'),
    'base_url' => env('IPROGSMS_BASE_URL', 'https://sms.iprogtech.com/api/v1'),
],

    'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],

    // Kept in case you switch to Google Cloud Translation later.
    // Not used by TranslationController while MyMemory is active.
    'google_translate' => [
        'key' => env('GOOGLE_TRANSLATE_API_KEY'),
    ],

    // Used by the chatbot's translate feature (MyMemory Translation API).
    // Adding an email raises the free daily limit from 5,000 to 50,000
    // characters — no signup or verification required, just a valid format.
    'mymemory' => [
        'email' => env('MYMEMORY_EMAIL'),
    ],

];