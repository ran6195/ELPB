<?php

namespace App\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler
{
    private static $secret;
    private static $algorithm = 'HS256';

    public static function init()
    {
        self::$secret = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-this-in-production';
    }

    public static function encode($payload)
    {
        self::init();
        $issuedAt = time();
        $expire = $issuedAt + (60 * 60 * 24 * 7); // 7 giorni

        $token = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => $payload
        ];

        return JWT::encode($token, self::$secret, self::$algorithm);
    }

    public static function decode($token)
    {
        self::init();
        try {
            $decoded = JWT::decode($token, new Key(self::$secret, self::$algorithm));
            return $decoded->data;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function verify($token)
    {
        $decoded = self::decode($token);
        return $decoded !== null;
    }
}
