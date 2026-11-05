<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Signup bonus
    |--------------------------------------------------------------------------
    | IMPORTANT: Leave this at 0. User::booted() already credits 60 KES
    | as the welcome bonus on wallet creation. Setting this to a non-zero
    | value would result in a DOUBLE signup bonus (120 KES total).
    |
    | To consolidate: remove the ->credit(60, 'Welcome bonus') from
    | User::booted() and set this to 60. But that requires a data migration
    | for existing users, so keeping it at 0 is the safe choice.
    */
    'signup' => (float) env('BONUS_SIGNUP', 0),

    /*
    |--------------------------------------------------------------------------
    | Referral bonus — flat reward to the referrer on successful signup
    |--------------------------------------------------------------------------
    | Fires once per new user. Idempotent via bonus_credits table.
    | Only applies when the new user submitted a valid referral code.
    */
    'referral' => (float) env('BONUS_REFERRAL', 100),

    /*
    |--------------------------------------------------------------------------
    | Machine activation bonus — awarded when a user invests in a machine
    |--------------------------------------------------------------------------
    */
    'rx_machine_activation' => (float) env('BONUS_MACHINE_ACTIVATION', 0),
];
