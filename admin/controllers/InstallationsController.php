<?php

final class InstallationsController
{
    private $installations;

    public function __construct(?AdminInstallationService $installations = null)
    {
        $this->installations = $installations ?: new AdminInstallationService();
    }

    public function index(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->installations->list($_GET);

        View::render('installations/index', [
            'title' => 'Installations',
            'admin' => $admin,
            'installations' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['last_page'],
            'filters' => $result['filters'],
            'success' => $_SESSION['_flash_success'] ?? '',
            'error' => $_SESSION['_flash_error'] ?? ($result['ok'] ? '' : ($result['message'] ?? 'Installation management is unavailable.')),
        ]);

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);
    }

    public function show(int $installationId): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->installations->detail($installationId);

        if (!($result['ok'] ?? false)) {
            http_response_code((int) ($result['status'] ?? 404));
            View::render('installations/show', [
                'title' => 'Installation Not Found',
                'admin' => $admin,
                'installation' => null,
                'matchedSale' => null,
                'activity' => [],
                'error' => $result['message'] ?? 'Installation not found.',
            ]);
            return;
        }

        View::render('installations/show', [
            'title' => $result['installation']['serial_number'],
            'admin' => $admin,
            'installation' => $result['installation'],
            'matchedSale' => $result['matched_sale'],
            'activity' => $result['activity'],
            'error' => '',
        ]);
    }
}
