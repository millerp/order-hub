<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OrderHub\Shared\Auth\JwtTokenDecoder;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    public function __construct(
        private readonly JwtTokenDecoder $jwtTokenDecoder
    ) {}

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
            $decoded = $this->jwtTokenDecoder->decode($token, storage_path('keys/oauth-public.key'));

            $user = new User;
            $user->id = $decoded->sub;
            $user->role = strtolower((string) ($decoded->role ?? 'customer'));

            // Set user resolver so $request->user() and Auth::user() works for policies
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            Auth::setUser($user);

        } catch (ExpiredException $e) {
            return response()->json([
                'message' => 'Token expired',
                'errors' => [
                    ['message' => 'Token expired'],
                ],
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 401);
        } catch (SignatureInvalidException $e) {
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
