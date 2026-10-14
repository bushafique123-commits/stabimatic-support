<?php

final class JwtHelper
{
    public static function issue(array $claims): string
    {
        $now = time();
        $payload = array_merge([
            'iat' => $now,
            'exp' => $now + (int) config('jwt.expiry', 2592000),
            'jti' => bin2hex(random_bytes(16)),
        ], $claims);

        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $segments = [
            self::base64UrlEncode(json_encode($header)),
            self::base64UrlEncode(json_encode($payload)),
        ];
        $segments[] = self::sign(implode('.', $segments));

        return implode('.', $segments);
    }

    public static function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        list($header, $payload, $signature) = $parts;
        if (!hash_equals(self::sign($header . '.' . $payload), $signature)) {
            return null;
        }

        $claims = json_decode(self::base64UrlDecode($payload), true);
        if (!is_array($claims) || ($claims['exp'] ?? 0) < time()) {
            return null;
        }

        return $claims;
    }

    private static function sign(string $payload): string
    {
        return self::base64UrlEncode(hash_hmac('sha256', $payload, config('jwt.secret'), true));
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
