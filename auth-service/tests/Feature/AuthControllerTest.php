<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'customer'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role'],
                'token'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role'],
                'token'
            ]);
    }

    public function test_login_fails_with_wrong_credentials()
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(422);
    }

    public function test_refresh_requires_bearer_token()
    {
        $response = $this->postJson('/api/v1/refresh');

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Token required');
    }

    public function test_auth_responses_include_request_id_header()
    {
        $response = $this->postJson('/api/v1/refresh');

        $response->assertHeader('X-Request-Id');
        $this->assertNotEmpty($response->headers->get('X-Request-Id'));
    }

    public function test_login_endpoint_is_rate_limited()
    {
        User::factory()->create([
            'email' => 'limit@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'limit@example.com',
                'password' => 'wrong_password',
            ]);
        }

        $response = $this->postJson('/api/v1/login', [
            'email' => 'limit@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(429);
    }
}
