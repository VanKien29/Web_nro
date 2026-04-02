<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JwtService
{
    private string $secret;
    private string $algo = 'HS256';

    public function __construct()
    {
        $this->secret = env('JWT_SECRET', config('app.key'));
    }

    public function encode(array $payload): string
    {
        $now = time();
        $payload = array_merge([
            'iss' => config('app.url'),
            'iat' => $now,
            'exp' => $now + (60 * 60 * 24 * 7), // 7 days
        ], $payload);

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    public function decode(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algo));
        } catch (ExpiredException) {
            return null;
        } catch (\Exception) {
            return null;
        }
    }
}
