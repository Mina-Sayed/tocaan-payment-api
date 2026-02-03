<?php

namespace App\Payments\Gateways;

use App\Contracts\Payments\PaymentGatewayContract;
use App\DTO\Payments\PaymentGatewayResult;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

class PaypalGateway implements PaymentGatewayContract
{
    public function key(): string
    {
        return 'paypal';
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function charge(Order $order, Payment $payment, array $payload): PaymentGatewayResult
    {
        $status = $this->resolveOutcome($payload);

        return new PaymentGatewayResult($status, [
            'provider' => 'paypal',
            'transaction_id' => Str::uuid()->toString(),
            'amount' => $order->total,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveOutcome(array $payload): string
    {
        $forced = $payload['simulate_outcome'] ?? null;
        $allowForced = (bool) config('payments.simulation.allow_forced_outcome', true);

        if ($allowForced && in_array($forced, [Payment::STATUS_SUCCESSFUL, Payment::STATUS_FAILED], true)) {
            return $forced;
        }

        return Payment::STATUS_SUCCESSFUL;
    }
}
