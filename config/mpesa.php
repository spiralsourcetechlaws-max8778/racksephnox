<?php

return [
    /*
    |--------------------------------------------------------------------------
    | M-Pesa Environment
    |--------------------------------------------------------------------------
    | This is required by MpesaClient.php to determine the base URL.
    | Options: 'sandbox' or 'production'
    */
    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | M-Pesa API URLs
    |--------------------------------------------------------------------------
    */
    'urls' => [
        'sandbox' => 'https://sandbox.safaricom.co.ke',
        'production' => 'https://api.safaricom.co.ke',
    ],

    /*
    |--------------------------------------------------------------------------
    | M-Pesa Daraja API Credentials
    |--------------------------------------------------------------------------
    | You will need to get these from your Safaricom Daraja Developer Portal
    */
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'shortcode' => env('MPESA_SHORTCODE', '174379'),
    'passkey' => env('MPESA_PASSKEY'),
    'callback_url' => env('MPESA_CALLBACK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Custom Manual Deposit Details (Your existing settings)
    |--------------------------------------------------------------------------
    */
    'pochi_number' => env('MPESA_POCHI_NUMBER', '0141423343'),
    'pochi_name'   => env('MPESA_POCHI_NAME', 'Racksephnox'),

    'paybill_number'  => env('MPESA_PAYBILL_NUMBER', '200999'),
    'paybill_account' => env('MPESA_PAYBILL_ACCOUNT', '0333060036213'),

    'instructions' => env(
        'MPESA_DEPOSIT_INSTRUCTIONS',
        'Pochi la Biashara for instant deposits. Paybill for larger transfers.'
    ),
];
