<?php

declare(strict_types=1);

$envPath = option('env') ?: dirname(__DIR__) . '/.env';
$env = loadEnvFile($envPath);
$results = [];

checkFileExists($results, 'Admin env file', $envPath);
checkEquals($results, 'APP_ENV is production', value($env, 'APP_ENV'), 'production');
checkFalse($results, 'APP_DEBUG is false', value($env, 'APP_DEBUG'));
checkHttpsUrl($results, 'APP_URL', value($env, 'APP_URL'));
checkHttpsUrl($results, 'ADMIN_URL', value($env, 'ADMIN_URL'));

checkRequired($results, 'DB_HOST configured', value($env, 'DB_HOST'));
checkRequired($results, 'DB_NAME configured', value($env, 'DB_NAME'));
checkRequired($results, 'DB_USER configured', value($env, 'DB_USER'));
checkSecret($results, 'DB_PASSWORD configured', value($env, 'DB_PASSWORD'), 1);

checkRequired($results, 'SESSION_NAME configured', value($env, 'SESSION_NAME'));
checkTrue($results, 'SESSION_SECURE is true', value($env, 'SESSION_SECURE'));
checkRequired($results, 'SESSION_PATH configured', value($env, 'SESSION_PATH'));
checkWritablePath($results, 'SESSION_PATH writable when present', dirname(__DIR__) . '/' . value($env, 'SESSION_PATH', ''));

printResults($results);
exit(hasBlocked($results) ? 1 : 0);

function option(string $name): ?string
{
    global $argv;
    foreach ($argv as $argument) {
        if (strpos($argument, '--' . $name . '=') === 0) {
            return substr($argument, strlen($name) + 3);
        }
    }
    return null;
}

function loadEnvFile(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim(trim($value), "\"'");
    }
    return $env;
}

function value(array $env, string $key, string $default = ''): string
{
    return trim((string)($env[$key] ?? $default));
}

function pass(array &$results, string $name, string $detail = ''): void
{
    $results[] = ['status' => 'PASS', 'name' => $name, 'detail' => $detail];
}

function blocked(array &$results, string $name, string $detail): void
{
    $results[] = ['status' => 'BLOCKED', 'name' => $name, 'detail' => $detail];
}

function warn(array &$results, string $name, string $detail): void
{
    $results[] = ['status' => 'WARN', 'name' => $name, 'detail' => $detail];
}

function checkFileExists(array &$results, string $name, string $path): void
{
    is_file($path) ? pass($results, $name, $path) : blocked($results, $name, 'File not found.');
}

function checkRequired(array &$results, string $name, string $value): void
{
    $value !== '' && strpos($value, 'change-this') === false
        ? pass($results, $name)
        : blocked($results, $name, 'Missing or placeholder value.');
}

function checkSecret(array &$results, string $name, string $value, int $minLength): void
{
    if ($value === '' || strlen($value) < $minLength || strpos($value, 'change-this') !== false) {
        blocked($results, $name, 'Missing or placeholder value.');
        return;
    }
    pass($results, $name, 'Configured.');
}

function checkEquals(array &$results, string $name, string $actual, string $expected): void
{
    strtolower($actual) === strtolower($expected)
        ? pass($results, $name)
        : blocked($results, $name, 'Expected ' . $expected . '.');
}

function checkFalse(array &$results, string $name, string $value): void
{
    in_array(strtolower($value), ['false', '0', 'no', 'off'], true)
        ? pass($results, $name)
        : blocked($results, $name, 'Must be false in production.');
}

function checkTrue(array &$results, string $name, string $value): void
{
    in_array(strtolower($value), ['true', '1', 'yes', 'on'], true)
        ? pass($results, $name)
        : blocked($results, $name, 'Must be true in production.');
}

function checkHttpsUrl(array &$results, string $name, string $value): void
{
    $host = parse_url($value, PHP_URL_HOST);
    $scheme = parse_url($value, PHP_URL_SCHEME);
    $blockedHosts = ['domain.com', 'example.com', 'localhost', '127.0.0.1', '10.0.2.2'];

    if ($scheme !== 'https' || !$host || in_array($host, $blockedHosts, true) || strpos($host, '192.168.') === 0) {
        blocked($results, $name, 'Must be a deployed HTTPS URL.');
        return;
    }
    pass($results, $name, $value);
}

function checkWritablePath(array &$results, string $name, string $path): void
{
    if (!is_dir($path)) {
        warn($results, $name, 'Directory not present locally; verify on server.');
        return;
    }
    is_writable($path) ? pass($results, $name, $path) : warn($results, $name, 'Directory is not writable locally; verify on server.');
}

function printResults(array $results): void
{
    foreach ($results as $result) {
        echo $result['status'] . ': ' . $result['name'] . ($result['detail'] ? ' - ' . $result['detail'] : '') . PHP_EOL;
    }
}

function hasBlocked(array $results): bool
{
    foreach ($results as $result) {
        if ($result['status'] === 'BLOCKED') {
            return true;
        }
    }
    return false;
}
