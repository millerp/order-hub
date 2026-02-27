<?php

namespace Tests\Feature;

use App\Models\DummyUser;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_can_create_order_successfully()
    {
        $user = new DummyUser;
        $user->id = 1;
        $user->role = 'customer';
        $this->actingAs($user);

        Http::fake([
            '*/api/v1/products/1' => Http::response(['data' => ['id' => 1, 'price' => 100, 'stock' => 10]], 200),
            '*/api/v1/products/1/reserve' => Http::response(['message' => 'Stock reserved', 'remaining_stock' => 8], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer fake-token')->postJson('/api/v1/orders', [
            'product_id' => 1,
            'quantity' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('orders', [
            'user_id' => 1,
            'product_id' => 1,
            'status' => 'pending',
        ]);
    }
}
