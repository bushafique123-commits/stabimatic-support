<?php

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name(admin_config('session.name', 'STABIMATIC_ADMIN'));
        $savePath = admin_config('session.path', 'storage/sessions');
        if ($savePath !== '') {
            if (!preg_match('/^[A-Za-z]:[\\\\\\/]/', $savePath) && strpos($savePath, '\\\\') !== 0 && strpos($savePath, '/') !== 0) {
                $savePath = dirname(__DIR__) . '/' . $savePath;
            }
            if (!is_dir($savePath)) {
                mkdir($savePath, 0775, true);
            }
            session_save_path($savePath);
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (bool) admin_config('session.secure', true),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
