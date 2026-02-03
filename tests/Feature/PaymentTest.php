<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_process_payment_for_pending_order(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PENDING,
            'total' => 15.00,
        ]);

        $payload = [
            'method' => 'credit_card',
        ];

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/orders/{$order->id}/payments", $payload)
            ->assertStatus(409);
    }

    public function test_process_payment_for_confirmed_order(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_CONFIRMED,
            'total' => 42.00,
        ]);

        $payload = [
            'method' => 'paypal',
            'simulate_outcome' => Payment::STATUS_FAILED,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/orders/{$order->id}/payments", $payload)
            ->assertCreated();

        $response->assertJsonPath('status', Payment::STATUS_FAILED);
        $response->assertJsonPath('method', 'paypal');
    }

    public function test_cannot_process_payment_for_already_paid_order(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_CONFIRMED,
            'total' => 42.00,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => Payment::STATUS_SUCCESSFUL,
            'amount' => 42.00,
        ]);

        $payload = [
            'method' => 'credit_card',
        ];

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/orders/{$order->id}/payments", $payload)
            ->assertStatus(409);
    }
}
