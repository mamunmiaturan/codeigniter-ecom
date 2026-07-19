<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Payment Method Registry
|--------------------------------------------------------------------------
| Provider-agnostic registry: each entry maps a payment CODE to a gateway
| CLASS (defined in libraries/Payment_gateways.php) plus display metadata and
| default settings. Admin-editable overrides (is_active, title, credentials)
| live in the `payment_settings` table and are merged over these defaults by
| Payment_model. Order-first flow: the order is created (payment_status=pending)
| then online methods redirect to the gateway; the callback marks it paid.
*/

$config['payment_methods'] = [

    'cod' => [
        'class'       => 'Cod_gateway',
        'title'       => 'Cash on Delivery',
        'description' => 'Pay with cash when your order is delivered.',
        'is_online'   => false,
        'is_active'   => true,
        'sort'        => 1,
        'config'      => [],
    ],

    'sslcommerz' => [
        'class'       => 'Sslcommerz_gateway',
        'title'       => 'Card / Mobile Banking (SSLCommerz)',
        'description' => 'Pay securely with card, bKash, Nagad, Rocket or net banking.',
        'is_online'   => true,
        'is_active'   => false, // activate in admin after adding sandbox/live credentials
        'sort'        => 2,
        'config'      => [
            'sandbox'      => true,
            'store_id'     => '',
            'store_passwd' => '',
        ],
    ],

    // Simulated online gateway — lets the full redirect→callback→paid flow be
    // exercised without live credentials. Deactivate in production.
    'mock' => [
        'class'       => 'Mock_gateway',
        'title'       => 'Test Online Payment (Mock)',
        'description' => 'Simulated online payment for testing the checkout flow.',
        'is_online'   => true,
        'is_active'   => false, // enable in admin for local QA only — never in production
        'sort'        => 9,
        'config'      => [],
    ],
];
