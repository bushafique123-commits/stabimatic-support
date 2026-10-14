<?php

final class AuthMiddleware
{
    public static function user(Request $request): array
    {
        $header = $request->header('authorization') ?? '';
        if (!preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            Response::error('Unauthenticated', [], 401);
        }

        $claims = JwtHelper::verify(trim($matches[1]));
        if (!$claims || !isset($claims['sub'], $claims['role'])) {
            Response::error('Invalid or expired token', [], 401);
        }

        try {
            if (!(new TokenService())->isActive($claims)) {
                Response::error('Invalid or expired token', [], 401);
            }
        } catch (PDOException $exception) {
            Logger::error('Token validation unavailable', ['detail' => $exception->getMessage()]);
            Response::error('Authentication service is unavailable', ['auth' => 'Please try again later'], 503);
        }

        return $claims;
    }
}
