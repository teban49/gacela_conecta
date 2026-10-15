<?php

namespace App\Core;

use Exception;
use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JwtService
{
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function encode(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + (self::$config['expiration'] ?? 43200);

        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expire
        ]);

        $secret = self::$config['secret'] ?? 'default_secret';
        $algo = self::$config['algorithm'] ?? 'HS256';

        return FirebaseJWT::encode($tokenPayload, $secret, $algo);
    }

    public static function decode(string $token): ?object
    {
        try {
            $secret = self::$config['secret'] ?? 'default_secret';
            $algo = self::$config['algorithm'] ?? 'HS256';
            $key = new Key($secret, $algo);

            return FirebaseJWT::decode($token, $key);
        } catch (Exception $e) {
            return null;
        }
    }

    public static function validate(string $token): bool
    {
        return self::decode($token) !== null;
    }

    public static function generateMagicLinkToken(int $userId): string
    {
        $randomBytes = random_bytes(32);
        $token = bin2hex($randomBytes);
        
        return hash('sha256', $token . $userId . time());
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}