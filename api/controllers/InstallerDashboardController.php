<?php

final class InstallerDashboardController
{
    private $dashboard;

    public function __construct(?InstallerDashboardService $dashboard = null)
    {
        $this->dashboard = $dashboard ?: new InstallerDashboardService();
    }

    public function index(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        RoleMiddleware::require($claims, [ROLE_INSTALLER]);

        $result = $this->dashboard->summary($claims);
        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Dashboard unavailable', $result['errors'] ?? [], $result['status'] ?? 503);
        }

        unset($result['ok']);
        Response::success('Installer dashboard loaded', $result);
    }
}
