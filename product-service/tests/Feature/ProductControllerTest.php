<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_can_list_products()
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'price', 'stock']
                ],
                'meta' => ['request_id'],
            ]);
    }

    public function test_can_show_product()
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $product->name)
            ->assertJsonStructure([
                'meta' => ['request_id'],
            ]);
    }

    public function test_can_create_product()
    {
        $user = new \App\Models\DummyUser;
        $user->id = 1;
        $user->role = 'admin';
        $this->actingAs($user);

        $productData = [
            'name' => 'New Product',
            'description' => 'Description',
            'price' => 99.99,
            'stock' => 10
        ];

        $response = $this->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'New Product');

        $this->assertDatabaseHas('products', ['name' => 'New Product']);
    }

    public function test_non_admin_cannot_create_product()
    {
        $user = new \App\Models\DummyUser;
        $user->id = 2;
        $user->role = 'customer';
        $this->actingAs($user);

        $response = $this->postJson('/api/v1/products', [
            'name' => 'Blocked Product',
            'description' => 'Description',
            'price' => 49.99,
            'stock' => 5,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('products', ['name' => 'Blocked Product']);
    }

    public function test_admin_role_is_case_insensitive_for_create_product()
    {
        $user = new \App\Models\DummyUser;
        $user->id = 3;
        $user->role = 'ADMIN';
        $this->actingAs($user);

        $response = $this->postJson('/api/v1/products', [
            'name' => 'Case Product',
            'description' => 'Description',
            'price' => 10.99,
            'stock' => 3,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Case Product');
    }

    public function test_can_reserve_stock()
    {
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->postJson("/api/v1/products/{$product->id}/reserve", [
            'quantity' => 4
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('remaining_stock', 6);

        $this->assertEquals(6, $product->fresh()->stock);
    }

    public function test_cannot_reserve_insufficient_stock()
    {
        $product = Product::factory()->create(['stock' => 3]);

        $response = $this->postJson("/api/v1/products/{$product->id}/reserve", [
            'quantity' => 5
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Insufficient stock');
    }
}
