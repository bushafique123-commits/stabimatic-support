<?php

final class InstallersController
{
    private $installers;

    public function __construct(?AdminInstallerService $installers = null)
    {
        $this->installers = $installers ?: new AdminInstallerService();
    }

    public function index(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->installers->list($_GET);

        View::render('installers/index', [
            'title' => 'Installers',
            'admin' => $admin,
            'installers' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['last_page'],
            'filters' => $result['filters'],
            'error' => $result['ok'] ? '' : ($result['message'] ?? 'Installer management is unavailable.'),
            'success' => $_SESSION['_flash_success'] ?? '',
            'flashError' => $_SESSION['_flash_error'] ?? '',
        ]);

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);
    }

    public function show(int $installerId): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->installers->detail($installerId);

        if (!($result['ok'] ?? false)) {
            http_response_code((int) ($result['status'] ?? 404));
            View::render('installers/show', [
                'title' => 'Installer Not Found',
                'admin' => $admin,
                'installer' => null,
                'stats' => [],
                'recentInstallations' => [],
                'error' => $result['message'] ?? 'Installer not found.',
                'success' => '',
                'flashError' => '',
            ]);
            return;
        }

        View::render('installers/show', [
            'title' => $result['installer']['full_name'],
            'admin' => $admin,
            'installer' => $result['installer'],
            'stats' => $result['stats'],
            'recentInstallations' => $result['recent_installations'],
            'error' => '',
            'success' => $_SESSION['_flash_success'] ?? '',
            'flashError' => $_SESSION['_flash_error'] ?? '',
        ]);

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);
    }

    public function setActive(int $installerId, bool $active): void
    {
        $this->handleMutation($installerId, function (array $admin) use ($installerId, $active): array {
            return $this->installers->setActive($admin, $installerId, $active);
        });
    }

    public function setVerified(int $installerId, bool $verified): void
    {
        $this->handleMutation($installerId, function (array $admin) use ($installerId, $verified): array {
            return $this->installers->setVerified($admin, $installerId, $verified);
        });
    }

    private function handleMutation(int $installerId, callable $operation): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $operation($admin);
        if ($result['ok'] ?? false) {
            $_SESSION['_flash_success'] = $result['message'] ?? 'Installer account updated.';
        } else {
            $_SESSION['_flash_error'] = $result['message'] ?? 'Installer account could not be updated.';
        }

        header('Location: /admin/installers/' . $installerId);
        exit;
    }
}
