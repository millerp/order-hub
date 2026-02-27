<?php

namespace Tests\Unit;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    private $userRepository;
    private $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->authService = new AuthService($this->userRepository);

        // Mock storage_path for keys if needed, but since we are running in the container
        // they might exist. For Unit tests, we should probably mock the file system or
        // ensure keys are available.
    }

    public function test_register_creates_user_and_returns_token()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'role' => 'customer'
        ];

        $user = new User();
        $user->id = 1;
        $user->name = $userData['name'];
        $user->email = $userData['email'];
        $user->role = $userData['role'];

        $this->userRepository->expects($this->once())
            ->method('create')
            ->willReturn($user);

        // We need the keys for generateToken
        // In a true unit test, we'd mock the JWT encoding or the file_get_contents
        // But AuthService uses static methods/facades/helper functions that are hard to mock without extra tools.
        // Let's see if it works with the real files in the container.

        $result = $this->authService->register($userData);

        $this->assertEquals($user, $result['user']);
        $this->assertIsString($result['token']);
    }

    public function test_login_with_valid_credentials()
    {
        $email = 'john@example.com';
        $password = 'password';

        $user = new User();
        $user->id = 1;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role = 'customer';

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $result = $this->authService->login($email, $password);

        $this->assertEquals($user, $result['user']);
        $this->assertIsString($result['token']);
    }

    public function test_login_with_invalid_credentials_throws_exception()
    {
        $email = 'john@example.com';
        $password = 'wrong_password';

        $user = new User();
        $user->email = $email;
        $user->password = Hash::make('correct_password');

        $this->userRepository->method('findByEmail')
            ->willReturn($user);

        $this->expectException(ValidationException::class);

        $this->authService->login($email, $password);
    }
}
