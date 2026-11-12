<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | Adding a language is a matter of adding an entry here and creating the
    | matching directory under lang/. The "regional" key is handed to the
    | intl extension for number and date formatting.
    |
    */

    'locales' => [
        'id' => ['label' => 'Bahasa Indonesia', 'regional' => 'id_ID', 'flag' => '🇮🇩'],
        'en' => ['label' => 'English', 'regional' => 'en_US', 'flag' => '🇺🇸'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timezones
    |--------------------------------------------------------------------------
    |
    | A curated shortlist keeps the settings dropdown usable on mobile. The
    | full PHP timezone list is still accepted by validation.
    |
    */

    'common_timezones' => [
        'Asia/Jakarta',
        'Asia/Makassar',
        'Asia/Jayapura',
        'Asia/Singapore',
        'Asia/Kuala_Lumpur',
        'Asia/Tokyo',
        'Asia/Dubai',
        'Europe/London',
        'Europe/Amsterdam',
        'America/New_York',
        'America/Los_Angeles',
        'Australia/Sydney',
        'UTC',
    ],

];
