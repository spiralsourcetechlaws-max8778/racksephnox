<?php

return [
    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),

    'consumer_key'    => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'shortcode'       => env('MPESA_SHORTCODE', '174379'),
    'passkey'         => env('MPESA_PASSKEY'),

    'b2c_shortcode'       => env('MPESA_B2C_SHORTCODE', env('MPESA_SHORTCODE')),
    'initiator_name'      => env('MPESA_INITIATOR_NAME'),
    'initiator_password'  => env('MPESA_INITIATOR_PASSWORD'),

    'urls' => [
        'sandbox' => [
            'base'      => 'https://sandbox.safaricom.co.ke',
            'oauth'     => 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
            'stk_push'  => 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
            'stk_query' => 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query',
            'b2c'       => 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest',
        ],
        'production' => [
            'base'      => 'https://api.safaricom.co.ke',
            'oauth'     => 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
            'stk_push'  => 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
            'stk_query' => 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query',
            'b2c'       => 'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest',
        ],
    ],

    'callback_urls' => [
        'stk'        => env('MPESA_STK_CALLBACK_URL', url('/api/v1/mpesa/callback')),
        'b2c_result' => env('MPESA_B2C_RESULT_URL', url('/api/v1/mpesa/b2c-result')),
        'b2c_timeout' => env('MPESA_B2C_TIMEOUT_URL', url('/api/v1/mpesa/b2c-timeout')),
    ],

    'limits' => [
        'min_deposit'    => (int) env('MPESA_MIN_DEPOSIT', 10),
        'max_deposit'    => (int) env('MPESA_MAX_DEPOSIT', 150000),
        'min_withdrawal' => (int) env('MPESA_MIN_WITHDRAWAL', 100),
        'max_withdrawal' => (int) env('MPESA_MAX_WITHDRAWAL', 150000),
    ],

    'fees' => [
        'withdrawal_percent' => (float) env('MPESA_WITHDRAWAL_FEE_PERCENT', 1.0),
        'withdrawal_min'     => (int) env('MPESA_WITHDRAWAL_FEE_MIN', 10),
        'withdrawal_max'     => (int) env('MPESA_WITHDRAWAL_FEE_MAX', 100),
    ],

    'pochi_number' => env('MPESA_POCHI_NUMBER', '0141423343'),
    'pochi_name'   => env('MPESA_POCHI_NAME', 'Racksephnox'),

    'paybill_number'  => env('MPESA_PAYBILL_NUMBER', '200999'),
    'paybill_account' => env('MPESA_PAYBILL_ACCOUNT', '0333060036213'),

    'instructions' => env(
        'MPESA_DEPOSIT_INSTRUCTIONS',
        'Pochi la Biashara for instant deposits. Paybill for larger transfers.'
    ),

    'callback_bypass_ip_check' => (bool) env('MPESA_CALLBACK_BYPASS_IP_CHECK', false),
];
