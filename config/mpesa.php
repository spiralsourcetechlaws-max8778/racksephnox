<?php

return [
    'pochi_number' => env('MPESA_POCHI_NUMBER', '0141423343'),
    'pochi_name'   => env('MPESA_POCHI_NAME', 'Racksephnox'),

    'paybill_number'  => env('MPESA_PAYBILL_NUMBER', '200999'),
    'paybill_account' => env('MPESA_PAYBILL_ACCOUNT', '0333060036213'),

    'instructions' => env(
        'MPESA_DEPOSIT_INSTRUCTIONS',
        'Pochi la Biashara for instant deposits. Paybill for larger transfers.'
    ),
];
