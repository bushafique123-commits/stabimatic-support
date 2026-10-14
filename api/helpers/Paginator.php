<?php

final class Paginator
{
    public static function page(array $query): int
    {
        return max(1, (int) ($query['page'] ?? 1));
    }

    public static function limit(array $query, int $default = 20, int $maximum = 100): int
    {
        $limit = (int) ($query['limit'] ?? $default);
        return max(1, min($limit, $maximum));
    }

    public static function offset(int $page, int $limit): int
    {
        return ($page - 1) * $limit;
    }
}
