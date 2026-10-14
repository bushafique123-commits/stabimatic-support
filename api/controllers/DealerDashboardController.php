<?php

final class DealerDashboardController
{
    private $dashboard;

    public function __construct(?DealerDashboardService $dashboard = null)
    {
        $this->dashboard = $dashboard ?: new DealerDashboardService();
    }

    public function index(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        RoleMiddleware::require($claims, [ROLE_DEALER]);

        $result = $this->dashboard->summary($claims);
        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Dashboard unavailable', $result['errors'] ?? [], $result['status'] ?? 503);
        }

        unset($result['ok']);
        Response::success('Dealer dashboard loaded', $result);
    }
}
