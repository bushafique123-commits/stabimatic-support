<?php

final class Response
{
    public static function success(string $message, $data = [], int $status = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function error(string $message, array $errors = [], int $status = 400, array $meta = []): void
    {
        self::json(array_merge([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $meta), $status);
    }

    public static function paginated(string $message, array $items, int $total, int $page, int $limit): void
    {
        self::success($message, [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'last_page' => (int) ceil($total / max($limit, 1)),
            'data' => $items,
        ]);
    }

    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
        exit;
    }
}
