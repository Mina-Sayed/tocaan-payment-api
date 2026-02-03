<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentGatewayResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayResolver $resolver,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function process(Order $order, array $payload): Payment
    {
        return DB::transaction(function () use ($order, $payload): Payment {
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_reference' => Str::uuid()->toString(),
                'status' => Payment::STATUS_PENDING,
                'method' => $payload['method'],
                'gateway' => $payload['method'],
                'amount' => $order->total,
            ]);

            $gateway = $this->resolver->resolve($payload['method']);
            $result = $gateway->charge($order, $payment, $payload);

            $payment->status = $result->status;
            $payment->gateway = $gateway->key();
            $payment->meta = $result->meta;
            $payment->save();

            return $payment->fresh();
        });
    }
}
