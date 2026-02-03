<?php

namespace App\Contracts\Payments;

use App\DTO\Payments\PaymentGatewayResult;
use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayContract
{
    public function key(): string;

    /**
     * @param array<string, mixed> $payload
     */
    public function charge(Order $order, Payment $payment, array $payload): PaymentGatewayResult;
}
