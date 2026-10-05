<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Racksephnox Banking Center — M-Pesa Paybill
    |--------------------------------------------------------------------------
    | These credentials are displayed to users when they make a deposit.
    | Change here to update the paybill everywhere.
    */
    'mpesa' => [
        'paybill'        => env('RACKSEPHNOX_PAYBILL', '200999'),
        'account_number' => env('RACKSEPHNOX_ACCOUNT', '0333060036213'),
        'account_name'   => env('RACKSEPHNOX_ACCOUNT_NAME', 'Racksephnox'),
        'till_name'      => env('RACKSEPHNOX_TILL_NAME', 'RACKSEPHNOX ENTERPRISE'),

        // Daraja API (optional — enables auto-verification)
        'daraja' => [
            'consumer_key'    => env('DARAJA_CONSUMER_KEY'),
            'consumer_secret' => env('DARAJA_CONSUMER_SECRET'),
            'shortcode'       => env('DARAJA_SHORTCODE', '200999'),
            'passkey'         => env('DARAJA_PASSKEY'),
            'environment'     => env('DARAJA_ENV', 'production'), // production | sandbox
            'initiator_name'  => env('DARAJA_INITIATOR_NAME'),
            'security_credential' => env('DARAJA_SECURITY_CREDENTIAL'),
        ],

        // Manual verification fallback
        'manual_verification_window_hours' => 72,
        'min_deposit' => 10,
        'max_deposit' => 500_000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Trading Console
    |--------------------------------------------------------------------------
    */
    'trading' => [
        'refresh_ms'         => env('TRADING_REFRESH_MS', 2000),
        'orderbook_refresh'  => env('TRADING_OB_REFRESH_MS', 1500),
        'max_retries'        => 10,
        'backoff_base_ms'    => 1000,
        'backoff_max_ms'     => 30000,
        'default_indicators' => ['MA'],
    ],
];
