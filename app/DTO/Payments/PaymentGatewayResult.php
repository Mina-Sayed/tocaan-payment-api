<?php

namespace App\DTO\Payments;

class PaymentGatewayResult
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly string $status,
        public readonly array $meta = [],
    ) {
    }
}
