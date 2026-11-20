<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Upperlink Checkout and Requery Endpoints
    |--------------------------------------------------------------------------
    */
    'checkout_url'      => env('UPPERLINK_CHECKOUT_URL', 'https://paygate.upperlink.ng/paygate/checkout'),
    'requery_url'       => env('UPPERLINK_REQUERY_URL', 'https://paygate.upperlink.ng/paygate/requery'),

    /*
    |--------------------------------------------------------------------------
    | API Credentials & Merchant Identification
    |--------------------------------------------------------------------------
    */
    'username'          => env('UPPERLINK_CHECKOUT_USERNAME'),
    'password'          => env('UPPERLINK_CHECKOUT_PASSWORD'),
    'merchant_id'       => env('UPPERLINK_REF', env('UPPERLINK_MERCHANT_ID')),

    /*
    |--------------------------------------------------------------------------
    | Settlement & Split Payment Configuration
    |--------------------------------------------------------------------------
    */
    'split_mode'        => (bool) env('UPPERLINK_SPLIT_MODE', false),
    'split_code'        => env('UPPERLINK_SPLIT_CODE'),

    /*
    |--------------------------------------------------------------------------
    | Default Redirection URL & Convenience Fee
    |--------------------------------------------------------------------------
    */
    'redirect_url'      => env('UPPERLINK_REDIRECT_URL'),
    'convenience_fee'   => (int) env('UPPERLINK_CONVENIENCE_FEE', 10000), // in kobo (10000 = ₦100)

    /*
    |--------------------------------------------------------------------------
    | Request Timeout & Logging
    |--------------------------------------------------------------------------
    */
    'timeout'           => (int) env('UPPERLINK_TIMEOUT', 30),
    'logging'           => (bool) env('UPPERLINK_LOGGING', true),
];
