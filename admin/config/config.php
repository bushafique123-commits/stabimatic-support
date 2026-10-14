<?php

function admin_config(string $key, $default = null)
{
    static $config = null;

    if ($config === null) {
        $config = [
            'app' => [
                'env' => Env::get('APP_ENV', 'production'),
                'debug' => filter_var(Env::get('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
                'url' => Env::get('APP_URL', ''),
                'admin_url' => Env::get('ADMIN_URL', ''),
            ],
            'session' => [
                'name' => Env::get('SESSION_NAME', 'STABIMATIC_ADMIN'),
                'secure' => filter_var(Env::get('SESSION_SECURE', true), FILTER_VALIDATE_BOOLEAN),
                'path' => Env::get('SESSION_PATH', 'storage/sessions'),
            ],
        ];
    }

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}
