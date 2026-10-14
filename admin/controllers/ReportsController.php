<?php

final class ReportsController
{
    private $reports;

    public function __construct(?AdminReportService $reports = null)
    {
        $this->reports = $reports ?: new AdminReportService();
    }

    public function index(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $report = $this->reports->dashboard($_GET);

        View::render('reports/index', [
            'title' => 'Reports',
            'admin' => $admin,
            'report' => $report,
            'filters' => $report['filters'],
            'summary' => $report['summary'],
            'error' => $report['ok'] ? '' : ($report['message'] ?? 'Reports are unavailable.'),
        ]);
    }
}
