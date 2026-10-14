<?php

final class NotificationController
{
    private $notifications;

    public function __construct(?NotificationService $notifications = null)
    {
        $this->notifications = $notifications ?: new NotificationService();
    }

    public function registerDevice(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->notifications->registerDevice($claims, $request->input());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Device token could not be registered', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Device token registered', $result['device']);
    }

    public function removeDevice(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->notifications->removeDevice($claims, $request->input());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Device token could not be removed', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Device token removed');
    }

    public function preferences(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->notifications->preferences($claims);

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Notification preferences could not be loaded', $result['errors'] ?? [], $result['status'] ?? 503);
        }

        Response::success('Notification preferences loaded', $result['preferences']);
    }

    public function updatePreferences(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->notifications->updatePreferences($claims, $request->input());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Notification preferences could not be saved', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Notification preferences saved', $result['preferences']);
    }

    public function inbox(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->notifications->inbox($claims, $request->query());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Notifications could not be loaded', $result['errors'] ?? [], $result['status'] ?? 503);
        }

        Response::paginated('Notifications loaded', $result['items'], (int) $result['total'], (int) $result['page'], (int) $result['limit']);
    }

    public function show(Request $request, string $id): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->notifications->show($claims, (int) $id);

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Notification not found', $result['errors'] ?? [], $result['status'] ?? 404);
        }

        Response::success('Notification loaded', $result['notification']);
    }

    public function unreadCount(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->notifications->unreadCount($claims);

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Notification count could not be loaded', $result['errors'] ?? [], $result['status'] ?? 503);
        }

        Response::success('Notification count loaded', ['unread_count' => (int) $result['unread_count']]);
    }

    public function markRead(Request $request, string $id): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->notifications->markRead($claims, (int) $id);

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Notification could not be marked read', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Notification marked read');
    }

    public function markAllRead(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->notifications->markAllRead($claims);

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Notifications could not be marked read', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Notifications marked read', ['updated' => (int) $result['updated']]);
    }
}
