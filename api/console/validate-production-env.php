<?php

declare(strict_types=1);

$envPath = option('env') ?: dirname(__DIR__) . '/.env';
$env = loadEnvFile($envPath);
$results = [];

checkFileExists($results, 'API env file', $envPath);
checkEquals($results, 'APP_ENV is production', value($env, 'APP_ENV'), 'production');
checkFalse($results, 'APP_DEBUG is false', value($env, 'APP_DEBUG'));
checkHttpsUrl($results, 'APP_URL', value($env, 'APP_URL'));
checkHttpsUrl($results, 'API_URL', value($env, 'API_URL'));

checkRequired($results, 'DB_HOST configured', value($env, 'DB_HOST'));
checkRequired($results, 'DB_NAME configured', value($env, 'DB_NAME'));
checkRequired($results, 'DB_USER configured', value($env, 'DB_USER'));
checkSecret($results, 'DB_PASSWORD configured', value($env, 'DB_PASSWORD'), 1);

checkSecret($results, 'JWT_SECRET configured', value($env, 'JWT_SECRET'), 32, ['CHANGE_THIS_SECRET']);
checkPositiveInt($results, 'JWT_EXPIRY positive', value($env, 'JWT_EXPIRY'));

checkCors($results, value($env, 'CORS_ALLOWED_ORIGINS'));
checkPositiveInt($results, 'OTP_EXPIRY_MINUTES positive', value($env, 'OTP_EXPIRY_MINUTES'));
checkPositiveInt($results, 'OTP_RESEND_SECONDS positive', value($env, 'OTP_RESEND_SECONDS'));
checkPositiveInt($results, 'OTP_MAX_ATTEMPTS positive', value($env, 'OTP_MAX_ATTEMPTS'));
checkFalse($results, 'OTP_EXPOSE_IN_RESPONSE is false', value($env, 'OTP_EXPOSE_IN_RESPONSE'));

checkEquals($results, 'SMS_PROVIDER is sendpk', value($env, 'SMS_PROVIDER'), 'sendpk');
checkHttpsUrl($results, 'SENDPK_API_URL', value($env, 'SENDPK_API_URL'));
checkSecret($results, 'SENDPK_API_KEY configured', value($env, 'SENDPK_API_KEY'), 12);
checkRequired($results, 'SENDPK_SENDER configured', value($env, 'SENDPK_SENDER'));
checkRequired($results, 'SENDPK_OTP_TEMPLATE_ID configured', value($env, 'SENDPK_OTP_TEMPLATE_ID'));
checkRequired($results, 'SENDPK_NOTIFICATION_TEMPLATE_ID configured', value($env, 'SENDPK_NOTIFICATION_TEMPLATE_ID'));
checkPositiveInt($results, 'SENDPK_TIMEOUT_SECONDS positive', value($env, 'SENDPK_TIMEOUT_SECONDS'));

checkEquals($results, 'PUSH_PROVIDER is fcm', value($env, 'PUSH_PROVIDER'), 'fcm');
checkHttpsUrl($results, 'FCM_ENDPOINT', value($env, 'FCM_ENDPOINT'));
checkSecret($results, 'FCM_SERVER_KEY configured', value($env, 'FCM_SERVER_KEY'), 12);
checkPositiveInt($results, 'FCM_TIMEOUT_SECONDS positive', value($env, 'FCM_TIMEOUT_SECONDS'));
checkPositiveInt($results, 'NOTIFICATION_DELIVERY_BATCH_SIZE positive', value($env, 'NOTIFICATION_DELIVERY_BATCH_SIZE'));

checkRequired($results, 'SMTP_HOST configured', value($env, 'SMTP_HOST'));
checkPositiveInt($results, 'SMTP_PORT positive', value($env, 'SMTP_PORT'));
checkRequired($results, 'SMTP_USERNAME configured', value($env, 'SMTP_USERNAME'));
checkSecret($results, 'SMTP_PASSWORD configured', value($env, 'SMTP_PASSWORD'), 1);
checkEmail($results, 'SMTP_FROM_EMAIL valid', value($env, 'SMTP_FROM_EMAIL'));
checkRequired($results, 'SMTP_FROM_NAME configured', value($env, 'SMTP_FROM_NAME'));

checkPositiveInt($results, 'MAX_UPLOAD_BYTES positive', value($env, 'MAX_UPLOAD_BYTES'));
checkRequired($results, 'UPLOAD_PATH configured', value($env, 'UPLOAD_PATH'));
checkWritablePath($results, 'UPLOAD_PATH writable when present', dirname(__DIR__) . '/' . value($env, 'UPLOAD_PATH', ''));

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

function checkSecret(array &$results, string $name, string $value, int $minLength, array $invalidValues = []): void
{
    if ($value === '' || strlen($value) < $minLength || in_array($value, $invalidValues, true) || strpos($value, 'change-this') !== false) {
        blocked($results, $name, 'Missing, placeholder, or too short.');
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

function checkPositiveInt(array &$results, string $name, string $value): void
{
    ctype_digit($value) && (int)$value > 0
        ? pass($results, $name)
        : blocked($results, $name, 'Must be a positive integer.');
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

function checkCors(array &$results, string $origins): void
{
    if ($origins === '') {
        blocked($results, 'CORS_ALLOWED_ORIGINS configured', 'Missing allowed origins.');
        return;
    }

    foreach (array_map('trim', explode(',', $origins)) as $origin) {
        if ($origin === '*' || strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false || strpos($origin, 'domain.com') !== false) {
            blocked($results, 'CORS_ALLOWED_ORIGINS production-safe', 'Remove wildcard, local, and placeholder origins.');
            return;
        }
    }
    pass($results, 'CORS_ALLOWED_ORIGINS production-safe');
}

function checkEmail(array &$results, string $name, string $value): void
{
    filter_var($value, FILTER_VALIDATE_EMAIL) && strpos($value, '.local') === false
        ? pass($results, $name)
        : blocked($results, $name, 'Must be a valid production email.');
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
