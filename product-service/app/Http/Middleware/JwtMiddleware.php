<?php

namespace App\Http\Middleware;

use App\Models\DummyUser;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json([
                'message' => 'Token required',
                'errors' => [
                    ['message' => 'Token required'],
                ],
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 401);
        }

        try {
            $publicKeyPath = storage_path('keys/oauth-public.key');
            if (! file_exists($publicKeyPath)) {
                return response()->json([
                    'message' => 'Internal server error',
                    'errors' => [
                        ['message' => 'Internal server error'],
                    ],
                    'meta' => [
                        'request_id' => $request->attributes->get('request_id'),
                    ],
                ], 500);
            }
            $publicKey = file_get_contents($publicKeyPath);
            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));

            $user = new DummyUser;
            $user->id = $decoded->sub;
            $user->role = strtolower((string) ($decoded->role ?? 'customer'));

            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            Auth::setUser($user);

        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json([
                'message' => 'Token expired',
                'errors' => [
                    ['message' => 'Token expired'],
                ],
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return response()->json([
                'message' => 'Invalid token signature',
                'errors' => [
                    ['message' => 'Invalid token signature'],
                ],
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 401);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Invalid token',
                'errors' => [
                    ['message' => 'Invalid token'],
                ],
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 401);
        }

        return $next($request);
    }
}
