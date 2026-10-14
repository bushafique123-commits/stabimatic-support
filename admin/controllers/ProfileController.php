<?php

final class ProfileController
{
    private $profiles;

    public function __construct(?AdminProfileService $profiles = null)
    {
        $this->profiles = $profiles ?: new AdminProfileService();
    }

    public function index(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->profiles->detail((int) $admin['id']);

        View::render('profile/index', [
            'title' => 'Profile',
            'admin' => $admin,
            'profile' => $_SESSION['_old_profile'] ?? $result['profile'],
            'profileErrors' => $_SESSION['_profile_errors'] ?? [],
            'passwordErrors' => $_SESSION['_password_errors'] ?? [],
            'success' => $_SESSION['_flash_success'] ?? '',
            'error' => $_SESSION['_flash_error'] ?? ($result['ok'] ? '' : ($result['message'] ?? 'Profile is unavailable.')),
        ]);

        unset($_SESSION['_old_profile'], $_SESSION['_profile_errors'], $_SESSION['_password_errors'], $_SESSION['_flash_success'], $_SESSION['_flash_error']);
    }

    public function update(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->profiles->updateProfile($admin, $_POST);
        if ($result['ok']) {
            $_SESSION['_flash_success'] = $result['message'] ?? 'Profile updated.';
        } else {
            $_SESSION['_flash_error'] = $result['message'] ?? 'Profile could not be saved.';
            $_SESSION['_old_profile'] = $result['profile'];
            $_SESSION['_profile_errors'] = $result['errors'];
        }

        header('Location: /admin/profile');
        exit;
    }

    public function changePassword(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->profiles->changePassword((int) $admin['id'], $_POST);
        if ($result['ok']) {
            $_SESSION['_flash_success'] = $result['message'] ?? 'Password changed.';
        } else {
            $_SESSION['_flash_error'] = $result['message'] ?? 'Password could not be changed.';
            $_SESSION['_password_errors'] = $result['errors'];
        }

        header('Location: /admin/profile');
        exit;
    }
}
