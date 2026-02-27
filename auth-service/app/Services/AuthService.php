<?php

namespace App\Services;

use App\Contracts\AuthServiceInterface;
use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function register(array $data): array
    {
        $user = $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'customer',
        ]);

        $token = $this->generateToken($user);

        return ['user' => $user, 'token' => $token];
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $token = $this->generateToken($user);

        return ['user' => $user, 'token' => $token];
    }

    public function refreshToken(string $token): string
    {
        $publicKey = file_get_contents(storage_path('keys/oauth-public.key'));
        $decoded = JWT::decode($token, new \Firebase\JWT\Key($publicKey, 'RS256'));
        $user = $this->userRepository->findById($decoded->sub);
        if (! $user) {
            throw new \RuntimeException('User not found');
        }

        return $this->generateToken($user);
    }

    public function generateToken(User $user): string
    {
        $payload = [
            'iss' => 'orderhub',
            'sub' => $user->id,
            'role' => $user->role,
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        if (! file_exists(storage_path('keys/oauth-private.key'))) {
            throw new \RuntimeException('Private key not found. Please generate keys.');
        }

        $privateKey = file_get_contents(storage_path('keys/oauth-private.key'));

        return JWT::encode($payload, $privateKey, 'RS256');
    }
}
