<?php

final class AuthController
{
    public function showLogin(): void
    {
        AdminAuthMiddleware::guestOnly();
        View::render('auth/login', [
            'title' => 'Admin Login',
            'error' => $_SESSION['_flash_error'] ?? '',
            'email' => $_SESSION['_old_email'] ?? '',
        ], 'layouts/auth');

        unset($_SESSION['_flash_error'], $_SESSION['_old_email']);
    }

    public function login(): void
    {
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $result = (new AdminAuthService())->attempt($email, $password);

        if (!($result['ok'] ?? false)) {
            $_SESSION['_flash_error'] = $result['message'] ?? 'Invalid email or password.';
            $_SESSION['_old_email'] = $email;
            header('Location: /admin/login');
            exit;
        }

        header('Location: /admin/dashboard');
        exit;
    }

    public function logout(): void
    {
        Csrf::verify($_POST['_csrf_token'] ?? null);
        (new AdminAuthService())->logout();
        header('Location: /admin/login');
        exit;
    }
}
