<?php

final class SettingsController
{
    private $settings;

    public function __construct(?AdminSettingService $settings = null)
    {
        $this->settings = $settings ?: new AdminSettingService();
    }

    public function index(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->settings->form();

        View::render('settings/index', [
            'title' => 'Settings',
            'admin' => $admin,
            'settings' => $_SESSION['_old_settings'] ?? $result['settings'],
            'errors' => $_SESSION['_setting_errors'] ?? $result['errors'],
            'success' => $_SESSION['_flash_success'] ?? '',
            'error' => $_SESSION['_flash_error'] ?? ($result['ok'] ? '' : ($result['message'] ?? 'Settings are unavailable.')),
        ]);

        unset($_SESSION['_old_settings'], $_SESSION['_setting_errors'], $_SESSION['_flash_success'], $_SESSION['_flash_error']);
    }

    public function update(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->settings->update($admin, $_POST);
        if ($result['ok'] ?? false) {
            $_SESSION['_flash_success'] = $result['message'] ?? 'Settings updated.';
        } else {
            $_SESSION['_flash_error'] = $result['message'] ?? 'Settings could not be saved.';
            $_SESSION['_old_settings'] = $result['settings'];
            $_SESSION['_setting_errors'] = $result['errors'];
        }

        header('Location: /admin/settings');
        exit;
    }
}
