<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$root = dirname(__DIR__);
$envPath = $root . '/api/.env';
if (!is_file($envPath)) {
    fwrite(STDERR, "Missing api/.env. Copy api/.env.example and update credentials before setup.\n");
    fwrite(STDERR, "Example: copy api\\.env.example api\\.env\n");
    exit(1);
}

foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
        continue;
    }
    list($key, $value) = explode('=', $line, 2);
    $_ENV[trim($key)] = trim(trim($value), "\"'");
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$database = $_ENV['DB_NAME'] ?? 'stabimatic';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

$pdo = new PDO("mysql:host={$host};charset=utf8mb4", $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$schema = file_get_contents($root . '/database/schema.sql');
if ($schema === false) {
    fwrite(STDERR, "Unable to read database/schema.sql.\n");
    exit(1);
}

$statements = array_filter(array_map('trim', explode(';', $schema)));
foreach ($statements as $statement) {
    if ($statement !== '') {
        $pdo->exec($statement);
    }
}

echo "Database setup completed for {$database}.\n";
