<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_calculates_total(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $payload = [
            'customer_name' => 'Customer One',
            'customer_email' => 'customer@example.com',
            'items' => [
                ['product_name' => 'Widget', 'quantity' => 2, 'unit_price' => 10.00],
                ['product_name' => 'Addon', 'quantity' => 1, 'unit_price' => 5.50],
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/orders', $payload)
            ->assertCreated();

        $response->assertJsonPath('total', '25.50');
        $response->assertJsonCount(2, 'items');
    }

    public function test_update_order_recalculates_total(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $order = Order::factory()->create(['user_id' => $user->id]);

        $payload = [
            'items' => [
                ['product_name' => 'Widget', 'quantity' => 3, 'unit_price' => 10.00],
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/orders/{$order->id}", $payload)
            ->assertOk();

        $response->assertJsonPath('total', '30.00');
        $response->assertJsonCount(1, 'items');
    }

    public function test_user_cannot_delete_order_with_payments(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_CONFIRMED,
            'total' => 20.00,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 20.00,
        ]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/orders/{$order->id}")
            ->assertStatus(409);
    }

    public function test_user_cannot_modify_order_items_when_payments_exist(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_CONFIRMED,
            'total' => 20.00,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 20.00,
        ]);

        $payload = [
            'items' => [
                ['product_name' => 'Widget', 'quantity' => 2, 'unit_price' => 999.00],
            ],
        ];

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/orders/{$order->id}", $payload)
            ->assertStatus(409);
    }

    public function test_user_cannot_access_another_users_order(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = auth('api')->login($otherUser);

        $order = Order::factory()->create(['user_id' => $owner->id]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertStatus(404);
    }
}
