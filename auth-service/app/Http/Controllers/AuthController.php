<?php

namespace App\Http\Controllers;

use App\Contracts\AuthServiceInterface;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthServiceInterface $authService
    ) {}

    public function register(RegisterUserRequest $request)
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            'meta' => [
                'request_id' => $request->attributes->get('request_id'),
            ],
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 201);
    }

    public function login(LoginUserRequest $request)
    {
        $validated = $request->validated();
        $result = $this->authService->login($validated['email'], $validated['password']);

        return response()->json([
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            'meta' => [
                'request_id' => $request->attributes->get('request_id'),
            ],
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function refresh(Request $request)
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json(['message' => 'Token required'], 401);
        }
        try {
            $newToken = $this->authService->refreshToken($token);

            return response()->json([
                'data' => [
                    'token' => $newToken,
                ],
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
                ],
                'token' => $newToken,
            ]);
        } catch (\Firebase\JWT\ExpiredException|\Firebase\JWT\SignatureInvalidException|\Exception $e) {
            $message = $e instanceof \RuntimeException && $e->getMessage() === 'User not found'
                ? 'User not found'
                : 'Invalid token';
            $status = $e instanceof \RuntimeException && $e->getMessage() === 'User not found' ? 404 : 401;

            return response()->json(['message' => $message], $status);
        }
    }
}
