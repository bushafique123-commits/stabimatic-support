<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$envPath = dirname(__DIR__) . '/api/.env';
if (!is_file($envPath)) {
    fwrite(STDERR, "Missing api/.env. Copy api/.env.example first.\n");
    exit(1);
}

foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
        continue;
    }
    list($key, $value) = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value);
}

$name = $argv[1] ?? 'Super Admin';
$email = $argv[2] ?? 'admin@stabimatic.local';
$password = $argv[3] ?? null;

if (!$password || strlen($password) < 12) {
    fwrite(STDERR, "Usage: php database/create_super_admin.php \"Super Admin\" admin@example.com \"StrongPassword123!\"\n");
    exit(1);
}

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_NAME'] ?? 'stabimatic'
);

$pdo = new PDO($dsn, $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$statement = $pdo->prepare(
    'INSERT INTO admin_users (name, email, password_hash, is_active)
     VALUES (:name, :email, :password_hash, 1)
     ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), is_active = 1'
);

$statement->execute([
    ':name' => $name,
    ':email' => $email,
    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
]);

echo "Super Admin is ready: {$email}\n";
