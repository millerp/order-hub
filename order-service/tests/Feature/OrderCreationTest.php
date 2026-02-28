<?php

namespace Tests\Feature;

use App\Models\DummyUser;
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
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('orders', [
            'user_id' => 1,
            'product_id' => 1,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('outbox_events', [
            'aggregate_type' => 'order',
            'event_type' => 'order.created',
            'aggregate_id' => '1',
        ]);
    }

    public function test_cannot_create_order_when_product_is_missing()
    {
        $user = new DummyUser;
        $user->id = 2;
        $user->role = 'customer';
        $this->actingAs($user);

        Http::fake([
            '*/api/v1/products/999' => Http::response(['message' => 'Product not found'], 404),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer fake-token')->postJson('/api/v1/orders', [
            'product_id' => 999,
            'quantity' => 1,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Product not found or unavailable.');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('outbox_events', 0);
    }

    public function test_cannot_create_order_when_stock_reservation_fails()
    {
        $user = new DummyUser;
        $user->id = 3;
        $user->role = 'customer';
        $this->actingAs($user);

        Http::fake([
            '*/api/v1/products/1' => Http::response(['data' => ['id' => 1, 'price' => 100, 'stock' => 1]], 200),
            '*/api/v1/products/1/reserve' => Http::response(['message' => 'Insufficient stock'], 400),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer fake-token')->postJson('/api/v1/orders', [
            'product_id' => 1,
            'quantity' => 10,
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Failed to reserve stock: {"message":"Insufficient stock"}');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('outbox_events', 0);
    }

    public function test_orders_index_includes_meta_request_id(): void
    {
        $user = new DummyUser;
        $user->id = 9;
        $user->role = 'customer';
        $this->actingAs($user);

        \App\Models\Order::create([
            'user_id' => 9,
            'product_id' => 1,
            'quantity' => 1,
            'total_amount' => 20.50,
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['request_id', 'trace_id'],
            ]);
    }

    public function test_orders_stream_returns_sse_content_type(): void
    {
        $user = new DummyUser;
        $user->id = 10;
        $user->role = 'customer';
        $this->actingAs($user);

        \App\Models\Order::create([
            'user_id' => 10,
            'product_id' => 2,
            'quantity' => 1,
            'total_amount' => 10.00,
            'status' => 'pending',
        ]);

        $response = $this->get('/api/v1/orders/stream?max_iterations=1');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/event-stream; charset=UTF-8');
    }
}
