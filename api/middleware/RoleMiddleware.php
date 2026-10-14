<?php

final class RoleMiddleware
{
    public static function require(array $user, array $roles): void
    {
        if (!in_array($user['role'] ?? '', $roles, true)) {
            Response::error('Forbidden', [], 403);
        }
    }
}
