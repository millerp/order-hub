<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token required'], 401);
        }

        try {
            $publicKeyPath = storage_path('keys/oauth-public.key');
            if (!file_exists($publicKeyPath)) {
                return response()->json(['success' => false, 'message' => 'Internal server error'], 500);
            }
            $publicKey = file_get_contents($publicKeyPath);
            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));
            
            $user = new User();
            $user->id = $decoded->sub;
            $user->role = $decoded->role ?? 'customer';
            
            // Set user resolver so $request->user() and Auth::user() works for policies
            $request->setUserResolver(function() use ($user) {
                return $user;
            });
            
        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json(['success' => false, 'message' => 'Token expired'], 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid token signature'], 401);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}
