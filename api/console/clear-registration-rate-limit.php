<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Env.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../helpers/InputNormalizer.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

Env::load(__DIR__ . '/../.env');

$mobile = InputNormalizer::mobile($argv[1] ?? '');
if ($mobile === '') {
    fwrite(STDERR, "Usage: php api/console/clear-registration-rate-limit.php 03XXXXXXXXX\n");
    exit(1);
}

$statement = db()->prepare(
    'DELETE FROM rate_limits WHERE action = :action AND rate_key = :rate_key'
);
$statement->execute([
    ':action' => 'registration',
    ':rate_key' => $mobile,
]);

echo "Cleared registration rate limit for {$mobile}. Rows removed: {$statement->rowCount()}\n";
