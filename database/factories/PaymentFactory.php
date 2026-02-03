<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_reference' => Str::uuid()->toString(),
            'status' => Payment::STATUS_SUCCESSFUL,
            'method' => 'credit_card',
            'gateway' => 'credit_card',
            'amount' => fake()->randomFloat(2, 10, 100),
            'meta' => ['note' => 'factory'],
        ];
    }
}
