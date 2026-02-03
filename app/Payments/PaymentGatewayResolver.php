<?php

namespace App\Payments;

use App\Contracts\Payments\PaymentGatewayContract;
use InvalidArgumentException;
use RuntimeException;

class PaymentGatewayResolver
{
    public function resolve(string $method): PaymentGatewayContract
    {
        $methods = config('payments.methods', []);

        if (! array_key_exists($method, $methods)) {
            throw new InvalidArgumentException('Unsupported payment method.');
        }

        $gatewayClass = $methods[$method];
        $gateway = app($gatewayClass);

        if (! $gateway instanceof PaymentGatewayContract) {
            throw new RuntimeException('Invalid payment gateway configuration.');
        }

        return $gateway;
    }
}
