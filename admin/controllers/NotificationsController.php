<?php

final class NotificationsController
{
    private $notifications;

    public function __construct(?AdminNotificationService $notifications = null)
    {
        $this->notifications = $notifications ?: new AdminNotificationService();
    }

    public function index(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->notifications->list($_GET);

        View::render('notifications/index', [
            'title' => 'Notifications',
            'admin' => $admin,
            'stats' => $result['stats'],
            'notifications' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['last_page'],
            'filters' => $result['filters'],
            'form' => $_SESSION['_old_notification'] ?? $result['form'],
            'errors' => $_SESSION['_notification_errors'] ?? $result['errors'],
            'success' => $_SESSION['_flash_success'] ?? '',
            'error' => $_SESSION['_flash_error'] ?? ($result['ok'] ? '' : ($result['message'] ?? 'Notifications are unavailable.')),
        ]);

        unset($_SESSION['_old_notification'], $_SESSION['_notification_errors'], $_SESSION['_flash_success'], $_SESSION['_flash_error']);
    }

    public function store(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->notifications->create($admin, $_POST);
        if ($result['ok'] ?? false) {
            $_SESSION['_flash_success'] = ($result['message'] ?? 'Notification queued.') . ' Recipients: ' . (int) ($result['queued'] ?? 0) . '.';
            header('Location: /admin/notifications/' . $result['batch_id']);
            exit;
        }

        $_SESSION['_flash_error'] = $result['message'] ?? 'Notification could not be queued.';
        $_SESSION['_old_notification'] = $result['form'];
        $_SESSION['_notification_errors'] = $result['errors'];
        header('Location: /admin/notifications');
        exit;
    }

    public function show(string $batchId): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->notifications->detail($batchId);

        if (!($result['ok'] ?? false)) {
            http_response_code((int) ($result['status'] ?? 404));
            View::render('notifications/show', [
                'title' => 'Notification Not Found',
                'admin' => $admin,
                'batch' => null,
                'recipients' => [],
                'success' => '',
                'error' => $result['message'] ?? 'Notification batch not found.',
            ]);
            return;
        }

        View::render('notifications/show', [
            'title' => $result['batch']['title'],
            'admin' => $admin,
            'batch' => $result['batch'],
            'recipients' => $result['recipients'],
            'success' => $_SESSION['_flash_success'] ?? '',
            'error' => $_SESSION['_flash_error'] ?? '',
        ]);

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);
    }

    public function updateStatus(string $batchId): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->notifications->updateStatus($admin, $batchId, $_POST);
        if ($result['ok'] ?? false) {
            $_SESSION['_flash_success'] = $result['message'] ?? 'Notification status updated.';
        } else {
            $_SESSION['_flash_error'] = $result['message'] ?? 'Notification status could not be updated.';
        }

        header('Location: /admin/notifications/' . $batchId);
        exit;
    }
}
