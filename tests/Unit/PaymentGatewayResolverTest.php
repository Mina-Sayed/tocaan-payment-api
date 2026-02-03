<?php

namespace Tests\Unit;

use App\Payments\Gateways\CreditCardGateway;
use App\Payments\PaymentGatewayResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentGatewayResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_supported_gateway(): void
    {
        $resolver = app(PaymentGatewayResolver::class);

        $gateway = $resolver->resolve('credit_card');

        $this->assertInstanceOf(CreditCardGateway::class, $gateway);
    }

    public function test_throws_for_unsupported_gateway(): void
    {
        $resolver = app(PaymentGatewayResolver::class);

        $this->expectException(InvalidArgumentException::class);

        $resolver->resolve('bank_transfer');
    }
}
