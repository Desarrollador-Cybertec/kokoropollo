<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function generateToken(): string
    {
        $token = bin2hex(random_bytes(length: 32));
        Session::set(key: '_csrf', value: $token);
        return $token;
    }

    public static function token(): string
    {
        return Session::get('_csrf') ?? self::generateToken();
    }

    public static function validateToken(string $token): bool
    {
        $stored = Session::get(key: '_csrf');
        return $stored !== null && hash_equals($stored, $token);
    }

    public static function field(): string
    {
        $token = self::token();
        return "<input type=\"hidden\" name=\"_csrf\" value=\"{$token}\">";
    }

    public static function meta(): string
    {
        $token = self::token();
        return "<meta name=\"csrf-token\" content=\"{$token}\">";
    }
}
