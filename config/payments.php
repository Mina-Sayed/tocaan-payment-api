<?php

use App\Payments\Gateways\CreditCardGateway;
use App\Payments\Gateways\PaypalGateway;

return [
    'methods' => [
        'credit_card' => CreditCardGateway::class,
        'paypal' => PaypalGateway::class,
    ],
    'gateways' => [
        'credit_card' => [
            'api_key' => env('CREDIT_CARD_API_KEY'),
            'secret' => env('CREDIT_CARD_SECRET'),
        ],
        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
        ],
    ],
    'simulation' => [
        'allow_forced_outcome' => env('PAYMENT_SIMULATE_OUTCOME', true),
    ],
];
