<?php

final class AdminAuthService
{
    private $admins;

    public function __construct(?AdminUserRepository $admins = null)
    {
        $this->admins = $admins ?: new AdminUserRepository();
    }

    public function attempt(string $email, string $password): array
    {
        $email = strtolower(trim($email));

        if ($email === '' || $password === '') {
            return ['ok' => false, 'message' => 'Email and password are required.'];
        }

        try {
            $admin = $this->admins->findByEmail($email);
            if (!$admin || !password_verify($password, $admin['password_hash'])) {
                return ['ok' => false, 'message' => 'Invalid email or password.'];
            }

            if (!(int) $admin['is_active']) {
                return ['ok' => false, 'message' => 'This admin account is inactive.'];
            }

            session_regenerate_id(true);
            $_SESSION['admin_user'] = [
                'id' => (int) $admin['id'],
                'name' => $admin['name'],
                'email' => $admin['email'],
            ];
            $_SESSION['admin_authenticated_at'] = time();

            $this->admins->touchLastLogin((int) $admin['id']);
            $this->admins->logActivity((int) $admin['id'], 'ADMIN_LOGIN', 'Super Admin signed in');

            return ['ok' => true];
        } catch (PDOException $exception) {
            error_log('Admin login failed: ' . $exception->getMessage());
            return ['ok' => false, 'message' => 'Admin authentication is unavailable. Please try again later.'];
        }
    }

    public function logout(): void
    {
        $adminId = (int) ($_SESSION['admin_user']['id'] ?? 0);
        if ($adminId > 0) {
            try {
                $this->admins->logActivity($adminId, 'ADMIN_LOGOUT', 'Super Admin signed out');
            } catch (PDOException $exception) {
                error_log('Admin logout logging failed: ' . $exception->getMessage());
            }
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }
}
