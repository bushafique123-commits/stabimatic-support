<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Env.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

Env::load(__DIR__ . '/../.env');

$checks = [];

function record_check(array &$checks, string $name, bool $ok, string $detail = ''): void
{
    $checks[] = [
        'name' => $name,
        'ok' => $ok,
        'detail' => $detail,
    ];
}

try {
    $pdo = db();
    record_check($checks, 'Database connection', true, 'Connected to ' . Env::get('DB_NAME', ''));

    foreach (['users', 'auth_tokens', 'rate_limits'] as $table) {
        $statement = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        record_check($checks, "Table {$table}", (bool) $statement->fetchColumn(), $table);
    }

    $requiredUserColumns = ['password_hash', 'is_active', 'is_verified', 'mobile_verified_at', 'email_verified_at'];
    $statement = $pdo->query('SHOW COLUMNS FROM users');
    $userColumns = array_map(static function (array $row): string {
        return $row['Field'];
    }, $statement->fetchAll());

    foreach ($requiredUserColumns as $column) {
        record_check($checks, "users.{$column}", in_array($column, $userColumns, true), $column);
    }
} catch (Throwable $exception) {
    record_check($checks, 'Database connection', false, $exception->getMessage());
}

foreach ($checks as $check) {
    echo ($check['ok'] ? '[OK] ' : '[FAIL] ') . $check['name'];
    if ($check['detail'] !== '') {
        echo ' - ' . $check['detail'];
    }
    echo PHP_EOL;
}

exit(count(array_filter($checks, static function (array $check): bool {
    return !$check['ok'];
})) > 0 ? 1 : 0);
