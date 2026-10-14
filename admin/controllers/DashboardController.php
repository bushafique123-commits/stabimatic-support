<?php

final class DashboardController
{
    private $dashboard;

    public function __construct(?AdminDashboardService $dashboard = null)
    {
        $this->dashboard = $dashboard ?: new AdminDashboardService();
    }

    public function index(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $summary = $this->dashboard->summary();

        View::render('dashboard/index', [
            'title' => 'Dashboard',
            'admin' => $admin,
            'dashboard' => $summary,
            'stats' => $summary['stats'],
            'recentActivity' => $summary['recent_activity'],
            'statusItems' => $summary['status'],
        ]);
    }
}
