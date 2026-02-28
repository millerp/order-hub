<?php

namespace OrderHub\Shared\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use stdClass;

class JwtTokenDecoder
{
    public function decode(string $token, string $publicKeyPath): stdClass
    {
        if (! file_exists($publicKeyPath)) {
            throw new RuntimeException('Public key not found');
        }

        $publicKey = file_get_contents($publicKeyPath);
        if (! is_string($publicKey) || $publicKey === '') {
            throw new RuntimeException('Public key not found');
        }

        return JWT::decode($token, new Key($publicKey, 'RS256'));
    }
}
