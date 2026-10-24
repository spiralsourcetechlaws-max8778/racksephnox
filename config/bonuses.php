<?php

return [
    'signup' => (float) env('BONUS_SIGNUP', 40.00),
    'referral' => (float) env('BONUS_REFERRAL', 40.00),
    'rx_machine_activation' => (float) env('BONUS_RX_MACHINE_ACTIVATION', 53.00),

    // Currency label shown in transaction descriptions
    'currency' => env('BONUS_CURRENCY', 'KES'),
];
