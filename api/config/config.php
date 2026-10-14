<?php

function config(string $key, $default = null)
{
    static $config = null;

    if ($config === null) {
        $config = [
            'app' => [
                'env' => Env::get('APP_ENV', 'production'),
                'debug' => filter_var(Env::get('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
                'url' => Env::get('APP_URL', ''),
                'api_url' => Env::get('API_URL', ''),
            ],
            'jwt' => [
                'secret' => Env::get('JWT_SECRET', 'CHANGE_THIS_SECRET'),
                'expiry' => (int) Env::get('JWT_EXPIRY', 2592000),
            ],
            'cors' => [
                'allowed_origins' => array_filter(array_map('trim', explode(',', Env::get('CORS_ALLOWED_ORIGINS', '')))),
            ],
            'otp' => [
                'expiry_minutes' => (int) Env::get('OTP_EXPIRY_MINUTES', 10),
                'resend_seconds' => (int) Env::get('OTP_RESEND_SECONDS', 60),
                'max_attempts' => (int) Env::get('OTP_MAX_ATTEMPTS', 5),
                'expose_in_response' => filter_var(Env::get('OTP_EXPOSE_IN_RESPONSE', false), FILTER_VALIDATE_BOOLEAN),
            ],
            'sms' => [
                'provider' => Env::get('SMS_PROVIDER', 'dummy'),
                'sendpk' => [
                    'api_url' => Env::get('SENDPK_API_URL', 'https://sendpk.com/api/sms.php'),
                    'api_key' => Env::get('SENDPK_API_KEY', ''),
                    'sender' => Env::get('SENDPK_SENDER', '8584'),
                    'otp_template_id' => Env::get('SENDPK_OTP_TEMPLATE_ID', ''),
                    'otp_message_template' => Env::get('SENDPK_OTP_MESSAGE_TEMPLATE', '{"otp":"{otp}"}'),
                    'notification_template_id' => Env::get('SENDPK_NOTIFICATION_TEMPLATE_ID', ''),
                    'notification_message_template' => Env::get('SENDPK_NOTIFICATION_MESSAGE_TEMPLATE', '{"title":"{title}","message":"{message}"}'),
                    'timeout_seconds' => (int) Env::get('SENDPK_TIMEOUT_SECONDS', 15),
                ],
            ],
            'push' => [
                'provider' => Env::get('PUSH_PROVIDER', 'log'),
                'fcm' => [
                    'endpoint' => Env::get('FCM_ENDPOINT', 'https://fcm.googleapis.com/fcm/send'),
                    'server_key' => Env::get('FCM_SERVER_KEY', ''),
                    'timeout_seconds' => (int) Env::get('FCM_TIMEOUT_SECONDS', 15),
                ],
            ],
            'notifications' => [
                'delivery_batch_size' => (int) Env::get('NOTIFICATION_DELIVERY_BATCH_SIZE', 50),
            ],
            'upload' => [
                'max_bytes' => (int) Env::get('MAX_UPLOAD_BYTES', 5242880),
                'path' => Env::get('UPLOAD_PATH', 'uploads/installations'),
                'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'smtp' => [
                'host' => Env::get('SMTP_HOST', ''),
                'port' => (int) Env::get('SMTP_PORT', 587),
                'username' => Env::get('SMTP_USERNAME', ''),
                'password' => Env::get('SMTP_PASSWORD', ''),
                'from_email' => Env::get('SMTP_FROM_EMAIL', 'no-reply@stabimatic.local'),
                'from_name' => Env::get('SMTP_FROM_NAME', 'STABIMATIC'),
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
