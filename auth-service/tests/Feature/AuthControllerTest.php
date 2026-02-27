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
}
