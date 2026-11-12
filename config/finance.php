<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | The default currency is Rupiah. Additional currencies may be enabled by
    | adding them below; every currency declares the number of fraction
    | digits it uses so amounts are rendered correctly per locale.
    |
    */

    'default_currency' => env('APP_DEFAULT_CURRENCY', 'IDR'),

    'default_timezone' => env('APP_DEFAULT_TIMEZONE', 'Asia/Jakarta'),

    'currencies' => [
        'IDR' => ['symbol' => 'Rp', 'fraction_digits' => 0],
        'USD' => ['symbol' => '$', 'fraction_digits' => 2],
        'EUR' => ['symbol' => '€', 'fraction_digits' => 2],
        'SGD' => ['symbol' => 'S$', 'fraction_digits' => 2],
        'MYR' => ['symbol' => 'RM', 'fraction_digits' => 2],
        'AUD' => ['symbol' => 'A$', 'fraction_digits' => 2],
        'JPY' => ['symbol' => '¥', 'fraction_digits' => 0],
    ],

    /*
    |--------------------------------------------------------------------------
    | Amount Boundaries
    |--------------------------------------------------------------------------
    |
    | Amounts are stored as DECIMAL(18, 2). The maximum below keeps validation
    | in sync with the column definition so overflow can never happen.
    |
    */

    'max_amount' => '9999999999999.99',

    /*
    |--------------------------------------------------------------------------
    | Budget Thresholds
    |--------------------------------------------------------------------------
    |
    | Percentage of a budget that flips its status from "safe" to "warning",
    | and the point at which it is considered exceeded.
    |
    */

    'budget' => [
        'warning_threshold' => 80,
        'exceeded_threshold' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Financial Goals
    |--------------------------------------------------------------------------
    |
    | Progress percentages that trigger a milestone notification. Each
    | milestone is announced at most once per goal.
    |
    */

    'goals' => [
        'milestones' => [25, 50, 75, 100],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bills
    |--------------------------------------------------------------------------
    */

    'bills' => [
        'default_reminder_days_before' => 3,
        'max_reminder_days_before' => 30,
        'overdue_reminder_interval_days' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'per_page' => [
        'transactions' => 20,
        'notifications' => 15,
        'audit_logs' => 20,
        'admin_users' => 20,
        'admin_households' => 20,
        'api_logs' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    'dashboard' => [
        'recent_transactions' => 5,
        'top_categories' => 6,
        'trend_months' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    |
    | Rows are streamed in chunks so exports never load an entire table into
    | memory. The hard limit protects the server from unbounded exports.
    |
    */

    'export' => [
        'chunk_size' => 500,
        'max_rows' => 50000,
    ],

];
