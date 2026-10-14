<?php

final class ActivityLogsController
{
    private $activityLogs;

    public function __construct(?AdminActivityLogService $activityLogs = null)
    {
        $this->activityLogs = $activityLogs ?: new AdminActivityLogService();
    }

    public function index(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->activityLogs->list($_GET);

        View::render('activity-logs/index', [
            'title' => 'Activity Logs',
            'admin' => $admin,
            'activityLogs' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['last_page'],
            'filters' => $result['filters'],
            'modules' => $result['modules'],
            'actions' => $result['actions'],
            'error' => $result['ok'] ? '' : ($result['message'] ?? 'Activity logs are unavailable.'),
        ]);
    }

    public function show(int $activityLogId): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->activityLogs->detail($activityLogId);

        if (!($result['ok'] ?? false)) {
            http_response_code((int) ($result['status'] ?? 404));
            View::render('activity-logs/show', [
                'title' => 'Activity Log Not Found',
                'admin' => $admin,
                'activity' => null,
                'error' => $result['message'] ?? 'Activity log not found.',
            ]);
            return;
        }

        View::render('activity-logs/show', [
            'title' => $result['activity']['action'],
            'admin' => $admin,
            'activity' => $result['activity'],
            'error' => '',
        ]);
    }
}
