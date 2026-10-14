<?php

final class NotificationService
{
    private const DEFAULT_PREFERENCES = [
        'sales_notifications' => true,
        'installation_notifications' => true,
        'account_notifications' => true,
        'marketing_notifications' => false,
    ];

    private $users;
    private $notifications;

    public function __construct(?UserRepository $users = null, ?NotificationRepository $notifications = null)
    {
        $this->users = $users ?: new UserRepository();
        $this->notifications = $notifications ?: new NotificationRepository();
    }

    public function registerDevice(array $claims, array $input): array
    {
        $data = [
            'platform' => InputNormalizer::string($input['platform'] ?? ''),
            'device_token' => InputNormalizer::string($input['device_token'] ?? ''),
            'device_name' => InputNormalizer::string($input['device_name'] ?? ''),
            'app_version' => InputNormalizer::string($input['app_version'] ?? ''),
        ];
        $errors = Validator::validate($data, [
            'platform' => ['required', 'in:ios,android,web'],
            'device_token' => ['required', 'max:255'],
            'device_name' => ['max:150'],
            'app_version' => ['max:40'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            $device = $this->notifications->upsertDeviceToken((int) $user['id'], $user['role'], $data);
            Logger::activity($user['role'], (int) $user['id'], strtoupper($user['role']) . '_DEVICE_TOKEN_REGISTERED', 'notifications', (int) ($device['id'] ?? 0), 'User registered notification device');

            return ['ok' => true, 'device' => $this->deviceResponse($device)];
        } catch (PDOException $exception) {
            Logger::error('Device token registration unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function removeDevice(array $claims, array $input): array
    {
        $data = ['device_token' => InputNormalizer::string($input['device_token'] ?? '')];
        $errors = Validator::validate($data, [
            'device_token' => ['required', 'max:255'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            $this->notifications->deactivateDeviceToken((int) $user['id'], $data['device_token']);
            Logger::activity($user['role'], (int) $user['id'], strtoupper($user['role']) . '_DEVICE_TOKEN_REMOVED', 'notifications', (int) $user['id'], 'User removed notification device');

            return ['ok' => true];
        } catch (PDOException $exception) {
            Logger::error('Device token removal unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function preferences(array $claims): array
    {
        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            $preferences = $this->notifications->preferencesForUser((int) $user['id']);
            return ['ok' => true, 'preferences' => $this->preferencesResponse($preferences)];
        } catch (PDOException $exception) {
            Logger::error('Notification preferences unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function updatePreferences(array $claims, array $input): array
    {
        $preferences = [
            'sales_notifications' => $this->booleanValue($input['sales_notifications'] ?? true),
            'installation_notifications' => $this->booleanValue($input['installation_notifications'] ?? true),
            'account_notifications' => $this->booleanValue($input['account_notifications'] ?? true),
            'marketing_notifications' => $this->booleanValue($input['marketing_notifications'] ?? false),
        ];

        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            $saved = $this->notifications->savePreferences((int) $user['id'], $preferences);
            Logger::activity($user['role'], (int) $user['id'], strtoupper($user['role']) . '_NOTIFICATION_PREFERENCES_UPDATED', 'notifications', (int) $user['id'], 'User updated notification preferences');

            return ['ok' => true, 'preferences' => $this->preferencesResponse($saved)];
        } catch (PDOException $exception) {
            Logger::error('Notification preference update unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function inbox(array $claims, array $query): array
    {
        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            $page = Paginator::page($query);
            $limit = Paginator::limit($query, 20, 50);
            $filters = [
                'status' => in_array(($query['status'] ?? ''), ['read', 'unread'], true) ? $query['status'] : '',
                'notification_type' => in_array(($query['notification_type'] ?? ''), ['general', 'sales', 'installation', 'account', 'marketing'], true) ? $query['notification_type'] : '',
            ];
            $result = $this->notifications->paginateForUser((int) $user['id'], $filters, $page, $limit);

            return [
                'ok' => true,
                'items' => array_map([$this, 'notificationResponse'], $result['items']),
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
            ];
        } catch (PDOException $exception) {
            Logger::error('Notification inbox unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function show(array $claims, int $notificationId): array
    {
        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            $notification = $this->notifications->findForUser((int) $user['id'], $notificationId);
            if (!$notification) {
                return ['ok' => false, 'status' => 404, 'message' => 'Notification not found', 'errors' => ['notification' => 'Notification not found']];
            }

            if (!$notification['read_at']) {
                $this->notifications->markRead((int) $user['id'], $notificationId);
                $notification = $this->notifications->findForUser((int) $user['id'], $notificationId) ?: $notification;
            }

            return ['ok' => true, 'notification' => $this->notificationResponse($notification)];
        } catch (PDOException $exception) {
            Logger::error('Notification detail unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function unreadCount(array $claims): array
    {
        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            return ['ok' => true, 'unread_count' => $this->notifications->unreadCountForUser((int) $user['id'])];
        } catch (PDOException $exception) {
            Logger::error('Notification unread count unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function markRead(array $claims, int $notificationId): array
    {
        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            if (!$this->notifications->findForUser((int) $user['id'], $notificationId)) {
                return ['ok' => false, 'status' => 404, 'message' => 'Notification not found', 'errors' => ['notification' => 'Notification not found']];
            }

            $this->notifications->markRead((int) $user['id'], $notificationId);
            return ['ok' => true];
        } catch (PDOException $exception) {
            Logger::error('Notification read update unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function markAllRead(array $claims): array
    {
        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            return ['ok' => true, 'updated' => $this->notifications->markAllRead((int) $user['id'])];
        } catch (PDOException $exception) {
            Logger::error('Notification bulk read update unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    private function activeUser(array $claims): ?array
    {
        $user = $this->users->findById((int) ($claims['sub'] ?? 0));
        if (!$user || !in_array($user['role'], [ROLE_DEALER, ROLE_INSTALLER], true) || !(int) $user['is_active']) {
            return null;
        }

        return $user;
    }

    private function deviceResponse(array $device): array
    {
        return [
            'id' => (int) ($device['id'] ?? 0),
            'platform' => $device['platform'] ?? '',
            'device_name' => $device['device_name'] ?? '',
            'app_version' => $device['app_version'] ?? '',
            'is_active' => (bool) ($device['is_active'] ?? false),
            'last_seen_at' => $device['last_seen_at'] ?? null,
        ];
    }

    private function preferencesResponse(?array $preferences): array
    {
        $preferences = $preferences ?: self::DEFAULT_PREFERENCES;
        return [
            'sales_notifications' => (bool) $preferences['sales_notifications'],
            'installation_notifications' => (bool) $preferences['installation_notifications'],
            'account_notifications' => (bool) $preferences['account_notifications'],
            'marketing_notifications' => (bool) $preferences['marketing_notifications'],
        ];
    }

    private function notificationResponse(array $notification): array
    {
        return [
            'id' => (int) $notification['id'],
            'batch_id' => $notification['batch_id'],
            'audience' => $notification['audience'],
            'channel' => $notification['channel'],
            'notification_type' => $notification['notification_type'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'payload' => $this->payload($notification['payload'] ?? null),
            'status' => $notification['status'],
            'queued_at' => $notification['queued_at'],
            'sent_at' => $notification['sent_at'],
            'read_at' => $notification['read_at'],
            'is_read' => !empty($notification['read_at']),
            'created_at' => $notification['created_at'],
            'updated_at' => $notification['updated_at'],
        ];
    }

    private function payload($payload): array
    {
        if (!$payload) {
            return [];
        }
        $decoded = json_decode((string) $payload, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function booleanValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array($value, [1, '1', 'true', 'yes', 'on'], true);
    }

    private function forbidden(): array
    {
        return ['ok' => false, 'status' => 403, 'message' => 'Notifications are not available for this account', 'errors' => ['notifications' => 'Notifications are not available']];
    }

    private function unavailable(): array
    {
        return ['ok' => false, 'status' => 503, 'message' => 'Notification service is unavailable', 'errors' => ['notifications' => 'Please try again later']];
    }
}
