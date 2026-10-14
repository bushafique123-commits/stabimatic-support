<?php

final class Logger
{
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function activity(string $userType, int $userId, string $action, string $module, ?int $recordId = null, ?string $description = null): void
    {
        try {
            $statement = db()->prepare(
                'INSERT INTO activity_logs (user_type, user_id, action, module, record_id, description, ip_address, user_agent)
                 VALUES (:user_type, :user_id, :action, :module, :record_id, :description, :ip_address, :user_agent)'
            );
            $statement->execute([
                ':user_type' => $userType,
                ':user_id' => $userId,
                ':action' => $action,
                ':module' => $module,
                ':record_id' => $recordId,
                ':description' => $description,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Throwable $exception) {
            self::error('Activity log failed', ['detail' => $exception->getMessage()]);
        }
    }

    private static function write(string $level, string $message, array $context): void
    {
        $directory = dirname(__DIR__) . '/logs';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $line = json_encode([
            'time' => date('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ], JSON_UNESCAPED_SLASHES);

        file_put_contents($directory . '/app-' . date('Y-m-d') . '.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
