<?php

final class AdminAuthMiddleware
{
    public static function requireAuth(): array
    {
        $admin = $_SESSION['admin_user'] ?? null;
        if (!is_array($admin) || empty($admin['id'])) {
            self::redirectToLogin();
        }

        try {
            $repository = new AdminUserRepository();
            $freshAdmin = $repository->findById((int) $admin['id']);
            if (!$freshAdmin || !(int) $freshAdmin['is_active']) {
                $_SESSION = [];
                session_destroy();
                self::redirectToLogin();
            }

            $_SESSION['admin_user'] = [
                'id' => (int) $freshAdmin['id'],
                'name' => $freshAdmin['name'],
                'email' => $freshAdmin['email'],
            ];

            return $_SESSION['admin_user'];
        } catch (PDOException $exception) {
            error_log('Admin guard failed: ' . $exception->getMessage());
            http_response_code(503);
            echo 'Admin area is unavailable. Please try again later.';
            exit;
        }
    }

    public static function guestOnly(): void
    {
        if (!empty($_SESSION['admin_user']['id'])) {
            header('Location: /admin/dashboard');
            exit;
        }
    }

    private static function redirectToLogin(): void
    {
        header('Location: /admin/login');
        exit;
    }
}
